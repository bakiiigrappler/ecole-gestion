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
        Schema::create('level_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->onDelete('cascade');
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
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['level_id', 'academic_year_id']);
            $table->index(['fee_type', 'is_active']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_fees');
    }
};