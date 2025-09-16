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
        // Supprimer l'ancienne table payments si elle existe
        Schema::dropIfExists('payments');
        
        // Supprimer l'ancienne table online_payments si elle existe
        Schema::dropIfExists('online_payments');

        // Créer la nouvelle table payments unifiée
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('enrollment_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('parents')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5);
            $table->enum('payment_type', [
                'enrollment', 're_enrollment', 'tuition', 
                'transport', 'canteen', 'uniform', 'other'
            ]);
            $table->enum('payment_method', [
                'moov_money', 'airtel_money', 'card', 
                'bank_transfer', 'cash', 'check'
            ]);
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', [
                'pending', 'processing', 'completed', 
                'failed', 'cancelled', 'refunded', 'partially_refunded'
            ])->default('pending');
            $table->string('payer_name');
            $table->string('payer_phone', 20);
            $table->string('payer_email')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_number')->unique()->nullable();
            $table->timestamps();

            // Index pour les performances
            $table->index(['status', 'created_at']);
            $table->index(['payment_method', 'created_at']);
            $table->index(['payment_type', 'created_at']);
            $table->index(['enrollment_id', 'status']);
            $table->index(['student_id', 'created_at']);
            $table->index(['parent_id', 'created_at']);
        });

        // Créer la table des remboursements
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('refund_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 'cancelled'
            ])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index pour les performances
            $table->index(['payment_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payments');
    }
};
