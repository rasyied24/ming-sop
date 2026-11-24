<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Layout memanggil @vite, dan tes tidak membutuhkan hasil build aset.
        $this->withoutVite();
    }

    /**
     * Pengaman: RefreshDatabase MENGHAPUS seluruh tabel. Dijalankan sebelum trait itu bekerja,
     * dan menolak semua koneksi selain database khusus tes.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'pgsql' || $database !== 'ming_sop_testing') {
            throw new RuntimeException(
                "Tes dihentikan demi keamanan data: koneksi '{$connection}' ke database '{$database}'. "
                ."Tes hanya boleh berjalan di pgsql 'ming_sop_testing'. Periksa phpunit.xml."
            );
        }
    }
}