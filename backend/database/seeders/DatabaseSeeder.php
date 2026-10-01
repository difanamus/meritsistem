<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PangkatSeeder::class,
            BidangFungsiSeeder::class,
            JenisKualifikasiSeeder::class,
            JenisPenugasanSeeder::class,
            UnitOrganisasiSeeder::class,
            UserScopeSeeder::class,
            PersonelSeeder::class,
            KualifikasiPersonelSeeder::class,
            RiwayatJabatanSeeder::class,
            MeritProfileSeeder::class,
        ]);
    }
}
