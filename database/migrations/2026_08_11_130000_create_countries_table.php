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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 2)->unique();
            $table->string('checkout_type'); // payment or enquiry
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed countries
        $countries = [
            ['name' => 'United Kingdom', 'code' => 'GB', 'checkout_type' => 'payment'],
            ['name' => 'Ireland', 'code' => 'IE', 'checkout_type' => 'payment'],
            ['name' => 'Austria', 'code' => 'AT', 'checkout_type' => 'enquiry'],
            ['name' => 'Belgium', 'code' => 'BE', 'checkout_type' => 'enquiry'],
            ['name' => 'Bulgaria', 'code' => 'BG', 'checkout_type' => 'enquiry'],
            ['name' => 'Croatia', 'code' => 'HR', 'checkout_type' => 'enquiry'],
            ['name' => 'Cyprus', 'code' => 'CY', 'checkout_type' => 'enquiry'],
            ['name' => 'Czech Republic (Czechia)', 'code' => 'CZ', 'checkout_type' => 'enquiry'],
            ['name' => 'Denmark', 'code' => 'DK', 'checkout_type' => 'enquiry'],
            ['name' => 'Estonia', 'code' => 'EE', 'checkout_type' => 'enquiry'],
            ['name' => 'Finland', 'code' => 'FI', 'checkout_type' => 'enquiry'],
            ['name' => 'France', 'code' => 'FR', 'checkout_type' => 'enquiry'],
            ['name' => 'Germany', 'code' => 'DE', 'checkout_type' => 'enquiry'],
            ['name' => 'Greece', 'code' => 'GR', 'checkout_type' => 'enquiry'],
            ['name' => 'Hungary', 'code' => 'HU', 'checkout_type' => 'enquiry'],
            ['name' => 'Italy', 'code' => 'IT', 'checkout_type' => 'enquiry'],
            ['name' => 'Latvia', 'code' => 'LV', 'checkout_type' => 'enquiry'],
            ['name' => 'Lithuania', 'code' => 'LT', 'checkout_type' => 'enquiry'],
            ['name' => 'Luxembourg', 'code' => 'LU', 'checkout_type' => 'enquiry'],
            ['name' => 'Malta', 'code' => 'MT', 'checkout_type' => 'enquiry'],
            ['name' => 'Netherlands', 'code' => 'NL', 'checkout_type' => 'enquiry'],
            ['name' => 'Poland', 'code' => 'PL', 'checkout_type' => 'enquiry'],
            ['name' => 'Portugal', 'code' => 'PT', 'checkout_type' => 'enquiry'],
            ['name' => 'Romania', 'code' => 'RO', 'checkout_type' => 'enquiry'],
            ['name' => 'Slovakia', 'code' => 'SK', 'checkout_type' => 'enquiry'],
            ['name' => 'Slovenia', 'code' => 'SI', 'checkout_type' => 'enquiry'],
            ['name' => 'Spain', 'code' => 'ES', 'checkout_type' => 'enquiry'],
            ['name' => 'Sweden', 'code' => 'SE', 'checkout_type' => 'enquiry'],
        ];

        foreach ($countries as $c) {
            DB::table('countries')->insert(array_merge($c, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
