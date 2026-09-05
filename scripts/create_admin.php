<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$role = App\Models\Role::firstOrCreate(['name' => 'admin']);

$user = App\Models\User::firstOrCreate(
    ['email' => 'admin@police.local'],
    [
        'name' => 'Admin Principal',
        'password' => 'Admin123!',
        'is_active' => true,
    ]
);

$user->roles()->syncWithoutDetaching([$role->id]);

echo "ADMIN_READY: {$user->email} | {$user->name} | active=" . ($user->is_active ? '1' : '0') . PHP_EOL;
