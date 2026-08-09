<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('occasions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Seed basic occasions
        $occasions = ['Wedding', 'Casual', 'Festival', 'Party'];
        foreach ($occasions as $occasion) {
            \Illuminate\Support\Facades\DB::table('occasions')->insert([
                'name' => $occasion,
                'slug' => \Illuminate\Support\Str::slug($occasion),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occasions');
    }
};
