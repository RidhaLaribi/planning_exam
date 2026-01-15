<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Professeur;
use App\Models\Departement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TempUserSeeder extends Seeder
{
    public function run()
    {
        // Ensure at least one department exists
        $dept = Departement::first();
        if (!$dept) {
            $dept = Departement::create(['nom' => 'Informatique']);
        }

        // Create Dept Head User
        $user = User::firstOrCreate(
            ['email' => 'depthead@univ.edu'],
            [
                'name' => 'Department Head',
                'password' => Hash::make('password'),
                'role' => 'department_head'
            ]
        );

        // Link to Professor (as Chef Dept logic usually implies being a prof)
        Professeur::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nom' => 'Chef Info',
                'dept_id' => $dept->id,
                'specialite' => 'Informatique',
                'user_id' => $user->id
            ]
        );

        $this->command->info('Dept Head created: depthead@univ.edu / password');

        // Ensure Doyen user
        User::firstOrCreate(
            ['email' => 'doyen1@univ.edu'],
            [
                'name' => 'Doyen User',
                'password' => Hash::make('password'),
                'role' => 'doyen'
            ]
        );
        $this->command->info('Doyen created: doyen1@univ.edu / password');
    }
}