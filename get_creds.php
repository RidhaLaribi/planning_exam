<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Inscription::has('module.examens')->first()->etudiant->user->email ?? 'None';
$prof = App\Models\Examen::whereNotNull('prof_id')->first()->prof->user->email ?? 'None';

echo "\nCREDENTIALS_START\n";
echo "Student: " . $student . "\n";
echo "Professor: " . $prof . "\n";
echo "CREDENTIALS_END\n";
