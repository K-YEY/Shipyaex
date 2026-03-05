<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tableName = 'order'; // or 'orders'
echo "Indexes for table: $tableName\n";
try {
    $indexes = DB::select("SHOW INDEX FROM `$tableName` ");
    foreach ($indexes as $i) {
        echo $i->Key_name . " (" . $i->Column_name . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
