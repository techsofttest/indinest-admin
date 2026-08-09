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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Seed basic colors
        $colors = ['Red', 'Blue', 'Green', 'Ivory', 'Gold', 'Black', 'White'];
        foreach ($colors as $color) {
            \Illuminate\Support\Facades\DB::table('colors')->insert([
                'name' => $color,
                'slug' => \Illuminate\Support\Str::slug($color),
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
        Schema::dropIfExists('colors');
    }
};
