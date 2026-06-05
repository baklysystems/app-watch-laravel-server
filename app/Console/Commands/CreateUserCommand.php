<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateUserCommand extends Command
{
    protected $signature = 'appswatch:create-user
                            {--name= : User display name}
                            {--email= : User email address}
                            {--password= : User password (min 8 chars)}
                            {--role=super_admin : Role: super_admin or user}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Create a new user account for the Appswatch dashboard';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $role = $this->option('role');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'role'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', Password::min(8)],
                'role' => ['required', 'in:super_admin,user'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->info('About to create user:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $name],
                    ['Email', $email],
                    ['Role', $role],
                ]
            );

            if (! $this->confirm('Proceed with user creation?')) {
                $this->info('Cancelled.');
                return self::SUCCESS;
            }
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        $this->info("User '{$email}' created successfully with role '{$role}'.");

        return self::SUCCESS;
    }
}