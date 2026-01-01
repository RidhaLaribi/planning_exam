<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Formation;
use App\Models\Departement;

class FormationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $depts = Departement::all();
        $levels = ['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2'];

        // Creates ~3 formations per department -> ~21 formations
        foreach ($depts as $dept) {
            foreach (array_slice($levels, 0, 3) as $level) {
                 Formation::create([
                    'nom' => $level . ' ' . $dept->nom,
                    'dept_id' => $dept->id,
                    'nb_modules' => rand(5, 8)
                 ]);
            }
        }
    }
}
