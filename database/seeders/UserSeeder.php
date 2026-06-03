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
        // Hapus akun lama (jika ada)
        \App\Models\User::whereIn('email', [
            'admin@sikn.local',
            'petugas@puskesmas.go.id',
            'marwa@puskesmas.go.id'
        ])->delete();

        // Akun Admin Baru
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@sikn.online'],
            [
                'name'     => 'Admin SiKN',
                'password' => \Illuminate\Support\Facades\Hash::make('SiKNAdmin1717'),
                'role'     => 'admin',
            ]
        );

        // Akun Petugas Baru
        \App\Models\User::updateOrCreate(
            ['email' => 'tujiemsudiarjo@gmail.com'],
            [
                'name'     => 'Tujiem Sudiarjo',
                'password' => \Illuminate\Support\Facades\Hash::make('tujiem123'),
                'role'     => 'petugas',
            ]
        );
    }
}
