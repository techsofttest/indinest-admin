<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\StripeWebhookLog;
use App\Models\ProductVariant;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Mail\OrderConfirmationMail;
use App\Mail\AdminOrderNotificationMail;
use App\Services\MailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripePaymentService implements PaymentGatewayInterface
{
    protected StripeClient $stripe;
    protected string $currency;

    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if (!empty($secret)) {
            $this->stripe = new StripeClient($secret);
        }
        $this->currency = config('services.stripe.currency', 'GBP');
    }

    public function createPaymentIntent(Order $order): array
    {
        $amount = (int) round($order->grand_total * 100);

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => strtolower($order->payment_currency ?: $this->currency),
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
            ],
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'transaction_type' => 'payment_intent_created',
            'payment_intent' => $paymentIntent->id,
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->payment_currency ?: $this->currency,
            'response' => $paymentIntent->toArray(),
        ]);

        $order->update([
            'payment_amount' => $order->grand_total,
            'payment_currency' => $order->payment_currency ?: $this->currency,
            'stripe_payment_intent' => $paymentIntent->id,
            'payment_status' => 'pending',
        ]);

        return [
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $order->grand_total,
            'currency' => $order->payment_currency ?: $this->currency,
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
        return $paymentIntent->toArray();
    }

    public function createCheckoutSession(Order $order): string
    {
        $amount = (int) round($order->grand_total * 100);
        $appUrl = rtrim(config('services.frontend.url', env('FRONTEND_URL', 'http://localhost:3000')), '/');

        $successUrl = $appUrl . '/checkout/success?order_id=' . $order->id . '&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $appUrl . '/checkout?order_id=' . $order->id . '&cancel=true';

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($order->payment_currency ?: $this->currency),
                    'product_data' => [
                        'name' => 'Order #' . $order->order_number,
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $order->customer_email,
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => (string) $order->order_number,
                ],
            ],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
            ],
        ]);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
            'payment_status' => 'pending',
            'status' => 'pending_payment',
        ]);

        return $session->url;
    }

    public function handleWebhook(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        $tolerance = config('services.stripe.webhook_tolerance', 300);

        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret, $tolerance);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            throw $e;
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            throw $e;
        }

        $eventId = $event->id;
        $eventType = $event->type;

        Log::info("Stripe webhook received", [
            'event' => $eventType,
            'event_id' => $eventId,
        ]);

        // Idempotency check: check if event has already been processed
        $existingLog = StripeWebhookLog::where('event_id', $eventId)->first();
        if ($existingLog && $existingLog->processed) {
            Log::info("Stripe event {$eventId} already processed.");
            return true;
        }

        $webhookLog = StripeWebhookLog::updateOrCreate(
            ['event_id' => $eventId],
            [
                'provider' => 'stripe',
                'event_type' => $eventType,
                'payload' => $event->toArray(),
                'processed' => false,
            ]
        );

        try {
            DB::transaction(function () use ($event, $eventType, $eventId, $webhookLog) {
                switch ($eventType) {
                    case 'checkout.session.completed':
                    case 'checkout.session.async_payment_succeeded':
                        $session = $event->data->object;
                        $paymentStatus = $session->payment_status ?? 'paid';
                        if ($paymentStatus === 'paid') {
                            $this->processSuccessfulCheckoutSession($session, $eventId);
                        } else {
                            Log::info("Checkout Session {$session->id} completed with payment_status={$paymentStatus}; skipping immediate payment confirmation.");
                        }
                        break;

                    case 'checkout.session.async_payment_failed':
                        $this->processFailedCheckoutSession($event->data->object, $eventId);
                        break;

                    case 'charge.refunded':
                        $this->handleChargeRefunded($event->data->object, $eventId);
                        break;

                    default:
                        Log::info("Ignored Stripe webhook event: {$eventType} for event_id {$eventId}");
                        break;
                }

                $webhookLog->update([
                    'processed' => true,
                    'error' => null,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Error processing Stripe Webhook Event {$eventId}: " . $e->getMessage());
            $webhookLog->update([
                'processed' => false,
                'error' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function processSuccessfulCheckoutSession($session, string $eventId): void
    {
        $orderId = $session->metadata->order_id ?? null;
        $order = null;

        if ($orderId) {
            $order = Order::with('items')->lockForUpdate()->find($orderId);
        }

        if (!$order) {
            $order = Order::with('items')->where('stripe_checkout_session_id', $session->id)->lockForUpdate()->first();
        }

        if (!$order) {
            Log::error("Order not found for Stripe Checkout Session {$session->id}");
            throw new \Exception("Order not found for Stripe Checkout Session {$session->id}");
        }

        Log::info("Processing successful Checkout Session for order {$order->order_number}", [
            'event' => 'checkout.session.completed',
            'event_id' => $eventId,
            'checkout_session' => $session->id,
            'order' => $order->order_number,
            'payment_status' => $session->payment_status ?? 'paid',
        ]);

        $paymentIntentId = $session->payment_intent ?? null;
        $chargeId = null;

        if ($paymentIntentId && !empty($this->stripe)) {
            try {
                $pi = $this->stripe->paymentIntents->retrieve($paymentIntentId);
                $chargeId = $pi->latest_charge ?? null;
            } catch (\Exception $e) {
                Log::error("Failed to retrieve PaymentIntent from Stripe: " . $e->getMessage());
            }
        }

        $meta = (array) ($order->payment_metadata ?? []);

        // --- 1. Payment Status Transition ---
        $currentPaymentStatus = $order->payment_status->value ?? (string) $order->payment_status;
        if ($currentPaymentStatus !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'stripe_payment_intent' => $paymentIntentId ?? $order->stripe_payment_intent,
                'stripe_charge_id' => $chargeId ?? $order->stripe_charge_id,
                'paid_at' => $order->paid_at ?? now(),
                'payment_metadata' => array_merge($meta, [
                    'checkout_session_id' => $session->id,
                    'checkout_session_completed_event_id' => $eventId,
                ]),
            ]);
            $meta = (array) $order->fresh()->payment_metadata;
            Log::info("Order {$order->order_number} marked as paid.");
        } else {
            Log::info("Order {$order->order_number} is already paid; payment transition skipped.");
        }

        // --- 2. Payment Transaction Recording ---
        $existingTx = PaymentTransaction::where('order_id', $order->id)
            ->where('event_id', $eventId)
            ->first();

        if (!$existingTx) {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway' => 'stripe',
                'transaction_type' => TransactionType::PAYMENT_INTENT_SUCCEEDED,
                'payment_intent' => $paymentIntentId,
                'charge_id' => $chargeId,
                'event_id' => $eventId,
                'status' => TransactionStatus::SUCCEEDED,
                'amount' => isset($session->amount_total) ? ($session->amount_total / 100) : $order->grand_total,
                'currency' => strtoupper($session->currency ?? $this->currency),
                'response' => (array) $session,
            ]);
            Log::info("Payment transaction recorded for order {$order->order_number}.");
        } else {
            Log::info("Payment transaction already recorded for event {$eventId}; transaction recording skipped.");
        }

        // --- 3. Stock / Inventory Deduction ---
        $inventoryDeducted = $meta['inventory_deducted'] ?? false;
        if (!$inventoryDeducted) {
            try {
                foreach ($order->items as $item) {
                    if ($item->variant_id) {
                        $variant = ProductVariant::find($item->variant_id);
                        if ($variant) {
                            $oldStock = $variant->stock;
                            $variant->decrement('stock', $item->quantity);
                            Log::info("Stock updated for variant {$item->variant_id}: reduced from {$oldStock} to {$variant->stock} by quantity {$item->quantity}");
                        }
                    }
                }
                $meta['inventory_deducted'] = true;
                $order->update(['payment_metadata' => $meta]);
            } catch (\Exception $e) {
                Log::error("Error reducing inventory for order {$order->id}: " . $e->getMessage());
                throw $e;
            }
        } else {
            Log::info("Stock already deducted for order {$order->order_number}; stock deduction skipped.");
        }

        // --- 4. Coupon Usage Logging ---
        if ($order->coupon_code) {
            $alreadyLogged = \App\Models\CouponUsage::where('order_id', $order->id)->exists();
            if (!$alreadyLogged) {
                $coupon = \App\Models\Coupon::where('coupon_code', $order->coupon_code)->first();
                if ($coupon) {
                    \App\Models\CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'customer_id' => $order->customer_id,
                        'order_id' => $order->id,
                        'discount_amount' => $order->discount,
                    ]);
                }
            }
        }

        // --- 5. Confirmation Email Dispatch ---
        $emailSent = $meta['confirmation_email_sent'] ?? false;
        if (!$emailSent) {
            $order->loadMissing('items');

            // Customer confirmation email
            $customerEmail = $order->customer_email ?? $order->email ?? null;
            if ($customerEmail) {
                try {
                    Mail::to($customerEmail)->send(new OrderConfirmationMail($order));
                    Log::info("Confirmation email dispatched for customer: {$customerEmail} on order {$order->order_number}");
                } catch (\Throwable $e) {
                    Log::error("Failed to send order confirmation email to customer for order {$order->order_number}: " . $e->getMessage());
                }
            }

            // Admin notification email
            $mailService = app(MailService::class);
            $adminRecipients = $mailService->adminRecipients();
            if (!empty($adminRecipients)) {
                try {
                    $mailService->sendToMany($adminRecipients, new AdminOrderNotificationMail($order));
                    Log::info("Confirmation email dispatched for admin: " . implode(', ', $adminRecipients) . " on order {$order->order_number}");
                } catch (\Throwable $e) {
                    Log::error("Failed to send admin order notification for order {$order->order_number}: " . $e->getMessage());
                }
            }

            $meta['confirmation_email_sent'] = true;
            $meta['confirmation_email_sent_at'] = now()->toIso8601String();
            $order->update(['payment_metadata' => $meta]);
        } else {
            Log::info("Confirmation email already dispatched for order {$order->order_number}; email dispatch skipped.");
        }
    }

    protected function processFailedCheckoutSession($session, string $eventId): void
    {
        $orderId = $session->metadata->order_id ?? null;
        $order = null;

        if ($orderId) {
            $order = Order::find($orderId);
        }

        if (!$order) {
            $order = Order::where('stripe_checkout_session_id', $session->id)->first();
        }

        if (!$order) {
            Log::warning("Order not found for failed Stripe Checkout Session {$session->id}");
            return;
        }

        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'transaction_type' => 'checkout.session.async_payment_failed',
            'payment_intent' => $session->payment_intent ?? null,
            'event_id' => $eventId,
            'status' => 'failed',
            'amount' => isset($session->amount_total) ? ($session->amount_total / 100) : $order->grand_total,
            'currency' => strtoupper($session->currency ?? $this->currency),
            'response' => (array) $session,
        ]);

        $order->update([
            'payment_status' => 'failed',
            'payment_failure_reason' => 'Async payment failed',
        ]);
        Log::info("Order {$order->order_number} async payment marked as failed.");
    }

    protected function handleChargeRefunded($charge, string $eventId): void
    {
        $order = Order::where('stripe_charge_id', $charge->id)->first();
        if (!$order) {
            $order = Order::where('stripe_payment_intent', $charge->payment_intent)->first();
        }
        if (!$order) return;

        $refundedAmount = $charge->amount_refunded / 100;
        $isFullRefund = $charge->refunded;

        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'transaction_type' => TransactionType::CHARGE_REFUNDED,
            'payment_intent' => $charge->payment_intent,
            'charge_id' => $charge->id,
            'event_id' => $eventId,
            'status' => $isFullRefund ? TransactionStatus::REFUNDED : TransactionStatus::PARTIALLY_REFUNDED,
            'amount' => $refundedAmount,
            'currency' => strtoupper($charge->currency),
            'response' => (array) $charge,
        ]);

        $order->update([
            'payment_status' => $isFullRefund ? 'refunded' : 'partially_refunded',
            'status' => $isFullRefund ? 'refunded' : $order->status,
        ]);
        Log::info("Order {$order->order_number} charge refunded: amount {$refundedAmount}");
    }
}
