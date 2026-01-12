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
        Schema::create('unscheduled_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('nom');
            $table->foreignId('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unscheduled_exams');
    }
};
