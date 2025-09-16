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
        Schema::create('enrollment_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('level_fee_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('class_fee_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->enum('fee_type', [
                'tuition', 'registration', 'uniform', 'transport', 
                'meal', 'books', 'activities', 'other'
            ])->default('tuition');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['monthly', 'quarterly', 'yearly', 'one_time'])->default('monthly');
            $table->date('due_date')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['enrollment_id', 'academic_year_id']);
            $table->index(['level_fee_id', 'is_paid']);
            $table->index(['class_fee_id', 'is_paid']);
            $table->index(['fee_type', 'is_paid']);
            $table->index(['due_date', 'is_paid']);
            $table->index('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_fees');
    }
};