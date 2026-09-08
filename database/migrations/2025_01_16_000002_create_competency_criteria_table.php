<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('competency_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained()->onDelete('cascade');
            $table->string('code'); // Ex: "C1", "C2", "C3", "C4"
            $table->string('name'); // Ex: "Identifier les informations pertinentes"
            $table->text('description')->nullable();
            $table->integer('max_points')->default(0); // Points maximum pour ce critère
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('competency_criteria');
    }
};
