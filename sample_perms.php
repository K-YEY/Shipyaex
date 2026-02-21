<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$perms = \Spatie\Permission\Models\Permission::take(20)->pluck('name')->toArray();
foreach ($perms as $p) {
    echo "'$p'" . PHP_EOL;
}
