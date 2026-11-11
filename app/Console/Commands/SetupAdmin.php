<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SetupAdmin extends Command
{
    protected $signature = 'rentrack:setup-admin';

    protected $description = 'Create the initial RentTrack admin account';

    public function handle(): int
    {
        if (Account::where('role', 'admin')->exists()) {
            $this->error('An admin account already exists.');
            return self::FAILURE;
        }

        $name = $this->ask('Admin full name');
        $username = $this->ask('Admin username');
        $email = strtolower(trim(
            $this->ask('Admin email address')
        ));

        $password = $this->secret('Admin password');
        $confirmation = $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'alpha_dash', 'min:3', 'max:30',
                Rule::unique('accounts', 'username'),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('accounts', 'email'),
            ],
            'password' => [
                'required',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }

        DB::transaction(function () use (
            $name, $username, $email, $password
        ) {
            $admin = Admin::create([
                'admin_name' => $name,
                'status' => 'Active',
            ]);

            Account::create([
                'admin_id' => $admin->admin_id,
                'tenant_id' => null,
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => 'admin',
                'profile_completed' => true,
            ]);
        });

        $this->info('RentTrack admin created successfully!');

        return self::SUCCESS;
    }
}
