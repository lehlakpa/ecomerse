<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {username?}';

    protected $description = 'Create a store administrator with an interactively entered password';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively to enter the administrator password securely.');

            return self::FAILURE;
        }

        $data = [
            'username' => $this->argument('username') ?? $this->ask('Username'),
            'name' => $this->ask('Full name'),
            'password' => $this->secret('Password (at least 7 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'max:50', 'unique:users'],
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(7)],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create(['username' => $data['username'], 'name' => $data['name'], 'password' => $data['password'], 'role' => 'admin']);
        $this->info('Administrator created. Sign in with your username at /login.');

        return self::SUCCESS;
    }
}
