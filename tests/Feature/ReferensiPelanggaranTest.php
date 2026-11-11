<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\ReferensiPelanggaran;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferensiPelanggaranTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('cyber_key', function (Blueprint $table) {
            $table->string('users', 15)->primary();
            $table->string('kunci', 30)->nullable();
            $table->string('fid', 20)->nullable();
            $table->string('ket', 50)->nullable();
            $table->string('kel', 20)->nullable();
            $table->integer('urut')->nullable();
            $table->string('password')->nullable();
        });

        Schema::create('akademik_ref_langgar', function (Blueprint $table) {
            $table->increments('urut');
            $table->text('jenis_pelangaran')->nullable();
            $table->smallInteger('skor')->nullable();
            $table->string('keterangan', 20)->nullable();
        });
    }

    public function test_kesantrian_lists_violation_references(): void
    {
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Tidak sholat fardhu dengan sengaja',
            'skor' => 75,
            'keterangan' => 'SPT',
        ]);
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Datang di masjid setelah iqomah',
            'skor' => 5,
        ]);

        $response = $this->actingAs($this->kesantrianAdmin())
            ->get('/kesantrian/master-pelanggaran');

        $response
            ->assertOk()
            ->assertSee('Master Pelanggaran')
            ->assertSee('Nama pelanggaran')
            ->assertSee('Tidak sholat fardhu dengan sengaja')
            ->assertSee('75')
            ->assertSee('SPT')
            ->assertSee('class="col-no"', false)
            ->assertSee('Nomor urut berjalan dari yang terkecil.')
            ->assertSee('name="nama"', false)
            ->assertSee('id="add-pelanggaran"', false)
            ->assertSee('icon-moon', false)
            ->assertSee('page-loader', false)
            ->assertDontSee('belum diisi.');

        $html = $response->getContent();
        $this->assertTrue(
            strpos($html, 'Tidak sholat fardhu dengan sengaja') < strpos($html, 'Datang di masjid setelah iqomah')
        );
    }

    public function test_kesantrian_can_add_a_violation_reference(): void
    {
        $this->actingAs($this->kesantrianAdmin())
            ->post('/kesantrian/master-pelanggaran', [
                'nama' => 'Terlambat mengikuti kegiatan',
                'skor' => 5,
                'keterangan' => 'nasihat',
            ])
            ->assertRedirect(route('kesantrian.menu', 'master-pelanggaran'))
            ->assertSessionHas('status', 'Pelanggaran ditambahkan.');

        $this->assertDatabaseHas('akademik_ref_langgar', [
            'jenis_pelangaran' => 'Terlambat mengikuti kegiatan',
            'skor' => 5,
            'keterangan' => 'nasihat',
        ]);
    }

    public function test_duplicate_violation_name_is_rejected(): void
    {
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Tidak memiliki Al-Qur\'an',
            'skor' => 20,
        ]);

        $this->actingAs($this->kesantrianAdmin())
            ->from('/kesantrian/master-pelanggaran')
            ->post('/kesantrian/master-pelanggaran', [
                'nama' => 'Tidak memiliki Al-Qur\'an',
                'skor' => 20,
            ])
            ->assertRedirect('/kesantrian/master-pelanggaran')
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, ReferensiPelanggaran::query()->count());
    }

    public function test_other_systems_cannot_open_the_violation_master(): void
    {
        $akademik = CyberKey::query()->create([
            'users' => 'admaka',
            'ket' => 'Admin Akademik',
            'kel' => 'admaka',
            'password' => 'pintu-akademik',
        ]);

        $this->actingAs($akademik)
            ->get('/kesantrian/master-pelanggaran')
            ->assertRedirect(route('akademik.home'));
    }

    private function kesantrianAdmin(): CyberKey
    {
        return CyberKey::query()->create([
            'users' => 'admkes',
            'ket' => 'Admin Kesantrian',
            'kel' => 'admkes',
            'password' => 'pintu-kesantrian',
        ]);
    }
}
