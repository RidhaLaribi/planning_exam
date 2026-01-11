<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conflicts', function (Blueprint $table) {
            $table->id();


            // Explicit FKs
            $table->unsignedBigInteger('exam_id');
            $table->foreign('exam_id')->references('id')->on('examens')->onDelete('cascade');

            $table->unsignedBigInteger('conflict_with_exam_id')->nullable();
            $table->foreign('conflict_with_exam_id')->references('id')->on('examens')->onDelete('cascade');
            $table->string('type'); // student_overlap, professor_overlap, room_capacity, rule_violation
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflicts');
    }
};
