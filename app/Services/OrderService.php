<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class OrderService
{
    /**
     * Mark a confirmed/processing order as shipped.
     */
    public function markAsShipped(Order $order): void
    {
        DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $paymentStatus = $lockedOrder->payment_status instanceof \BackedEnum
                ? $lockedOrder->payment_status->value
                : (string) $lockedOrder->payment_status;

            if ($lockedOrder->order_type === 'order' && $paymentStatus !== PaymentStatus::PAID->value && $paymentStatus !== 'paid') {
                throw new InvalidArgumentException("Payment is required before marking an order as shipped.");
            }

            $currentStatus = $lockedOrder->status instanceof \BackedEnum
                ? $lockedOrder->status->value
                : (string) $lockedOrder->status;

            if (!in_array($currentStatus, ['confirmed', 'processing'], true)) {
                throw new InvalidArgumentException("Order cannot be marked as shipped from current status: '{$currentStatus}'.");
            }

            $lockedOrder->update([
                'status' => OrderStatus::SHIPPED,
            ]);

            $recipient = $lockedOrder->customer_email ?: $lockedOrder->email;
            if ($recipient) {
                try {
                    $lockedOrder->loadMissing('items');
                    Mail::to($recipient)->send(new OrderShippedMail($lockedOrder));
                } catch (\Exception $mailEx) {
                    Log::error("Failed to send order shipped email for order {$lockedOrder->order_number}: " . $mailEx->getMessage());
                }
            }

            // Sync model instance passed to service
            $order->status = OrderStatus::SHIPPED;
        });
    }

    /**
     * Mark a shipped order as delivered.
     */
    public function markAsDelivered(Order $order): void
    {
        DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $currentStatus = $lockedOrder->status instanceof \BackedEnum
                ? $lockedOrder->status->value
                : (string) $lockedOrder->status;

            if ($currentStatus !== 'shipped' && $currentStatus !== OrderStatus::SHIPPED->value) {
                throw new InvalidArgumentException("Order cannot be marked as delivered from current status: '{$currentStatus}'.");
            }

            $lockedOrder->update([
                'status' => OrderStatus::DELIVERED,
            ]);

            // Delivered email notification temporarily disabled
            /*
            $recipient = $lockedOrder->customer_email ?: $lockedOrder->email;
            if ($recipient) {
                try {
                    $lockedOrder->loadMissing('items');
                    Mail::to($recipient)->send(new OrderDeliveredMail($lockedOrder));
                } catch (\Exception $mailEx) {
                    Log::error("Failed to send order delivered email for order {$lockedOrder->order_number}: " . $mailEx->getMessage());
                }
            }
            */

            // Sync model instance passed to service
            $order->status = OrderStatus::DELIVERED;
        });
    }
}
