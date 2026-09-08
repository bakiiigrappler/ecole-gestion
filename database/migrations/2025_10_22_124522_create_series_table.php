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
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // S, A1, A2, B, C, D, E, F1, F2, F3, F4, G1, G2, G3
            $table->string('name'); // Nom complet de la série
            $table->text('description')->nullable(); // Description de la série
            $table->string('level'); // 2nde, 1ère, Terminale
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
