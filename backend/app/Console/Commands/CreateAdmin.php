<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Provision a dev-company admin account for the admin panel.
 *
 * Usage:
 *   php artisan admin:create "Ada Lovelace" ada@tavro.dev
 *   php artisan admin:create --password= "Ada Lovelace" ada@tavro.dev   (prompt)
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {name : The admin display name}
        {email : The admin email (lower-cased)}
        {--password= : Prompt if omitted}';

    protected $description = 'Create (or reactivate) a dev-company admin account';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $email = strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email: {$email}");

            return self::INVALID;
        }

        $password = $this->option('password');
        if ($password === null || $password === '') {
            $password = (string) $this->secret('Password');
            if ($password === '') {
                $this->error('A password is required.');

                return self::INVALID;
            }
        }
        if (strlen($password) < 12) {
            $this->warn('Password is shorter than 12 characters — consider a stronger one.');
        }

        $admin = AdminUser::firstOrNew(['email' => $email]);

        $admin->name = $name;
        $admin->password = Hash::make($password);
        $admin->is_active = true;
        $admin->save();

        $verb = $admin->wasRecentlyCreated ? 'Created' : 'Reactivated';
        $this->info("{$verb} admin: {$name} <{$email}> (id {$admin->id})");

        $this->line('');
        $this->comment('Admin panel path: /'.trim(config('security.admin_path'), '/'));

        return self::SUCCESS;
    }
}
