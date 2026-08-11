<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Create a new payment intent for the given order.
     *
     * @param Order $order
     * @return array
     */
    public function createPaymentIntent(Order $order): array;

    /**
     * Retrieve a payment intent by ID.
     *
     * @param string $paymentIntentId
     * @return array
     */
    public function retrievePaymentIntent(string $paymentIntentId): array;

    /**
     * Create a new Stripe Checkout Session for the given order and return redirect URL.
     *
     * @param Order $order
     * @return string
     */
    public function createCheckoutSession(Order $order): string;

    /**
     * Handle webhook event and return true if successfully processed.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function handleWebhook(string $payload, string $signature): bool;
}
