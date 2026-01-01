<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Examen;
use App\Models\Module;
use App\Models\Professeur;
use App\Models\LieuExamen;

class ExamensTableSeeder extends Seeder
{
    public function run(): void
    {
        $modules = Module::all();
        $profs = Professeur::all();
        $salles = LieuExamen::all();

        if ($modules->isEmpty() || $profs->isEmpty() || $salles->isEmpty()) {
            return;
        }

        // Create 30 exams
        for ($i = 0; $i < 30; $i++) {
            $module = $modules->random();
            // Try to find a prof from the same department as the module's formation
            $prof = $profs->where('dept_id', $module->formation->dept_id)->first() ?? $profs->random();
            $salle = $salles->random();

            Examen::create([
                'module_id' => $module->id,
                'prof_id' => $prof->id,
                'salle_id' => $salle->id,
                'date_heure' => now()->addDays(rand(1, 30))->setTime(rand(8, 16), 0),
                'duree_minutes' => [90, 120, 180][rand(0, 2)],
            ]);
        }
    }
}
