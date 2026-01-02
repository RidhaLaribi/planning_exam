<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@univ.edu',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Doyen User
        User::create([
            'name' => 'Doyen User',
            'email' => 'doyen@univ.edu',
            'password' => Hash::make('password'),
            'role' => 'doyen',
        ]);

        $this->call([
            DepartementsTableSeeder::class,
            LieuExamenTableSeeder::class,
            FormationsTableSeeder::class,
            ProfesseursTableSeeder::class, // Creates Users too
            ModulesTableSeeder::class, // Depends on Formations
            EtudiantsTableSeeder::class, // Creates Users too
            ExamensTableSeeder::class,
        ]);

        // Populate Inscriptions (students -> modules)
        // Taking all students and assigning them to random modules of their formation
        $etudiants = \App\Models\Etudiant::with('formation.modules')->get();
        foreach ($etudiants as $etudiant) {
            $modules = $etudiant->formation->modules; // Modules of their formation
            if ($modules->isNotEmpty()) {
                // Attach random subset of modules
                $etudiant->modules()->attach(
                    $modules->random(min(rand(1, 3), $modules->count()))->pluck('id')->toArray(),
                    ['note' => rand(0, 20)] // Random grade
                );
            }
        }
    }
}
