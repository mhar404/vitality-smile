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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "Oral Prophylaxis (₱800-₱1,000)" or booking key
            $table->string('title'); // e.g. "Oral Prophylaxis (Dental Cleaning)"
            $table->string('category')->default('general'); // general, restorative, cosmetic, surgery, prosthodontics, orthodontics, preventive
            $table->decimal('base_price', 10, 2)->default(0.00);
            $table->decimal('max_price', 10, 2)->nullable();
            $table->string('price_display')->nullable(); // e.g. "₱800 - ₱1,000" or custom display override
            $table->string('duration')->default('30 - 45 mins');
            $table->string('icon')->default('🦷');
            $table->text('description')->nullable();
            $table->json('highlights')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
