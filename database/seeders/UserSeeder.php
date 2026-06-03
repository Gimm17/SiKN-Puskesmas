<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@sikn.local'],
            [
                'name'     => 'Admin SiKN',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'petugas@puskesmas.go.id'],
            [
                'name'     => 'Petugas',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role'     => 'petugas',
            ]
        );

        // Design-spec sample users
        \App\Models\User::updateOrCreate(
            ['email' => 'marwa@puskesmas.go.id'],
            [
                'name'     => 'Marwa T. Materru',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role'     => 'petugas',
            ]
        );
    }
}
