<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('veggitable:seed-demo', function () {
    $this->info('Seeding database with demo users...');
    $this->call(DatabaseSeeder::class);
    $this->info('Database seeding completed successfully.');
})->purpose('Seed demo users and multi-tenant test data');

Artisan::command('veggitable:list-users', function () {
    $users = User::all(['id', 'name', 'email', 'is_super_admin', 'tenant_id', 'status']);
    if ($users->isEmpty()) {
        $this->warn('No users found in database! Running seed...');
        $this->call(DatabaseSeeder::class);
        $users = User::all(['id', 'name', 'email', 'is_super_admin', 'tenant_id', 'status']);
    }

    $rows = $users->map(fn ($u) => [
        'ID' => $u->id,
        'Name' => $u->name,
        'Email' => $u->email,
        'Role' => $u->is_super_admin ? 'Platform Super Admin' : ($u->is_tenant_owner ? 'Tenant Owner' : 'Employee'),
        'Tenant' => $u->tenant?->name ?? 'Platform (No Tenant)',
        'Status' => $u->status,
    ])->toArray();

    $this->table(['ID', 'Name', 'Email', 'Role', 'Tenant', 'Status'], $rows);
})->purpose('List all registered users and their credentials');
