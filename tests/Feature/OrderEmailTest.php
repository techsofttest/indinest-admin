<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\AdminOrderNotificationMail;
use App\Mail\ContactEnquiryMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderEnquiryMail;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'indinest',
            'database.connections.mysql.prefix' => 'in_',
        ]);
    }

    public function test_customer_order_confirmation_mail_renders_correctly(): void
    {
        Mail::fake();

        $brand = Brand::create(['name' => 'Brand Test', 'slug' => 'brand-test']);
        $category = Category::create(['name' => 'Category Test', 'slug' => 'category-test', 'is_active' => true]);

        $product = Product::create([
            'sku' => 'SKU-EM-1',
            'name' => 'Silk Saree',
            'slug' => 'silk-saree',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'IND-TEST-1001',
            'customer_id' => 1,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '07123456789',
            'country' => 'United Kingdom',
            'address' => '10 Downing Street',
            'city' => 'London',
            'state' => 'London',
            'pin_code' => 'SW1A 2AA',
            'shipping_name' => 'Test Customer',
            'shipping_address_line_1' => '10 Downing Street',
            'shipping_city' => 'London',
            'shipping_postcode' => 'SW1A 2AA',
            'shipping_country' => 'United Kingdom',
            'shipping_method' => 'standard',
            'payment_method' => 'stripe',
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::CONFIRMED,
            'subtotal' => 150.00,
            'shipping_cost' => 4.00,
            'discount' => 0.00,
            'grand_total' => 154.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Silk Saree',
            'variant_details' => 'Red / M',
            'quantity' => 1,
            'price' => 150.00,
            'line_total' => 150.00,
        ]);

        $order->load('items');

        Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));

        Mail::assertSent(OrderConfirmationMail::class, function ($mail) use ($order) {
            return $mail->hasTo('customer@example.com') &&
                   $mail->order->order_number === $order->order_number;
        });
    }

    public function test_admin_order_notification_mail_renders_correctly(): void
    {
        Mail::fake();

        $order = Order::create([
            'order_number' => 'IND-TEST-1002',
            'customer_name' => 'Priya Patel',
            'customer_email' => 'priya@example.com',
            'first_name' => 'Priya',
            'last_name' => 'Patel',
            'email' => 'priya@example.com',
            'phone' => '07999888777',
            'country' => 'United Kingdom',
            'address' => '15 Oxford St',
            'city' => 'London',
            'state' => 'London',
            'pin_code' => 'W1D 2DW',
            'shipping_name' => 'Priya Patel',
            'shipping_address_line_1' => '15 Oxford St',
            'shipping_city' => 'London',
            'shipping_postcode' => 'W1D 2DW',
            'shipping_country' => 'United Kingdom',
            'shipping_method' => 'standard',
            'payment_method' => 'stripe',
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::CONFIRMED,
            'subtotal' => 200.00,
            'shipping_cost' => 0.00,
            'discount' => 0.00,
            'grand_total' => 200.00,
        ]);

        $order->load('items');

        Mail::to('admin@indinest.com')->send(new AdminOrderNotificationMail($order));

        Mail::assertSent(AdminOrderNotificationMail::class, function ($mail) use ($order) {
            return $mail->hasTo('admin@indinest.com') &&
                   $mail->order->order_number === $order->order_number;
        });
    }

    public function test_order_enquiry_mail_renders_correctly(): void
    {
        Mail::fake();

        $order = Order::create([
            'order_number' => 'ENQ-TEST-5001',
            'customer_name' => 'Ananya Roy',
            'customer_email' => 'ananya@example.com',
            'customer_phone' => '07111222333',
            'first_name' => 'Ananya',
            'last_name' => 'Roy',
            'email' => 'ananya@example.com',
            'phone' => '07111222333',
            'country' => 'Germany',
            'address' => 'Berliner Str. 10',
            'city' => 'Berlin',
            'state' => 'Berlin',
            'pin_code' => '10115',
            'shipping_method' => 'standard',
            'payment_method' => 'enquiry',
            'payment_status' => PaymentStatus::NOT_REQUIRED,
            'status' => OrderStatus::PENDING,
            'subtotal' => 120.00,
            'shipping_cost' => 0.00,
            'discount' => 0.00,
            'grand_total' => 120.00,
        ]);

        Mail::to('admin@indinest.com')->send(new OrderEnquiryMail($order));

        Mail::assertSent(OrderEnquiryMail::class, function ($mail) use ($order) {
            return $mail->hasTo('admin@indinest.com') &&
                   $mail->enquiry->order_number === $order->order_number;
        });
    }

    public function test_stripe_checkout_session_completed_triggers_order_emails_and_idempotency(): void
    {
        Mail::fake();
        config(['app.admin_email' => 'techsofttest123@gmail.com']);

        $order = Order::create([
            'order_number' => 'IND-TEST-9999',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '07111222444',
            'country' => 'United Kingdom',
            'address' => '1 Baker St',
            'city' => 'London',
            'state' => 'London',
            'pin_code' => 'NW1 6XE',
            'shipping_method' => 'standard',
            'payment_method' => 'stripe',
            'payment_status' => PaymentStatus::PENDING,
            'status' => OrderStatus::PENDING,
            'stripe_checkout_session_id' => 'cs_test_9999',
            'subtotal' => 50.00,
            'shipping_cost' => 4.00,
            'discount' => 0.00,
            'grand_total' => 54.00,
        ]);

        $service = app(\App\Services\Payments\StripePaymentService::class);
        $sessionObj = (object)[
            'id' => 'cs_test_9999',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_123',
            'amount_total' => 5400,
            'currency' => 'gbp',
            'metadata' => (object)[
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ]
        ];

        // First delivery
        $service->processSuccessfulCheckoutSession($sessionObj, 'evt_test_1001');

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(AdminOrderNotificationMail::class, 1);

        // Verify order status updated to paid
        $order->refresh();
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);

        // Second delivery (duplicate event)
        $service->processSuccessfulCheckoutSession($sessionObj, 'evt_test_1001');

        // Emails should still only have been sent ONCE
        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(AdminOrderNotificationMail::class, 1);
    }
}
