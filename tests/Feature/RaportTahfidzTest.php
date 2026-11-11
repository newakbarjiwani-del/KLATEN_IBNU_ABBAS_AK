<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\RaportTahfidz;
use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RaportTahfidzTest extends TestCase
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

        Schema::create('akademik_rapot_tahfidzh', function (Blueprint $table) {
            $table->increments('urut');
            $table->string('NOCUST', 10)->nullable();
            $table->string('THN_AKA', 20)->nullable();
            $table->string('FILE_RAPOT', 250)->nullable();
            $table->string('UJIAN', 7)->nullable();
        });
    }

    public function test_data_page_lists_uploaded_report_files(): void
    {
        $this->seedMasters();
        RaportTahfidz::query()->create([
            'NOCUST' => '1402000001',
            'THN_AKA' => '2024/2025 - GANJIL',
            'FILE_RAPOT' => 'budi.pdf',
            'UJIAN' => 'MID',
        ]);
        RaportTahfidz::query()->create([
            'NOCUST' => '1301154344',
            'THN_AKA' => '2024/2025 - GANJIL',
            'FILE_RAPOT' => 'zahra.pdf',
            'UJIAN' => 'AKHIR',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/kesantrian/data-raport')
            ->assertOk()
            ->assertSee('Data Raport')
            ->assertSee('1301154344')
            ->assertSee('Ahmad')
            ->assertSee('zahra.pdf')
            ->assertSee('http://101.50.2.56/Klaten_IbnuAbbas/uploadrapottahfidzh/uploads/zahra.pdf', false)
            ->assertSee('SMP')
            ->assertSee('Kelompok')
            ->assertSee('2024/2025 - GANJIL')
            ->assertSee('row-group')
            ->assertSee('Akhir')
            ->assertSeeInOrder(['Ahmad', 'Budi'])
            ->assertDontSee('belum diisi.');

        $this->actingAs($admin)
            ->get('/kesantrian/data-raport?kelompok=A&thn_aka='.urlencode('2024/2025 - GANJIL'))
            ->assertOk()
            ->assertSee('Ahmad')
            ->assertDontSee('Budi');
    }

    public function test_pdf_upload_is_stored_against_the_tahfidz_table(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapottahfidzh/uploads/*' => Http::response('', 404),
            'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapottahfidzh/index.php' => Http::response('The file hafalan.pdf has been uploaded.'),
        ]);
        $this->seedMasters();

        $this->actingAs($this->admin())
            ->post('/kesantrian/upload-raport', [
                'thn_aka' => '2024/2025 - GANJIL',
                'nocust' => '1301154344',
                'ujian' => 'MID',
                'berkas' => UploadedFile::fake()->create('hafalan.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('kesantrian.menu', 'upload-raport'))
            ->assertSessionHas('status', 'Raport hafalan.pdf diunggah.');

        $this->assertDatabaseHas('akademik_rapot_tahfidzh', [
            'NOCUST' => '1301154344',
            'THN_AKA' => '2024/2025 - GANJIL',
            'FILE_RAPOT' => 'hafalan.pdf',
            'UJIAN' => 'MID',
        ]);
        Http::assertSent(function (Request $request): bool {
            $parts = collect($request->data())->keyBy('name');

            return $request->method() === 'POST'
                && $request->url() === 'http://101.50.2.56/Klaten_IbnuAbbas/uploadrapottahfidzh/index.php'
                && ($parts['nis']['contents'] ?? null) === '1301154344'
                && ($parts['ujian']['contents'] ?? null) === 'MID'
                && ($parts['file']['filename'] ?? null) === 'hafalan.pdf';
        });
    }

    public function test_nis_lookup_returns_the_student_name(): void
    {
        $this->seedMasters();

        $this->actingAs($this->admin())
            ->getJson('/kesantrian/upload-raport/santri?nis=1301154344')
            ->assertOk()
            ->assertJson(['nama' => 'Ahmad']);
    }

    public function test_nis_suggestions_follow_the_typed_prefix(): void
    {
        $this->seedMasters();

        $this->actingAs($this->admin())
            ->getJson('/kesantrian/santri?q=1301')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.nis', '1301154344')
            ->assertJsonPath('items.0.nama', 'Ahmad');
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
        Santri::query()->create([
            'NOCUST' => '1402000001',
            'NMCUST' => 'Budi',
            'DESC02' => '8B',
            'CODE02' => 'SMA',
            'DESC03' => 'B',
        ]);
    }

    private function admin(): CyberKey
    {
        return CyberKey::query()->create([
            'users' => 'admkes',
            'ket' => 'Admin Kesantrian',
            'kel' => 'admkes',
            'password' => 'pintu-kesantrian',
        ]);
    }
}
