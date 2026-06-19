<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $f = \App\Models\Flight::first();
    echo "Initial status: " . $f->status . "\n";
    echo "--- Attempting invalid transition (Scheduled -> InFlight) ---\n";
    $f->transitionTo('in_flight');
} catch (\Exception $e) {
    echo "CAUGHT ERROR: " . $e->getMessage() . "\n";
}

try {
    echo "--- Attempting valid transitions (Scheduled -> CheckIn -> Boarding) ---\n";
    $f->transitionTo('check_in');
    echo "Status after transitionTo(check_in): " . $f->status . "\n";
    
    $f->transitionTo('boarding');
    echo "Status after transitionTo(boarding): " . $f->status . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Revert for testing purposes
$f->status = 'scheduled';
$f->save();
