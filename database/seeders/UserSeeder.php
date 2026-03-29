<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuário admin com credenciais conhecidas para testes
        User::firstOrCreate(
            ['email' => 'admin@media.test'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Usuários aleatórios para testes de favoritos etc.
        User::factory(4)->create();
    }
}
