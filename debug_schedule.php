<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Exams Count: " . App\Models\Examen::count() . "\n";
echo "Exams with Prof: " . App\Models\Examen::whereNotNull('prof_id')->count() . "\n";

$jobStatus = Illuminate\Support\Facades\Cache::get('scheduler_job_manual-init');
echo "Job Status: " . json_encode($jobStatus) . "\n";

$lastError = Illuminate\Support\Facades\Cache::get('scheduler_error'); // If I had one?
