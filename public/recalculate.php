<?php

use App\Models\CollectedShipper;
use App\Models\CollectedClient;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre>";
echo "Starting recalculation of CollectedShipper records...\n";
$shippers = CollectedShipper::all();
foreach ($shippers as $shipper) {
    echo "Recalculating Shipper Collection #{$shipper->id}... ";
    try {
        $shipper->recalculateAmounts();
        echo "Done.\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\nStarting recalculation of CollectedClient records...\n";
$clients = CollectedClient::all();
foreach ($clients as $client) {
    echo "Recalculating Client Collection #{$client->id}... ";
    try {
        $client->recalculateAmounts();
        echo "Done.\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\nAll recalculations completed.\n";
echo "</pre>";
