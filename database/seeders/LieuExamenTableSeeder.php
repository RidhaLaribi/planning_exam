<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LieuExamen;

class LieuExamenTableSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Amphi', 'Salle TD'];
        
        for ($i = 1; $i <= 5; $i++) {
            LieuExamen::create([
                'nom' => 'Amphi ' . $i,
                'capacite' => rand(100, 200),
                'type' => 'Amphi',
                'batiment' => 'Batiment A'
            ]);
        }

        for ($i = 101; $i <= 110; $i++) {
            LieuExamen::create([
                'nom' => 'Salle ' . $i,
                'capacite' => rand(30, 50),
                'type' => 'Salle TD',
                'batiment' => 'Batiment B'
            ]);
        }
    }
}
