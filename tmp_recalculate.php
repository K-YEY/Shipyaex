<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Recalculating Collected Clients...\n";
foreach (\App\Models\CollectedClient::all() as $c) {
    try {
        $c->recalculateAmounts();
        echo "Collected Client #$c->id updated.\n";
    } catch (\Exception $e) {
        echo "Error updating Collected Client #$c->id: " . $e->getMessage() . "\n";
    }
}

echo "\nRecalculating Collected Shippers...\n";
foreach (\App\Models\CollectedShipper::all() as $s) {
    try {
        $s->recalculateAmounts();
        echo "Collected Shipper #$s->id updated.\n";
    } catch (\Exception $e) {
        echo "Error updating Collected Shipper #$s->id: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
