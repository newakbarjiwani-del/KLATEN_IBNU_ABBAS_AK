<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\RaportAkademik;
use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AkademikRaportTest extends TestCase
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
            $table->string('thn_aka', 20)->nullable();
        });

        Schema::create('scctcust', function (Blueprint $table) {
            $table->increments('CUSTID');
            $table->string('NOCUST', 15)->nullable();
            $table->string('NMCUST', 70)->nullable();
            $table->string('DESC02', 50)->nullable();
            $table->string('CODE02', 30)->nullable();
            $table->string('DESC03', 50)->nullable();
        });

        Schema::create('akademik_rapot', function (Blueprint $table) {
            $table->increments('urut');
            $table->string('NOCUST', 10)->nullable();
            $table->string('THN_AKA', 20)->nullable();
            $table->string('FILE_RAPOT', 250)->nullable();
            $table->string('UJIAN', 7)->nullable();
        });
    }

    public function test_dashboard_lists_upload_and_data_raport(): void
    {
        $this->actingAs($this->admin())
            ->get('/akademik')
            ->assertOk()
            ->assertSee('Raport Akademik')
            ->assertSee('Upload Raport')
            ->assertSee('Data Raport');
    }

    public function test_pdf_upload_is_stored_against_akademik_rapot(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/uploads/*' => Http::response('', 404),
            'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/index.php' => Http::response('The file nilai.pdf has been uploaded.'),
        ]);
        $this->seedMasters();

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/akademik/upload-raport', [
                'thn_aka' => '2024/2025 - GANJIL',
                'nocust' => '1301154344',
                'kelas' => 'SMA|||8B|||B',
                'ujian' => 'AKHIR',
                'berkas' => UploadedFile::fake()->create('nilai.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('kelas');

        $this->actingAs($admin)
            ->post('/akademik/upload-raport', [
                'thn_aka' => '2024/2025 - GANJIL',
                'nocust' => '1301154344',
                'kelas' => 'SMP|||7A|||A',
                'ujian' => 'AKHIR',
                'berkas' => UploadedFile::fake()->create('nilai.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('akademik.menu', 'upload-raport'))
            ->assertSessionHas('status', 'Raport nilai.pdf diunggah.');

        $this->assertDatabaseHas('akademik_rapot', [
            'NOCUST' => '1301154344',
            'THN_AKA' => '2024/2025 - GANJIL',
            'FILE_RAPOT' => 'nilai.pdf',
            'UJIAN' => 'AKHIR',
        ]);
        Http::assertSent(function (Request $request): bool {
            $parts = collect($request->data())->keyBy('name');

            return $request->method() === 'POST'
                && $request->url() === 'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/index.php'
                && ($parts['nis']['contents'] ?? null) === '1301154344'
                && ($parts['tahun_aka']['contents'] ?? null) === '2024/2025 - GANJIL'
                && ($parts['ujian']['contents'] ?? null) === 'AKHIR'
                && ($parts['file']['filename'] ?? null) === 'nilai.pdf';
        });

        $this->actingAs($admin)
            ->get('/akademik/data-raport/1/berkas')
            ->assertRedirect('http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/uploads/nilai.pdf');
    }

    public function test_upload_stays_empty_when_the_report_server_is_unreachable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/*' => Http::failedConnection(),
        ]);
        $this->seedMasters();

        $this->actingAs($this->admin())
            ->from('/akademik/upload-raport')
            ->post('/akademik/upload-raport', [
                'thn_aka' => '2024/2025 - GANJIL',
                'nocust' => '1301154344',
                'kelas' => 'SMP|||7A|||A',
                'ujian' => 'AKHIR',
                'berkas' => UploadedFile::fake()->create('nilai.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect('/akademik/upload-raport')
            ->assertSessionHasErrors('berkas');

        $this->assertDatabaseCount('akademik_rapot', 0);
    }

    public function test_data_page_lists_report_files_with_class_and_group(): void
    {
        $this->seedMasters();
        RaportAkademik::query()->create([
            'NOCUST' => '1301154344',
            'THN_AKA' => '2024/2025 - GANJIL',
            'FILE_RAPOT' => 'ahmad.pdf',
            'UJIAN' => 'AKHIR',
        ]);

        Santri::query()->create([
            'NOCUST' => '1402000001',
            'NMCUST' => 'Budi',
            'DESC02' => '8B',
            'CODE02' => 'SMA',
            'DESC03' => 'B',
        ]);
        RaportAkademik::query()->create([
            'NOCUST' => '1402000001',
            'THN_AKA' => '2024/2025 - GANJIL',
            'FILE_RAPOT' => 'budi.pdf',
            'UJIAN' => 'MID',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/akademik/data-raport')
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertSee('7A')
            ->assertSee('SMP')
            ->assertSee('SMP · 7A · A', false)
            ->assertSee('ahmad.pdf')
            ->assertSee('http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/uploads/ahmad.pdf', false)
            ->assertSee('2024/2025 - GANJIL');

        $this->actingAs($admin)
            ->get('/akademik/data-raport?kelas='.urlencode('SMP|||7A|||A'))
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertDontSee('Budi');
    }

    public function test_other_systems_cannot_open_akademik_raport(): void
    {
        $tahfidz = CyberKey::query()->create([
            'users' => 'admtah',
            'ket' => 'Admin Tahfidz',
            'kel' => 'admtah',
            'password' => 'pintu-tahfidz',
        ]);

        $this->actingAs($tahfidz)
            ->get('/akademik/data-raport')
            ->assertRedirect(route('tahfidz.home'));
    }

    private function seedMasters(): void
    {
        TahunAkademik::query()->create(['thn_aka' => '2024/2025 - GANJIL']);
        Santri::query()->create([
            'NOCUST' => '1301154344',
            'NMCUST' => 'Ahmad',
            'DESC02' => '7A',
            'CODE02' => 'SMP',
            'DESC03' => 'A',
        ]);
    }

    private function admin(): CyberKey
    {
        return CyberKey::query()->create([
            'users' => 'admaka',
            'ket' => 'Admin Akademik',
            'kel' => 'admaka',
            'password' => 'pintu-akademik',
        ]);
    }
}
