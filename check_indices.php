<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$indices = DB::select('SHOW INDEX FROM `order`');
foreach ($indices as $i) {
    echo $i->Key_name . ' => ' . $i->Column_name . PHP_EOL;
}
