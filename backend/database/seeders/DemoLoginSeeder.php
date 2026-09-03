<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoLoginSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);

        // Create organization
        $org = Organization::create([
            'name'     => 'Tavro Demo Restaurant',
            'type'     => 'Restaurant',
            'currency' => 'NGN',
            'tax_percentage'              => 7.5,
            'service_charge_percentage'   => 5.0,
            'timezone'    => 'Africa/Lagos',
        ]);

        // Create branch (set organization_id explicitly — no auth user in seeder context)
        $branch = Branch::create([
            'organization_id' => $org->id,
            'name'            => 'Main Branch',
            'address'         => '12 Admiralty Way, Lekki Phase 1, Lagos',
            'phone'           => '+2348012345678',
            'timezone'        => 'Africa/Lagos',
        ]);

        // Create owner user (upsert — safe to re-run)
        $user = User::firstOrCreate(
            ['email' => 'admin@tavro.ng'],
            [
                'name'            => 'Admin User',
                'first_name'      => 'Admin',
                'last_name'       => 'User',
                'password'        => Hash::make('password'),
                'phone'           => '+2348012345678',
                'status'          => 'active',
                'email_verified_at' => now(),
                'organization_id' => $org->id,
            ]
        );

        // Assign owner role
        $user->assignRole($ownerRole);

        // Link user to branch
        $branch->users()->attach($user->id);

        $this->command->info('Demo login created: admin@tavro.ng / password');
    }
}
