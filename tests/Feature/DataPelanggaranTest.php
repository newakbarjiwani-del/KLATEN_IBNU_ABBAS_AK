<?php

namespace Tests\Feature;

use App\Models\CatatanPelanggaran;
use App\Models\CyberKey;
use App\Models\ReferensiPelanggaran;
use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataPelanggaranTest extends TestCase
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
            $table->string('DESC02', 50)->nullable();
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

    public function test_data_pelanggaran_page_shows_summary_of_all_students(): void
    {
        $this->seedData();

        $this->actingAs($this->kesantrianAdmin())
            ->get('/kesantrian/data-pelanggaran')
            ->assertOk()
            ->assertSee('Data Pelanggaran Siswa')
            ->assertSee('Ahmad Alif')
            ->assertSee('1301154344')
            ->assertSee('IX 1')
            ->assertSee('Farel Raditya')
            ->assertSee('9999999999')
            ->assertSee('VII 2')
            ->assertDontSee('belum diisi.');
    }

    public function test_filter_by_academic_year_and_class_and_keyword(): void
    {
        $this->seedData();
        $admin = $this->kesantrianAdmin();

        // Filter by year
        $this->actingAs($admin)
            ->get('/kesantrian/data-pelanggaran?thn_aka=2024/2025 - GANJIL')
            ->assertOk()
            ->assertSee('Ahmad Alif')
            ->assertDontSee('Farel Raditya');

        // Filter by class
        $this->actingAs($admin)
            ->get('/kesantrian/data-pelanggaran?kelas=VII 2')
            ->assertOk()
            ->assertSee('Farel Raditya')
            ->assertDontSee('Ahmad Alif');

        // Filter by keyword
        $this->actingAs($admin)
            ->get('/kesantrian/data-pelanggaran?q=Farel')
            ->assertOk()
            ->assertSee('Farel Raditya')
            ->assertDontSee('Ahmad Alif');
    }

    public function test_detail_endpoint_returns_json_history_for_student(): void
    {
        $this->seedData();
        $admin = $this->kesantrianAdmin();

        $response = $this->actingAs($admin)
            ->getJson('/kesantrian/data-pelanggaran/detail?nis=1301154344&thn=2024/2025 - GANJIL');

        $response->assertOk()
            ->assertJson([
                'nis' => '1301154344',
                'nama' => 'Ahmad Alif',
                'total_pelanggaran' => 20,
                'total_remisi' => 5,
                'total_skor' => 15,
            ]);
    }

    private function seedData(): void
    {
        TahunAkademik::query()->create(['thn_aka' => '2024/2025 - GANJIL']);
        TahunAkademik::query()->create(['thn_aka' => '2023/2024 - GENAP']);

        ReferensiPelanggaran::query()->create(['jenis_pelangaran' => 'Terlambat Shalat', 'skor' => 10]);
        ReferensiPelanggaran::query()->create(['jenis_pelangaran' => 'Kerapian Asrama', 'skor' => 5]);

        Santri::query()->create(['NOCUST' => '1301154344', 'NMCUST' => 'Ahmad Alif', 'DESC02' => 'IX 1']);
        Santri::query()->create(['NOCUST' => '9999999999', 'NMCUST' => 'Farel Raditya', 'DESC02' => 'VII 2']);

        // Ahmad: 2 violations (10 + 10 = 20) and 1 remission (5) -> Total = 15
        CatatanPelanggaran::query()->create([
            'NOCUST' => '1301154344',
            'tgl_kejadian' => '2024-08-10',
            'skor_kredit' => 10,
            'id_pelanggaran' => 1,
            'thn_aka' => '2024/2025 - GANJIL',
        ]);
        CatatanPelanggaran::query()->create([
            'NOCUST' => '1301154344',
            'tgl_kejadian' => '2024-08-12',
            'skor_kredit' => 10,
            'id_pelanggaran' => 1,
            'thn_aka' => '2024/2025 - GANJIL',
        ]);
        CatatanPelanggaran::query()->create([
            'NOCUST' => '1301154344',
            'tgl_kejadian' => '2024-08-15',
            'skor_debet' => 5,
            'id_remisi' => 2,
            'thn_aka' => '2024/2025 - GANJIL',
        ]);

        // Farel in 2023/2024 - GENAP
        CatatanPelanggaran::query()->create([
            'NOCUST' => '9999999999',
            'tgl_kejadian' => '2024-02-05',
            'skor_kredit' => 10,
            'id_pelanggaran' => 1,
            'thn_aka' => '2023/2024 - GENAP',
        ]);
    }

    public function test_daftar_kelas_handles_incomplete_class_in_cache(): void
    {
        Santri::query()->create(['NOCUST' => '001', 'NMCUST' => 'Test', 'DESC02' => '7A']);

        $incompleteClass = unserialize('O:20:"SomeNonExistentClass":0:{}');
        Cache::put('scctcust.kelas', $incompleteClass, 60);

        $result = Santri::daftarKelas();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->contains('7A'));
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
