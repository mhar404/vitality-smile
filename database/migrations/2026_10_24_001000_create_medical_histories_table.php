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
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $table->string('blood_type', 10)->nullable();
            
            // Critical medical alert flags
            $table->boolean('has_allergies')->default(false);
            $table->text('allergies')->nullable(); // e.g. Penicillin, Latex, Local Anesthetics
            
            $table->boolean('hypertension')->default(false);
            $table->boolean('diabetes')->default(false);
            $table->boolean('heart_disease')->default(false);
            $table->boolean('bleeding_disorders')->default(false);
            $table->boolean('asthma')->default(false);
            $table->boolean('kidney_disease')->default(false);
            $table->boolean('liver_disease')->default(false);
            $table->boolean('hepatitis')->default(false);
            $table->boolean('epilepsy')->default(false);
            $table->boolean('is_pregnant')->default(false);
            
            // Detailed lists & text
            $table->json('other_conditions')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('past_surgeries')->nullable();
            
            // Physician & Emergency Contact
            $table->string('physician_name')->nullable();
            $table->string('physician_contact')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
