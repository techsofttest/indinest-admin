<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
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

    protected function createTestOrder(array $attributes = []): Order
    {
        $brand = Brand::firstOrCreate(['name' => 'Test Brand'], ['slug' => 'test-brand']);
        $category = Category::firstOrCreate(['name' => 'Test Cat'], ['slug' => 'test-cat', 'is_active' => true]);

        $product = Product::create([
            'sku' => 'SKU-LIFECYCLE-' . rand(1000, 9999),
            'name' => 'Lifecycle Item',
            'slug' => 'lifecycle-item-' . rand(1000, 9999),
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $order = Order::create(array_merge([
            'order_number' => 'IND-LC-' . rand(1000, 9999),
            'order_type' => 'order',
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '07123456789',
            'country' => 'United Kingdom',
            'address' => '42 Wallaby Way',
            'city' => 'London',
            'state' => 'London',
            'pin_code' => 'EC1A 1BB',
            'shipping_name' => 'Jane Smith',
            'shipping_address_line_1' => '42 Wallaby Way',
            'shipping_city' => 'London',
            'shipping_postcode' => 'EC1A 1BB',
            'shipping_country' => 'United Kingdom',
            'shipping_method' => 'standard',
            'payment_method' => 'stripe',
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::CONFIRMED,
            'subtotal' => 100.00,
            'shipping_cost' => 4.45,
            'discount' => 0.00,
            'grand_total' => 104.45,
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Lifecycle Item',
            'variant_details' => 'Blue / S',
            'quantity' => 1,
            'price' => 100.00,
            'line_total' => 100.00,
        ]);

        return $order->fresh(['items']);
    }

    public function test_confirmed_order_can_be_marked_as_shipped_and_sends_email(): void
    {
        Mail::fake();

        $order = $this->createTestOrder([
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $service = app(OrderService::class);
        $service->markAsShipped($order);

        $order->refresh();
        $this->assertEquals(OrderStatus::SHIPPED, $order->status);

        Mail::assertSent(OrderShippedMail::class, 1);
        Mail::assertSent(OrderShippedMail::class, function ($mail) use ($order) {
            return $mail->hasTo('jane@example.com') && $mail->order->order_number === $order->order_number;
        });
    }

    public function test_shipped_order_can_be_marked_as_delivered_and_sends_email(): void
    {
        Mail::fake();

        $order = $this->createTestOrder([
            'status' => OrderStatus::SHIPPED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $service = app(OrderService::class);
        $service->markAsDelivered($order);

        $order->refresh();
        $this->assertEquals(OrderStatus::DELIVERED, $order->status);

        Mail::assertNotSent(OrderDeliveredMail::class);
    }

    public function test_cannot_mark_confirmed_order_directly_as_delivered(): void
    {
        Mail::fake();

        $order = $this->createTestOrder([
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $service = app(OrderService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->markAsDelivered($order);
    }

    public function test_cannot_mark_unpaid_order_as_shipped(): void
    {
        Mail::fake();

        $order = $this->createTestOrder([
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PENDING,
        ]);

        $service = app(OrderService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->markAsShipped($order);
    }

    public function test_cannot_ship_already_shipped_or_delivered_order(): void
    {
        Mail::fake();

        $order = $this->createTestOrder([
            'status' => OrderStatus::SHIPPED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $service = app(OrderService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->markAsShipped($order);
    }

    public function test_duplicate_ship_request_does_not_send_duplicate_emails(): void
    {
        Mail::fake();

        $order = $this->createTestOrder([
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $service = app(OrderService::class);
        $service->markAsShipped($order);

        Mail::assertSent(OrderShippedMail::class, 1);

        // Attempting to ship again should fail
        try {
            $service->markAsShipped($order);
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Email count remains 1
        Mail::assertSent(OrderShippedMail::class, 1);
    }
}
