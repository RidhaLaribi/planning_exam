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
        Schema::create('room_usage_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('lieu_examen')->onDelete('cascade');
            $table->date('date');
            $table->integer('total_minutes')->default(0);
            $table->decimal('occupancy_rate', 5, 2)->default(0); // Percentage
            $table->timestamps();

            $table->unique(['room_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_usage_stats');
    }
};
