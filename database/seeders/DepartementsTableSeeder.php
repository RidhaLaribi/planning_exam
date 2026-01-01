<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departement;

class DepartementsTableSeeder extends Seeder
{
    public function run(): void
    {
        $depts = [
            'Informatique',
            'Mathématiques',
            'Physique',
            'Chimie',
            'Biologie',
            'Génie Civil',
            'Génie Mécanique',
        ];

        foreach ($depts as $dept) {
            Departement::create(['nom' => $dept]);
        }
    }
}
