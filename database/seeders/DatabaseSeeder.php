<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Unguard supaya created_at/updated_at bisa diisi tanggal historis.
        Model::unguarded(function () {
            $this->call([
                UserSeeder::class,
                SopSeeder::class,
                SopAcknowledgmentSeeder::class,
                SopReportSeeder::class,
            ]);
        });
    }
}