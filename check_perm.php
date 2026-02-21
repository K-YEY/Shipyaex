<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$permission = \Spatie\Permission\Models\Permission::where('name', 'ViewAny:Order')->first();
if ($permission) {
    echo "Permission ViewAny:Order exists!" . PHP_EOL;
    $roles = $permission->roles->pluck('name')->toArray();
    echo "Assigned to roles: " . implode(', ', $roles) . PHP_EOL;
} else {
    echo "Permission ViewAny:Order is MISSING!" . PHP_EOL;
}
