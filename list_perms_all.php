<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$perms = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
sort($perms);
foreach ($perms as $p) {
    echo $p . PHP_EOL;
}
