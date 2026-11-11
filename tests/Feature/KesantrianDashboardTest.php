<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KesantrianDashboardTest extends TestCase
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

    public function test_kesantrian_dashboard_lists_every_menu(): void
    {
        CyberKey::query()->create([
            'users' => 'admkes',
            'ket' => 'Admin Kesantrian',
            'kel' => 'admkes',
            'password' => 'pintu-kesantrian',
        ]);

        $this->post('/login', [
            'aplikasi' => 'admkes',
            'users' => 'admkes',
            'password' => 'pintu-kesantrian',
        ]);

        $this->get('/kesantrian')
            ->assertOk()
            ->assertSee('Master Tahun Akademik')
            ->assertSee('Master Pelanggaran')
            ->assertSee('Input Pelanggaran')
            ->assertSee('Data Pelanggaran')
            ->assertSee('Input Raport Kesantrian')
            ->assertSee('Data Raport Kesantrian')
            ->assertSee('Upload Raport')
            ->assertSee('Data Raport')
            ->assertSee('noindex, nofollow', false);

        $this->get('/kesantrian/data-pelanggaran')
            ->assertOk()
            ->assertSee('Data Pelanggaran')
            ->assertSee('Telusuri catatan pelanggaran santri yang sudah masuk.')
            ->assertDontSee('belum diisi.')
            ->assertSee('Filter Data Pelanggaran Siswa')
            ->assertSee('logo.png', false);

        $this->get('/kesantrian/input-raport-kesantrian')
            ->assertOk()
            ->assertSee('Import Raport Kesantrian')
            ->assertDontSee('belum diisi.');

        $this->get('/kesantrian/tidak-ada')->assertNotFound();
    }
}
