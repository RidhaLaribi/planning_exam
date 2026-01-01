<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Etudiant;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EtudiantsTableSeeder extends Seeder
{
    public function run(): void
    {
        $formations = Formation::all();
        $count = 1;

        foreach ($formations as $formation) {
            // Create 6 students per formation
            for ($i = 0; $i < 6; $i++) {
                $name = 'Etudiant ' . $count;
                $prenom = 'Prenom ' . $count;
                
                $user = User::create([
                    'name' => $name . ' ' . $prenom,
                    'email' => 'etudiant' . $count . '@univ.edu',
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'email_verified_at' => now(),
                ]);

                Etudiant::create([
                    'nom' => $name,
                    'prenom' => $prenom,
                    'formation_id' => $formation->id,
                    'promo' => '2025-2026',
                    'user_id' => $user->id,
                ]);

                $count++;
            }
        }
    }
}
