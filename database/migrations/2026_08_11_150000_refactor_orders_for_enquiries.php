<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add order_type column to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type')->default('order')->after('order_number');
        });

        // 2. Migrate existing enquiry records if enquiries table exists
        if (Schema::hasTable('enquiries')) {
            $enquiries = DB::table('enquiries')->get();

            foreach ($enquiries as $enquiry) {
                $nameParts = explode(' ', trim($enquiry->customer_name), 2);
                $firstName = $nameParts[0] ?? 'Guest';
                $lastName = $nameParts[1] ?? '';

                // Insert into orders
                $orderId = DB::table('orders')->insertGetId([
                    'order_number' => $enquiry->enquiry_number,
                    'order_type' => 'enquiry',
                    'customer_id' => $enquiry->customer_id,
                    'customer_name' => $enquiry->customer_name,
                    'customer_email' => $enquiry->customer_email,
                    'customer_phone' => $enquiry->customer_phone,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $enquiry->customer_email,
                    'phone' => $enquiry->customer_phone,
                    'country' => $enquiry->country,
                    'address' => $enquiry->address,
                    'apartment' => $enquiry->apartment,
                    'city' => $enquiry->city,
                    'state' => $enquiry->state ?? 'N/A',
                    'pin_code' => $enquiry->pin_code,
                    'shipping_method' => 'standard',
                    'payment_method' => 'enquiry',
                    'payment_status' => 'not_required',
                    'status' => 'pending',
                    'subtotal' => $enquiry->subtotal,
                    'discount' => $enquiry->discount,
                    'grand_total' => $enquiry->grand_total,
                    'notes' => $enquiry->notes,
                    'created_at' => $enquiry->created_at,
                    'updated_at' => $enquiry->updated_at,
                ]);

                // Decode and insert items
                $items = json_decode($enquiry->items, true);
                if (is_array($items)) {
                    foreach ($items as $item) {
                        DB::table('order_items')->insert([
                            'order_id' => $orderId,
                            'product_id' => $item['product_id'] ?? null,
                            'variant_id' => $item['variant_id'] ?? null,
                            'product_name' => $item['product_name'] ?? 'Product',
                            'variant_details' => $item['variant_details'] ?? null,
                            'quantity' => $item['quantity'] ?? 1,
                            'price' => $item['price'] ?? 0.00,
                            'line_total' => $item['line_total'] ?? 0.00,
                            'created_at' => $enquiry->created_at,
                            'updated_at' => $enquiry->updated_at,
                        ]);
                    }
                }
            }

            // 3. Drop enquiries table
            Schema::dropIfExists('enquiries');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_type');
        });
    }
};
