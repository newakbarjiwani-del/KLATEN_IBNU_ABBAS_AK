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
        DB::table('akademik_setoran')->insert([
            'tgl' => '2024-10-01',
            'juzzdihapal' => '30',
            'juzzposisi' => '5',
            'juzztotal' => '5',
            'NOCUST' => '1301154344',
            'ziadah' => '0.5',
            'periode' => '202410',
            'StatusLulus' => 'lulus',
            'StatusPrestasi' => '5 juz',
        ]);
        DB::table('akademik_setoran')->insert([
            'tgl' => '2024-10-02',
            'NOCUST' => '1402000001',
            'periode' => '202411',
            'ziadah' => '1',
        ]);
        DB::table('akademik_murajaah')->insert([
            'tgl' => '2024-09-01',
            'juzz' => '1-2',
            'totjuzz' => '2',
            'NOCUST' => '1301154344',
            'periode' => '202409',
            'StatusLulus' => 'lulus',
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
            ->assertSee('Data Setoran dan Tahfidz')
            ->assertSee('Murajaah')
            ->assertSee('Setoran (OLD)')
            ->assertSee('noindex, nofollow', false);

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?periode=202410&mode=setoran')
            ->assertOk()
            ->assertSee('Juzz Di Hapal')
            ->assertSee('Ahmad')
            ->assertSee('0.5')
            ->assertDontSee('Budi');

        $this->actingAs($admin)
            ->get('/tahfidz/data-setoran?mode=murajaah&periode=202409')
            ->assertOk()
            ->assertSee('1-2')
            ->assertSee('Ahmad')
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
            ->get('/tahfidz/setoran')
            ->assertOk()
            ->assertSee('Import Setoran Santri');

        $this->actingAs($admin)
            ->get('/tahfidz/murajaah')
            ->assertOk()
            ->assertSee('Import Murajaah Santri');

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

    public function test_murajaah_file_can_be_opened_and_saved(): void
    {
        Storage::fake('local');
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert([
            'NOCUST' => '1301154344',
            'NMCUST' => 'Ahmad',
        ]);
        $file = UploadedFile::fake()->createWithContent(
            'murajaah.csv',
            "NIS,Total Juzz,Juzz,Lulus,Prestasi\n1301154344,2,1-2,lulus,baik\n999,1,1,lulus,baik\n",
        );

        $this->actingAs($admin)
            ->post('/tahfidz/murajaah/buka', [
                'periode' => '202410',
                'berkas' => $file,
            ])
            ->assertRedirect('/tahfidz/murajaah')
            ->assertSessionHas('status', '2 baris siap diperiksa.');

        $this->actingAs($admin)
            ->get('/tahfidz/murajaah')
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertSee('NIS tidak ditemukan');

        $this->actingAs($admin)
            ->post('/tahfidz/murajaah/simpan', ['periode' => '202410'])
            ->assertRedirect('/tahfidz/murajaah')
            ->assertSessionHas('status', '1 baris disimpan untuk periode 202410.');

        $this->assertDatabaseHas('akademik_murajaah', [
            'NOCUST' => '1301154344',
            'periode' => '202410',
            'juzz' => '1-2',
            'totjuzz' => '2',
        ]);
        $this->assertDatabaseMissing('akademik_murajaah', [
            'NOCUST' => '999',
        ]);
    }

    public function test_template_and_filtered_export_are_excel_files(): void
    {
        $admin = $this->admin('admtah', 'Admin Tahfidz');
        DB::table('scctcust')->insert(['NOCUST' => '1301154344', 'NMCUST' => 'Ahmad']);
        DB::table('akademik_setoran')->insert([
            'tgl' => '2024-10-01',
            'juzzdihapal' => '30',
            'NOCUST' => '1301154344',
            'periode' => '202410',
        ]);
        DB::table('akademik_setoran')->insert([
            'tgl' => '2024-11-01',
            'NOCUST' => '1301154344',
            'periode' => '202411',
        ]);

        $template = $this->actingAs($admin)->get('/tahfidz/murajaah/template');
        $template->assertOk();
        $template->assertDownload('template-murajaah.xlsx');
        $this->assertStringContainsString('Total Juzz', $this->sheetText($template));

        $export = $this->actingAs($admin)->get('/tahfidz/data-setoran/ekspor?mode=setoran&periode=202410');
        $export->assertOk();
        $export->assertDownload('setoran-bacaan.xlsx');
        $sheet = $this->sheetText($export);
        $this->assertStringContainsString('Ahmad', $sheet);
        $this->assertStringContainsString('202410', $sheet);
        $this->assertStringNotContainsString('202411', $sheet);
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
            DB::table('akademik_setoran')->insert([
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
