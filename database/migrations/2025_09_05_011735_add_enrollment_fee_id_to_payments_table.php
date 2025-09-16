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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('enrollment_fee_id')->nullable()->after('enrollment_id')
                ->constrained()->onDelete('cascade');
            
            // Index pour améliorer les performances
            $table->index('enrollment_fee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['enrollment_fee_id']);
            $table->dropIndex(['enrollment_fee_id']);
            $table->dropColumn('enrollment_fee_id');
        });
    }
};