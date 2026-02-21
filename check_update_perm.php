<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$permission = \Spatie\Permission\Models\Permission::where('name', 'Update:Order')->first();
if ($permission) {
    echo "Permission Update:Order exists!" . PHP_EOL;
    $roles = $permission->roles->pluck('name')->toArray();
    echo "Assigned to roles: " . implode(', ', $roles) . PHP_EOL;
} else {
    echo "Permission Update:Order is MISSING!" . PHP_EOL;
}
