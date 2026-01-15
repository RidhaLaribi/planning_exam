<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Department Head Check ===\n";

$deptHead = App\Models\User::where('role', 'department_head')->first();
if (!$deptHead) {
    echo "No department_head user found!\n";
    exit(1);
}

echo "User: {$deptHead->email}\n";

$prof = App\Models\Professeur::where('user_id', $deptHead->id)->first();
if ($prof) {
    echo "Already has Professeur record (dept_id: {$prof->dept_id})\n";
} else {
    echo "Creating Professeur record...\n";

    // Get first department
    $dept = App\Models\Departement::first();
    if (!$dept) {
        echo "No departments found!\n";
        exit(1);
    }

    App\Models\Professeur::create([
        'user_id' => $deptHead->id,
        'dept_id' => $dept->id,
        'nom' => 'Department Head',
        'specialite' => 'Administration'
    ]);

    echo "Created Professeur record with dept_id: {$dept->id}\n";
}

echo "Done!\n";
