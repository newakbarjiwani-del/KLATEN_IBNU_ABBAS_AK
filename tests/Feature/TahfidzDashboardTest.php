<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TahfidzDashboardTest extends TestCase
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

        Schema::create('scctcust', function (Blueprint $table) {
            $table->increments('CUSTID');
            $table->string('NOCUST', 15)->nullable();
            $table->string('NMCUST', 70)->nullable();
        });

        Schema::create('akademik_setoran', function (Blueprint $table) {
            $table->increments('urut');
            $table->date('tgl')->nullable();
            $table->string('juzzdihapal', 100)->nullable();
            $table->string('juzzposisi', 20)->nullable();
            $table->string('juzztotal', 20)->nullable();
            $table->string('NOCUST', 10)->nullable();
            $table->string('ziadah', 20)->nullable();
            $table->string('periode', 10)->nullable();
            $table->string('StatusLulus', 15)->nullable();
            $table->string('StatusPrestasi', 15)->nullable();
        });

        Schema::create('akademik_upload_tahfidz', function (Blueprint $table) {
            $table->increments('urut');
            $table->date('tgl')->nullable();
            $table->string('periode', 10)->nullable();
            $table->string('NOCUST', 15)->nullable();
            $table->string('juzzdihapal', 100)->nullable();
            $table->string('juzzposisi', 20)->nullable();
            $table->string('juzztotal', 20)->nullable();
            $table->string('ziadah', 20)->nullable();
            $table->string('murojaah', 50)->nullable();
            $table->string('tuhfah', 50)->nullable();
            $table->string('jazari', 50)->nullable();
            $table->string('statusujian', 30)->nullable();
            $table->string('prestasi', 50)->nullable();
            $table->string('jumlahpertemuan', 20)->nullable();
            $table->string('totalkehadiran', 20)->nullable();
        });

        Schema::create('akademik_murajaah', function (Blueprint $table) {
            $table->increments('urut');
            $table->date('tgl')->nullable();
            $table->string('juzz', 50)->nullable();
            $table->string('totjuzz', 50)->nullable();
            $table->string('NOCUST', 10)->nullable();
            $table->string('periode', 10)->nullable();
            $table->string('StatusLulus', 15)->nullable();
            $table->string('StatusPrestasi', 15)->nullable();
        });

        Schema::create('oldakademik_setoran', function (Blueprint $table) {
            $table->increments('urut');
            $table->date('tgl')->nullable();
            $table->string('surat', 3)->nullable();
            $table->string('ayat', 10)->nullable();
            $table->string('nilai', 100)->nullable();
            $table->string('NOCUST', 10)->nullable();
            $table->string('thn_aka', 20)->nullable();
            $table->string('ayat_akhir', 4)->nullable();
            $table->integer('halaman')->nullable();
            $table->integer('lembar')->nullable();
            $table->string('periode', 6)->nullable();
            $table->integer('lulus')->nullable();
            $table->integer('juzz')->nullable();
        });
    }

    public function test_guest_cannot_open_the_tahfidz_dashboard(): void
    {
        $this->get('/tahfidz')->assertRedirect(route('login'));
    }

    public function test_dashboard_lists_setoran_menus_and_their_records(): void
    {
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert([
            ['NOCUST' => '1301154344', 'NMCUST' => 'Ahmad'],
            ['NOCUST' => '1402000001', 'NMCUST' => 'Budi'],
        ]);
        DB::table('akademik_upload_tahfidz')->insert([
            'tgl' => '2024-10-01',
            'juzzdihapal' => '30',
            'juzzposisi' => '5',
            'juzztotal' => '5',
            'NOCUST' => '1301154344',
            'ziadah' => '0.5',
            'murojaah' => '1-2',
            'tuhfah' => 'lancar',
            'jazari' => 'dasar',
            'statusujian' => 'lulus',
            'prestasi' => '5 juz',
            'jumlahpertemuan' => '8',
            'totalkehadiran' => '7',
            'periode' => '202410',
        ]);
        DB::table('akademik_upload_tahfidz')->insert([
            'tgl' => '2024-11-02',
            'NOCUST' => '1402000001',
            'periode' => '202411',
            'ziadah' => '1',
            'murojaah' => '3',
        ]);
        DB::table('oldakademik_setoran')->insert([
            'tgl' => '2019-01-15',
            'surat' => '002',
            'ayat' => '1',
            'NOCUST' => '1402000001',
            'thn_aka' => '2018/2019 - GANJIL',
            'juzz' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/tahfidz')
            ->assertOk()
            ->assertSee('Dashboard Tahfidz')
            ->assertSee('Data Tahfidz')
            ->assertSee('Upload Tahfidz')
            ->assertSee('Laporan Capaian')
            ->assertSee('Setoran (OLD)')
            ->assertSee('noindex, nofollow', false);

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?periode=202410')
            ->assertOk()
            ->assertSee('Juz di hapal')
            ->assertSee('Murojaah')
            ->assertSee('Tuhfah')
            ->assertSee('Ahmad')
            ->assertSee('0.5')
            ->assertSee('lancar')
            ->assertDontSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?q=Ahmad')
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertDontSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?q=1402000001')
            ->assertOk()
            ->assertSee('Budi')
            ->assertDontSee('Ahmad');

        $this->actingAs($admin)
            ->get('/tahfidz/tahfidz')
            ->assertRedirect('/tahfidz/data-setoran');

        $this->actingAs($admin)
            ->get('/tahfidz/upload-tahfidz')
            ->assertOk()
            ->assertSee('Upload Tahfidz');

        $this->actingAs($admin)
            ->get('/tahfidz/setoran')
            ->assertRedirect('/tahfidz/upload-tahfidz');

        $this->actingAs($admin)
            ->get('/tahfidz/murajaah')
            ->assertRedirect('/tahfidz/upload-tahfidz');

        $this->actingAs($admin)
            ->get('/tahfidz/setoran-lama')
            ->assertOk()
            ->assertSee('Surat')
            ->assertSee('2018/2019 - GANJIL')
            ->assertSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/tidak-ada')
            ->assertNotFound();
    }

    public function test_tahfidz_file_can_be_opened_and_saved(): void
    {
        Storage::fake('local');
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert([
            'NOCUST' => '1301154344',
            'NMCUST' => 'Ahmad',
        ]);
        $file = UploadedFile::fake()->createWithContent(
            'tahfidz.csv',
            "NIS,Juz di hapal,Juz posisi,Juz total,Ziadah,Murojaah,Tuhfah,Jazari,Status ujian,Prestasi,Jumlah pertemuan,Total kehadiran\n1301154344,30,5,5,0.5,1-2,lancar,dasar,lulus,baik,8,7\n999,1,1,1,0,1,lancar,dasar,lulus,baik,1,1\n",
        );

        $this->actingAs($admin)
            ->post('/tahfidz/upload-tahfidz/buka', [
                'periode' => '202410',
                'berkas' => $file,
            ])
            ->assertRedirect('/tahfidz/upload-tahfidz')
            ->assertSessionHas('status', '2 baris siap diperiksa.');

        $this->actingAs($admin)
            ->get('/tahfidz/upload-tahfidz')
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertSee('NIS tidak ditemukan');

        $this->actingAs($admin)
            ->post('/tahfidz/upload-tahfidz/simpan', ['periode' => '202410'])
            ->assertRedirect('/tahfidz/upload-tahfidz')
            ->assertSessionHas('status', '1 baris disimpan untuk periode 202410.');

        $this->assertDatabaseHas('akademik_upload_tahfidz', [
            'NOCUST' => '1301154344',
            'periode' => '202410',
            'juzzdihapal' => '30',
            'murojaah' => '1-2',
            'tuhfah' => 'lancar',
            'statusujian' => 'lulus',
            'jumlahpertemuan' => '8',
            'totalkehadiran' => '7',
        ]);
        $this->assertDatabaseMissing('akademik_upload_tahfidz', [
            'NOCUST' => '999',
        ]);
    }

    public function test_template_and_filtered_export_are_excel_files(): void
    {
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert(['NOCUST' => '1301154344', 'NMCUST' => 'Ahmad']);
        DB::table('akademik_upload_tahfidz')->insert([
            'tgl' => '2024-10-01',
            'juzzdihapal' => '30',
            'NOCUST' => '1301154344',
            'periode' => '202410',
        ]);
        DB::table('akademik_upload_tahfidz')->insert([
            'tgl' => '2024-11-01',
            'NOCUST' => '1301154344',
            'periode' => '202411',
        ]);

        $template = $this->actingAs($admin)->get('/tahfidz/upload-tahfidz/template');
        $template->assertOk();
        $template->assertDownload('template-tahfidz.xlsx');
        $this->assertStringContainsString('Juz di hapal', $this->sheetText($template));

        $export = $this->actingAs($admin)->get('/tahfidz/data-setoran/ekspor?periode=202410');
        $export->assertOk();
        $export->assertDownload('data-tahfidz.xlsx');
        $sheet = $this->sheetText($export);
        $this->assertStringContainsString('Ahmad', $sheet);
        $this->assertStringContainsString('202410', $sheet);
        $this->assertStringNotContainsString('202411', $sheet);
    }

    public function test_capaian_report_shows_one_or_more_months_and_prints(): void
    {
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert([
            ['NOCUST' => '1301154344', 'NMCUST' => 'Ahmad'],
            ['NOCUST' => '1402000001', 'NMCUST' => 'Budi'],
        ]);
        DB::table('akademik_upload_tahfidz')->insert([
            ['tgl' => '2024-10-01', 'NOCUST' => '1301154344', 'periode' => '202410', 'murojaah' => '1-2', 'tuhfah' => 'lancar'],
            ['tgl' => '2024-11-01', 'NOCUST' => '1402000001', 'periode' => '202411', 'murojaah' => '3', 'tuhfah' => 'baik'],
        ]);

        $this->actingAs($admin)
            ->get('/tahfidz/laporan-capaian')
            ->assertOk()
            ->assertSee('Pilih satu bulan atau beberapa bulan sekaligus.');

        $this->actingAs($admin)
            ->get('/tahfidz/laporan-capaian?bulan[]=202410')
            ->assertOk()
            ->assertSee('Oktober 2024')
            ->assertSee('Ahmad')
            ->assertSee('1-2')
            ->assertDontSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/laporan-capaian?bulan[]=202410&bulan[]=202411')
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/laporan-capaian/cetak?bulan[]=202410')
            ->assertOk()
            ->assertSee('LAPORAN CAPAIAN TAHFIDZ', false)
            ->assertSee('Cetak / simpan PDF', false)
            ->assertSee('Ahmad', false)
            ->assertDontSee('Budi', false);

        $this->actingAs($admin)
            ->get('/tahfidz/laporan-capaian/cetak')
            ->assertRedirect('/tahfidz/laporan-capaian');
    }

    public function test_other_systems_cannot_open_tahfidz_menus(): void
    {
        $kesantrian = $this->admin('admkes', 'Admin Kesantrian');

        $this->actingAs($kesantrian)
            ->get('/tahfidz/data-setoran')
            ->assertRedirect(route('kesantrian.home'));
    }

    public function test_setoran_page_can_be_typed_and_rows_group_by_column(): void
    {
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert([
            ['NOCUST' => '1301154344', 'NMCUST' => 'Ahmad'],
            ['NOCUST' => '1402000001', 'NMCUST' => 'Budi'],
        ]);

        foreach (range(1, 31) as $index) {
            DB::table('akademik_upload_tahfidz')->insert([
                'tgl' => '2024-10-01',
                'NOCUST' => $index % 2 === 0 ? '1402000001' : '1301154344',
                'periode' => '202410',
            ]);
        }

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran')
            ->assertOk()
            ->assertSee('name="page"', false)
            ->assertSee('Buka')
            ->assertSee('Ke halaman')
            ->assertSee('Belum dikelompokkan')
            ->assertSee('Kelompokkan menurut Nama');

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?grup=NMCUST')
            ->assertOk()
            ->assertSee('Dikelompokkan menurut')
            ->assertSee('Aktif')
            ->assertSee('Lepas pengelompokan')
            ->assertSee('row-group', false)
            ->assertSee('Ahmad')
            ->assertSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?grup=hapus')
            ->assertOk()
            ->assertDontSee('Lepas pengelompokan');
    }

    private function sheetText(TestResponse $response): string
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        return is_string($xml) ? $xml : '';
    }

    private function admin(string $kel, string $name): CyberKey
    {
        return CyberKey::query()->create([
            'users' => $kel,
            'ket' => $name,
            'kel' => $kel,
            'password' => 'pintu-'.$kel,
        ]);
    }
}
