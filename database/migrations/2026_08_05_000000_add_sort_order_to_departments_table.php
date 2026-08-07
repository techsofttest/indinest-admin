<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('image');
        });

        // Set default sort_order based on existing order
        \App\Models\Department::query()
            ->orderBy('name')
            ->get()
            ->each(function ($department, $index) {
                $department->update(['sort_order' => $index + 1]);
            });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};