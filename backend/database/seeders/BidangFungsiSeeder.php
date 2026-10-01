<?php

namespace Database\Seeders;

use App\Models\BidangFungsi;
use Illuminate\Database\Seeder;

class BidangFungsiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bidangFungsi = [
            ['INTELKAM', 'Intelijen dan Keamanan'],
            ['RESKRIM', 'Reserse Kriminal'],
            ['LANTAS', 'Lalu Lintas'],
            ['SAMAPTA', 'Samapta'],
            ['BINMAS', 'Pembinaan Masyarakat'],
            ['SDM', 'Sumber Daya Manusia'],
            ['LOGISTIK', 'Logistik'],
            ['HUMAS', 'Hubungan Masyarakat'],
            ['PROPAM', 'Profesi dan Pengamanan'],
            ['TIK', 'Teknologi Informasi dan Komunikasi'],
        ];

        foreach ($bidangFungsi as [$kode, $nama]) {
            BidangFungsi::query()->updateOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'is_active' => true],
            );
        }
    }
}
