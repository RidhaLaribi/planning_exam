<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Professeur;
use App\Models\Departement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfesseursTableSeeder extends Seeder
{
    public function run(): void
    {
        $depts = Departement::all();
        $count = 1;

        foreach ($depts as $dept) {
            // Create 3 professors per department
            for ($i = 0; $i < 3; $i++) {
                $name = 'Professeur ' . $count;
                $user = User::create([
                    'name' => $name,
                    'email' => 'prof' . $count . '@univ.edu',
                    'password' => Hash::make('password'),
                    'role' => 'professor',
                    'email_verified_at' => now(),
                ]);

                Professeur::create([
                    'nom' => $name,
                    'dept_id' => $dept->id,
                    'specialite' => $dept->nom . ' Spécialité ' . ($i + 1),
                    'user_id' => $user->id,
                ]);

                $count++;
            }
        }
    }
}
