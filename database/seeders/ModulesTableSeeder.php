<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Formation;

class ModulesTableSeeder extends Seeder
{
    public function run(): void
    {
        $formations = Formation::all();
        $count = 1;

        foreach ($formations as $formation) {
            // Create 3 modules per formation
            for ($i = 1; $i <= 3; $i++) {
                Module::create([
                    'nom' => 'Module ' . $count . ' - ' . $formation->nom,
                    'credits' => rand(2, 6),
                    'formation_id' => $formation->id,
                    // pre_req_id left null for simplicity or can be randomised slightly
                ]);
                $count++;
            }
        }
    }
}
