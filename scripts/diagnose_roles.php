<?php

use App\Models\Auth\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = Role::all();
echo "Role Count: " . $roles->count() . "\n";

foreach ($roles as $r) {
    echo "ID: {$r->id} | Name: {$r->name}\n";
}

if ($roles->isEmpty()) {
    echo "WARNING: NO ROLES FOUND. Site creation will fail.\n";
}
