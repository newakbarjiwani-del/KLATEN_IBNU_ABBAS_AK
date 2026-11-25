<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('akses')) {
            Schema::create('akses', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('nama', 50);
            });
        }

        DB::table('akses')->insertOrIgnore([
            ['id' => 1, 'nama' => 'Kesantrian'],
            ['id' => 2, 'nama' => 'Tahfidz'],
            ['id' => 3, 'nama' => 'Raport Akademik'],
        ]);

        if (! Schema::hasColumn('cyber_key', 'unit')) {
            Schema::table('cyber_key', function (Blueprint $table) {
                $table->string('unit', 50)->nullable()->after('kel');
            });
        }

        if (! Schema::hasColumn('cyber_key', 'akses')) {
            Schema::table('cyber_key', function (Blueprint $table) {
                $table->string('akses', 30)->nullable()->after('unit');
            });
        }

        $nextUrut = (int) DB::table('cyber_key')->max('urut');

        foreach ($this->accounts() as $account) {
            $existing = DB::table('cyber_key')->where('users', $account['users'])->exists();

            if ($existing) {
                DB::table('cyber_key')->where('users', $account['users'])->update([
                    'unit' => $account['unit'],
                    'akses' => $account['akses'],
                ]);

                continue;
            }

            $nextUrut++;

            DB::table('cyber_key')->insert([
                'users' => $account['users'],
                'ket' => $account['ket'],
                'kel' => $account['kel'],
                'unit' => $account['unit'],
                'akses' => $account['akses'],
                'urut' => $nextUrut,
                'password' => Hash::make('ibnuabbas'),
                'kunci' => null,
                'fid' => null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Akun unit tetap ada. Nilai unit dan akses ikut terhapus bersama kolomnya.
     */
    public function down(): void
    {
        if (Schema::hasColumn('cyber_key', 'akses')) {
            Schema::table('cyber_key', function (Blueprint $table) {
                $table->dropColumn('akses');
            });
        }

        if (Schema::hasColumn('cyber_key', 'unit')) {
            Schema::table('cyber_key', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }

        Schema::dropIfExists('akses');
    }

    /**
     * @return array<int, array{users: string, ket: string, kel: string, unit: string, akses: string}>
     */
    private function accounts(): array
    {
        return [
            ['users' => 'admkuttab', 'ket' => 'Admin Kuttab', 'kel' => 'unit', 'unit' => 'KUTTAB', 'akses' => '1,2,3'],
            ['users' => 'admkmi', 'ket' => 'Admin KMI', 'kel' => 'unit', 'unit' => 'KMI', 'akses' => '1,2,3'],
            ['users' => 'admmahad', 'ket' => "Admin Ma'had Aly", 'kel' => 'unit', 'unit' => "MA'HAD ALY", 'akses' => '1,2,3'],
            ['users' => 'admsmptah', 'ket' => 'Admin Tahfidz SMP', 'kel' => 'admtah', 'unit' => 'SMP', 'akses' => '2'],
            ['users' => 'admsmpkes', 'ket' => 'Admin Kesantrian SMP', 'kel' => 'admkes', 'unit' => 'SMP', 'akses' => '1'],
            ['users' => 'admsmpaka', 'ket' => 'Admin Akademik SMP', 'kel' => 'admaka', 'unit' => 'SMP', 'akses' => '3'],
            ['users' => 'admsmatah', 'ket' => 'Admin Tahfidz SMA', 'kel' => 'admtah', 'unit' => 'SMA', 'akses' => '2'],
            ['users' => 'admsmakes', 'ket' => 'Admin Kesantrian SMA', 'kel' => 'admkes', 'unit' => 'SMA', 'akses' => '1'],
            ['users' => 'admsmaka', 'ket' => 'Admin Akademik SMA', 'kel' => 'admaka', 'unit' => 'SMA', 'akses' => '3'],
        ];
    }
};
