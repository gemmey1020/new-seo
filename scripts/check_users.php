<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = User::all();

if ($users->isEmpty()) {
    echo "No users found. Creating default admin...\n";
    $password = 'password'; // Default
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ]);
    echo "Created User:\n";
    echo "Email: {$user->email}\n";
    echo "Password: {$password}\n";
} else {
    echo "Existing Users:\n";
    foreach ($users as $user) {
        echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
    }
    echo "If you need a password reset, please request it.\n";
}
