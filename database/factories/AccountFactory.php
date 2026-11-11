<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'admin_id' => null,
            'tenant_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make('ValidPassword!123'),
            'role' => 'tenant',
            'profile_completed' => true,
        ];
    }

    public function forAdmin(Admin $admin): static
    {
        return $this->state(fn (): array => [
            'admin_id' => $admin->admin_id,
            'tenant_id' => null,
            'role' => 'admin',
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => [
            'admin_id' => null,
            'tenant_id' => $tenant->tenant_id,
            'role' => 'tenant',
        ]);
    }
}
