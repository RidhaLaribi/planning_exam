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
        Schema::create('exam_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departements')->onDelete('cascade'); // Nullable for Global Doyen validation
            $table->enum('status', ['draft', 'validated_chef', 'validated_doyen', 'rejected'])->default('draft');
            $table->foreignId('validated_by')->constrained('users');
            $table->text('comments')->nullable();
            $table->timestamps();

            // Ensure one validation record per department (or one global)
            // Actually, we might want history? For now, let's keep it simple: current state.
            // $table->unique(['department_id']); // Optional, depends on logic
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_validations');
    }
};
