<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Total Permissions: " . \Spatie\Permission\Models\Permission::count() . PHP_EOL;

$pascalPerms = \Spatie\Permission\Models\Permission::where('name', 'like', '%:%')->count();
echo "Pascal Case (:): " . $pascalPerms . PHP_EOL;

$snakePerms = \Spatie\Permission\Models\Permission::where('name', 'like', '%_%')->count();
echo "Snake Case (_): " . $snakePerms . PHP_EOL;
