<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\RoleName;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('admin:create {email : Administrator email address} {--name= : Administrator name} {--password= : Password; omit to enter it securely}')]
#[Description('Create an active system administrator')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name');
        $password = $this->option('password');

        if (! is_string($password) || $password === '') {
            $password = $this->secret('Password');
        }

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
        $user->roles()->attach(Role::query()->where('name', RoleName::Administrator->value)->firstOrFail());

        $this->info("Administrator created: {$user->email}");

        return self::SUCCESS;
    }
}
