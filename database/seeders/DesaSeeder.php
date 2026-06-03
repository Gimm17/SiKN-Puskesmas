<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DesaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $desa = [
            ['urutan' => 1,  'nama' => 'MAPANE'],
            ['urutan' => 2,  'nama' => 'KASIGUNCU'],
            ['urutan' => 3,  'nama' => 'TABALU'],
            ['urutan' => 4,  'nama' => 'LANTOJAYA'],
            ['urutan' => 5,  'nama' => 'TOINI'],
            ['urutan' => 6,  'nama' => 'BETANIA'],
            ['urutan' => 7,  'nama' => 'BEGA'],
            ['urutan' => 8,  'nama' => 'MASAMBA'],
            ['urutan' => 9,  'nama' => 'SAATU'],
            ['urutan' => 10, 'nama' => 'PINEDAPA'],
        ];

        foreach ($desa as $d) {
            \App\Models\Desa::updateOrCreate(
                ['nama' => $d['nama']],
                ['urutan' => $d['urutan']]
            );
        }
    }
}
