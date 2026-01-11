<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Etudiant;
use App\Models\Professeur;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class LargeScaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Total: 7 Deps, 200 Formations, ~1200 Modules, ~12000 Students, ~130000 Inscriptions
     */
    public function run()
    {
        ini_set('memory_limit', '1024M');
        DB::disableQueryLog();

        $this->command->info('Starting Large Scale Seed...');
        $startTime = microtime(true);

        // 1. Departments (7)
        $deptIds = [];
        $depts = [];
        $deptNames = ['Informatique', 'Mathematiques', 'Physique', 'Biologie', 'Chimie', 'Geologie', 'Architecture'];

        foreach ($deptNames as $name) {
            $depts[] = ['nom' => $name, 'created_at' => now(), 'updated_at' => now()];
        }

        // Delete in correct order to respect Foreign Keys
        // 1. Children
        DB::table('examens')->delete();
        DB::table('inscriptions')->delete();

        // 2. Intermediates
        DB::table('modules')->delete();
        DB::table('professeurs')->delete();
        DB::table('etudiants')->delete();

        // 3. Parents
        DB::table('formations')->delete();
        DB::table('departements')->delete();
        DB::table('users')->delete();
        DB::table('lieu_examen')->delete();

        DB::table('departements')->insert($depts);

        // Get IDs
        $deptIds = DB::table('departements')->pluck('id')->all();

        // 2. Formations (200) ~ 28 per dept
        $formations = [];
        $formationIds = [];
        $now = now();

        for ($i = 0; $i < 200; $i++) {
            $deptId = $deptIds[$i % count($deptIds)];
            $formations[] = [
                'nom' => "Formation " . ($i + 1),
                'dept_id' => $deptId,
                'nb_modules' => 6, // Default or random
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        $chunks = array_chunk($formations, 100);
        foreach ($chunks as $chunk)
            DB::table('formations')->insert($chunk);

        // Get Formation IDs
        $formationIds = DB::table('formations')->pluck('id')->all();

        // 3. Modules (6-9 per formation)
        $modules = [];
        $moduleIds = [];

        foreach ($formationIds as $fId) {
            $count = rand(6, 9);
            for ($k = 0; $k < $count; $k++) {
                $modules[] = [
                    'nom' => "Module F{$fId}-{$k}",
                    'formation_id' => $fId,
                    'credits' => rand(3, 6), // Random credits
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }

        $chunks = array_chunk($modules, 200);
        foreach ($chunks as $chunk)
            DB::table('modules')->insert($chunk);

        // Get Module IDs grouped by formation for faster lookup
        // modulesByFormation[formation_id] = [module_ids]
        $modulesByFormation = [];
        $allModules = DB::table('modules')->select('id', 'formation_id')->get();
        foreach ($allModules as $m) {
            $modulesByFormation[$m->formation_id][] = $m->id;
        }

        // 4. Rooms (Enough for 13k students? Exam slots reuse rooms)
        // Need mix of capacities.
        // Let's create 50 rooms
        // 10 large (300-500), 20 medium (100-200), 20 small (50-80)
        $rooms = [];
        for ($i = 1; $i <= 50; $i++) {
            if ($i <= 10)
                $cap = rand(300, 500);
            elseif ($i <= 30)
                $cap = rand(100, 200);
            else
                $cap = rand(50, 80);

            $rooms[] = [
                'nom' => "Salle $i", // libelle -> nom
                'capacite' => $cap,
                'type' => ($cap > 100) ? 'Amphi' : 'Salle TD', // Add type
                'batiment' => 'Batiment Principal', // Add batiment
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        DB::table('lieu_examen')->insert($rooms);

        // 5. Professors (~500)
        // They need Users accounts
        $profs = [];
        $users = [];
        $password = Hash::make('password');

        for ($i = 1; $i <= 500; $i++) {
            $userId = $i; // Auto-increment simulation or assume empty DB
            // We usually insert User then Prof.
            // Batch create Users first? ID synchronization is tricky with batch.
            // Let's rely on auto-increment being predictable if we truncated.
            // Better: Insert User, Get ID. Slow.
            // Faster: Prepare Users with IDs? Postgres/MySQL handles auto-ids.
            // Let's just create User/Prof in loop but using Eloquent for simplicity on Auth?
            // No, too slow for 13k users.
            // We can batch insert Users but we won't know their IDs easily unless we query back.
            // But we truncated! So auto-increment starts at 1 (or we can force it).
            // Let's assume ID 1 is taken by Admin from DatabaseSeeder?
            // Let's just create array of data and insert.
        }

        // Let's do Professors via loop (500 is fast enough)
        $this->command->info('Creating Professors...');
        $profData = [];
        $usersData = [];
        for ($i = 0; $i < 500; $i++) {
            // We can batch insert users if we don't care about their exact IDs matching profs?
            // Actually prof has user_id. 
            // Let's create users via Factory-ish or just bulk
            // For 500 records, loop with User::create is fine.
        }

        // Batch approach for 500 profs
        // Insert 500 users
        // Get their IDs
        // Insert 500 profs linked to IDs
        // Create Admin and Doyen first
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@univ.edu',
                'email_verified_at' => $now,
                'password' => $password,
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Doyen User',
                'email' => 'doyen@univ.edu',
                'email_verified_at' => $now,
                'password' => $password,
                'role' => 'doyen', // Make sure 'doyen' is valid enum if we changed things? 
                // Schema has 'doyen'.
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Dept Head User',
                'email' => 'deptchef@univ.edu',
                'email_verified_at' => $now,
                'password' => $password,
                'role' => 'department_head',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);

        $baseEmail = 'prof' . uniqid();
        $userBatches = [];
        for ($i = 0; $i < 500; $i++) {
            $userBatches[] = [
                'name' => "Prof $i",
                'email' => "prof{$i}@univ.edu",
                'email_verified_at' => $now,
                'password' => $password,
                'role' => 'professor', // professeur -> professor
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        DB::table('users')->insert($userBatches);

        // Fetch valid user IDs for profs
        $profUserIds = DB::table('users')->where('role', 'professor')->pluck('id')->all();

        foreach ($profUserIds as $uid) {
            $profData[] = [
                'user_id' => $uid,
                'dept_id' => $deptIds[array_rand($deptIds)],
                'nom' => "Prof " . $uid, // Add nom
                'specialite' => "Specialite " . $uid, // Add specialite
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        DB::table('professeurs')->insert($profData);


        // 6. Students (~13,000)
        $this->command->info('Creating Students (13k)...');
        // Batch insert Users first
        $studentUserBatches = [];
        $batchSize = 1000;

        for ($i = 0; $i < 13000; $i++) {
            $studentUserBatches[] = [
                'name' => "Student $i",
                'email' => "s{$i}@etu.univ.edu",
                'email_verified_at' => $now,
                'password' => $password,
                'role' => 'student', // etudiant -> student
                'created_at' => $now,
                'updated_at' => $now
            ];

            if (count($studentUserBatches) >= $batchSize) {
                DB::table('users')->insert($studentUserBatches);
                $studentUserBatches = [];
                $this->command->info("Inserted users batch...");
            }
        }
        if (!empty($studentUserBatches))
            DB::table('users')->insert($studentUserBatches);

        // Fetch IDs
        // This query might be heavy. 
        // SELECT id FROM users WHERE role='etudiant'
        $studentUserIds = DB::table('users')->where('role', 'student')->pluck('id')->all();

        $studentData = [];
        $inscriptions = [];

        $count = 0;
        $totalStudents = count($studentUserIds);

        foreach ($studentUserIds as $index => $uid) {
            // Skew distribution: 50% of students go to first 5 formations (Huge classes)
            if ($index < $totalStudents * 0.5) {
                $fIndex = rand(0, 4);
            } else {
                $fIndex = rand(20, 199);
            }
            $fId = $formationIds[$fIndex];

            $studentData[] = [
                'user_id' => $uid,
                'nom' => "Nom$i",
                'prenom' => "Prenom$i",
                'promo' => '2025-2026',
                'formation_id' => $fId,
                'created_at' => $now,
                'updated_at' => $now
            ];

            // Inscriptions: All modules of this formation
            if (isset($modulesByFormation[$fId])) {
                foreach ($modulesByFormation[$fId] as $mId) {
                    $inscriptions[] = [
                        'etudiant_id' => null, // Need Etudiant ID not User ID. Wait.
                        // Etudiant table auto-increments ID usually.
                        // We need to insert students first to get their etudiant_id.
                    ];
                }
            }
            $count++;
        }

        // Insert Students
        $chunks = array_chunk($studentData, 1000);
        foreach ($chunks as $chunk)
            DB::table('etudiants')->insert($chunk); // IDs generated

        // Check map: UserID -> EtudiantID
        // We probably don't need this map if we just iterate etudiants table
        $this->command->info("Students inserted. Building Inscriptions...");

        // Stream etudiants to save memory
        // For each student, find formation, add modules.

        $etudiants = DB::table('etudiants')->select('id', 'formation_id')->orderBy('id')->cursor();

        $inscriptionsBatch = [];
        $totalInscriptions = 0;

        foreach ($etudiants as $etu) {
            if (isset($modulesByFormation[$etu->formation_id])) {
                foreach ($modulesByFormation[$etu->formation_id] as $mId) {
                    $inscriptionsBatch[] = [
                        'etudiant_id' => $etu->id,
                        'module_id' => $mId,
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];

                    if (count($inscriptionsBatch) >= 2000) {
                        DB::table('inscriptions')->insert($inscriptionsBatch);
                        $totalInscriptions += count($inscriptionsBatch);
                        $inscriptionsBatch = [];
                        echo ".";
                    }
                }
            }
        }
        if (!empty($inscriptionsBatch)) {
            DB::table('inscriptions')->insert($inscriptionsBatch);
            $totalInscriptions += count($inscriptionsBatch);
        }

        $this->command->info("\nDone! Total Inscriptions: $totalInscriptions");
        $this->command->info("Time: " . round(microtime(true) - $startTime, 2) . "s");
    }
}
