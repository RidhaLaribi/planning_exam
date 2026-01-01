<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lieu_examen', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->integer('capacite');
            $table->string('type'); // e.g. "Amphi", "Salle TD"
            $table->string('batiment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lieu_examen');
    }
};
