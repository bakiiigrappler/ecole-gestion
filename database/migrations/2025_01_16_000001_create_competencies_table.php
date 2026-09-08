<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Histoire, géographie, citoyenneté"
            $table->string('code'); // Ex: "COMP_1", "COMP_2"
            $table->text('description')->nullable();
            $table->string('subject_area'); // Ex: "EDM & EAS", "Français", "Mathématiques"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('competencies');
    }
};
