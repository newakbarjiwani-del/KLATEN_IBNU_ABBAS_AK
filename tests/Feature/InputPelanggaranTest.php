<?php

namespace Tests\Feature;

use App\Models\CatatanPelanggaran;
use App\Models\CyberKey;
use App\Models\ReferensiPelanggaran;
use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InputPelanggaranTest extends TestCase
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

        Schema::create('mst_thn_aka', function (Blueprint $table) {
            $table->increments('urut');
            $table->string('thn_aka', 30)->nullable();
        });

        Schema::create('akademik_ref_langgar', function (Blueprint $table) {
            $table->increments('urut');
            $table->text('jenis_pelangaran')->nullable();
            $table->smallInteger('skor')->nullable();
            $table->string('keterangan', 20)->nullable();
        });

        Schema::create('scctcust', function (Blueprint $table) {
            $table->increments('CUSTID');
            $table->string('NOCUST', 15)->nullable();
            $table->string('NMCUST', 70)->nullable();
        });

        Schema::create('akademik_pelanggaran', function (Blueprint $table) {
            $table->increments('urut');
            $table->string('NOCUST', 10)->nullable();
            $table->date('tgl_kejadian')->nullable();
            $table->smallInteger('skor_debet')->nullable();
            $table->smallInteger('skor_kredit')->nullable();
            $table->smallInteger('id_pelanggaran')->nullable();
            $table->smallInteger('id_remisi')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('thn_aka', 30)->nullable();
        });
    }

    public function test_input_page_lists_saved_records_and_master_options(): void
    {
        $this->seedMasters();
        CatatanPelanggaran::query()->create([
            'NOCUST' => '1301154344',
            'tgl_kejadian' => '2026-05-12',
            'skor_kredit' => 10,
            'id_pelanggaran' => 1,
            'keterangan' => 'terlambat iqomah',
            'thn_aka' => '2024/2025 - GANJIL',
        ]);

        $this->actingAs($this->kesantrianAdmin())
            ->get('/kesantrian/input-pelanggaran')
            ->assertOk()
            ->assertSee('Input Pelanggaran')
            ->assertSee('Masbuq shalat')
            ->assertSee('1301154344')
            ->assertSee('Ahmad')
            ->assertSee('terlambat iqomah')
            ->assertSee('name="jenis"', false)
            ->assertSee('NIS / Nama')
            ->assertSee('Ketik NIS atau nama santri...')
            ->assertDontSee('belum diisi.');
    }

    public function test_violation_score_is_taken_from_the_master(): void
    {
        $this->seedMasters();

        $admin = $this->kesantrianAdmin();

        $this->actingAs($admin)
            ->post('/kesantrian/input-pelanggaran', [
                'jenis' => 'pelanggaran',
                'thn_aka' => '2024/2025 - GANJIL',
                'tgl_kejadian' => '2026-10-28',
                'nocust' => '1301154344',
                'id_langgar' => 1,
                'skor' => 99,
                'keterangan' => 'masbuq',
            ])
            ->assertRedirect(route('kesantrian.menu', 'input-pelanggaran'))
            ->assertSessionHas('status', 'Pelanggaran disimpan.');

        $this->assertDatabaseHas('akademik_pelanggaran', [
            'NOCUST' => '1301154344',
            'skor_kredit' => 10,
            'skor_debet' => null,
            'id_pelanggaran' => 1,
            'id_remisi' => null,
            'keterangan' => 'masbuq',
        ]);

        $this->actingAs($admin)
            ->post('/kesantrian/input-pelanggaran', [
                'jenis' => 'pelanggaran',
                'thn_aka' => '2024/2025 - GANJIL',
                'tgl_kejadian' => '2026-10-29',
                'nocust' => 'Ahmad',
                'id_langgar' => 1,
                'keterangan' => 'lewat nama',
            ])
            ->assertRedirect(route('kesantrian.menu', 'input-pelanggaran'));

        $this->assertDatabaseHas('akademik_pelanggaran', [
            'NOCUST' => '1301154344',
            'keterangan' => 'lewat nama',
        ]);
    }

    public function test_remission_keeps_the_entered_score(): void
    {
        $this->seedMasters();

        $this->actingAs($this->kesantrianAdmin())
            ->post('/kesantrian/input-pelanggaran', [
                'jenis' => 'remisi',
                'thn_aka' => '2024/2025 - GANJIL',
                'tgl_kejadian' => '2026-10-28',
                'nocust' => '1301154344',
                'id_langgar' => 1,
                'skor' => 4,
                'keterangan' => 'keringanan',
            ])
            ->assertRedirect(route('kesantrian.menu', 'input-pelanggaran'))
            ->assertSessionHas('status', 'Remisi disimpan.');

        $this->assertDatabaseHas('akademik_pelanggaran', [
            'skor_debet' => 4,
            'skor_kredit' => null,
            'id_pelanggaran' => null,
            'id_remisi' => 1,
        ]);
    }

    public function test_nis_lookup_returns_the_student_name(): void
    {
        $this->seedMasters();
        $admin = $this->kesantrianAdmin();

        $this->actingAs($admin)
            ->getJson('/kesantrian/input-pelanggaran/santri?nis=1301154344')
            ->assertOk()
            ->assertJson(['nama' => 'Ahmad']);

        $this->actingAs($admin)
            ->getJson('/kesantrian/input-pelanggaran/santri?nis=000')
            ->assertNotFound();
    }

    private function seedMasters(): void
    {
        TahunAkademik::query()->create(['thn_aka' => '2024/2025 - GANJIL']);
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Masbuq shalat',
            'skor' => 10,
        ]);
        Santri::query()->create([
            'NOCUST' => '1301154344',
            'NMCUST' => 'Ahmad',
        ]);
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
