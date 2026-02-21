<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = Schema::getColumnListing('order');
foreach ($columns as $column) {
    echo $column . PHP_EOL;
}
