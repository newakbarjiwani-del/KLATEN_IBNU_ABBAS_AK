<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\RaportKesantrian;
use App\Models\Santri;
use App\Models\TahunAkademik;
use App\Services\RaportKesantrianSpreadsheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RaportKesantrianTest extends TestCase
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
            $table->string('DESC03', 50)->nullable();
        });

        Schema::create('akademik_raport_kesantrian', function (Blueprint $table) {
            $table->increments('urut');
            $table->string('NOCUST', 10)->nullable();
            $table->date('tgl')->nullable();
            $table->string('periode', 25)->nullable();
            $table->string('Catatan', 250)->nullable();
            $table->string('IbadahA', 5)->nullable();
            $table->string('IbadahB', 5)->nullable();
            $table->string('IbadahC', 5)->nullable();
            $table->string('IbadahD', 5)->nullable();
            $table->string('IbadahE', 5)->nullable();
            $table->string('AdabA', 5)->nullable();
            $table->string('AdabB', 5)->nullable();
            $table->string('AdabC', 5)->nullable();
            $table->string('AdabD', 5)->nullable();
            $table->string('AdabE', 5)->nullable();
            $table->string('KepribadianA', 5)->nullable();
            $table->string('KepribadianB', 5)->nullable();
            $table->string('KepribadianC', 5)->nullable();
            $table->string('KepribadianD', 5)->nullable();
            $table->string('KepribadianE', 5)->nullable();
            $table->string('PengembanganA', 255)->nullable();
            $table->string('PengembanganB', 255)->nullable();
            $table->string('PengembanganC', 255)->nullable();
            $table->string('Disiplin', 5)->nullable();
            $table->string('Kerapian', 5)->nullable();
            $table->string('Leadership', 5)->nullable();
            $table->string('Sosial', 5)->nullable();
        });
    }

    protected function signInAsKesantrian(): CyberKey
    {
        $user = CyberKey::create([
            'users' => 'admkesantrian',
            'kel' => 'admkes',
            'ket' => 'Admin Kesantrian',
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_can_view_input_raport_page(): void
    {
        $this->signInAsKesantrian();

        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);

        $response = $this->get(route('kesantrian.menu', 'input-raport-kesantrian'));

        $response->assertOk();
        $response->assertSee('Import Raport Kesantrian');
        $response->assertSee('2024/2025 - GANJIL');
        $response->assertSee('Menghormati');
        $response->assertSee('Kebersihan Diri');
        $response->assertSee('Literasi Buku');
        $response->assertSee('Wali Hujroh');
        $response->assertDontSee('Adab 1');
        $response->assertDontSee('Pribadi 1');
    }

    public function test_can_download_templates(): void
    {
        $this->signInAsKesantrian();

        // CSV template
        $responseCsv = $this->get(route('kesantrian.input-raport-kesantrian.template', ['format' => 'csv']));
        $responseCsv->assertOk();
        $responseCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Sholat Wajib', $responseCsv->getContent());
        $this->assertStringContainsString('Menghormati', $responseCsv->getContent());
        $this->assertStringContainsString('Wali Hujroh', $responseCsv->getContent());

        // XLSX template
        $responseXlsx = $this->get(route('kesantrian.input-raport-kesantrian.template', ['format' => 'xlsx']));
        $responseXlsx->assertOk();
        $this->assertStringContainsString('application/', $responseXlsx->headers->get('Content-Type') ?? '');
    }

    public function test_can_preview_csv_and_store_raport(): void
    {
        $this->signInAsKesantrian();

        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        Santri::create(['NOCUST' => '999999999', 'NMCUST' => 'Burhan Testing']);

        $csvContent = "NIS,Ibadah1,Ibadah2,Ibadah3,Ibadah4,Ibadah5,Adab1,Adab2,Adab3,Adab4,Adab5,Pribadi1,Pribadi2,Pribadi3,Pribadi4,Pribadi5,Diri1,Diri2,Diri3,Catatan\n".
                      "999999999,A,B,C,D,E,A,B,C,D,E,A,B,C,D,E,Organisasi Pramuka,Baca Buku,Karya Tulis,Catatan Wali Mantap\n";

        $file = UploadedFile::fake()->createWithContent('import_test.csv', $csvContent);

        $previewResponse = $this->post(route('kesantrian.input-raport-kesantrian.preview'), [
            'file' => $file,
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
        ]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('preview_rows');

        $rows = session('preview_rows');
        $this->assertCount(1, $rows);
        $this->assertSame('999999999', $rows[0]['nis']);
        $this->assertSame('Burhan Testing', $rows[0]['nama']);
        $this->assertTrue($rows[0]['santri_ditemukan']);

        // Now commit via store
        $storeResponse = $this->post(route('kesantrian.input-raport-kesantrian.store'), [
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'rows_json' => json_encode($rows),
        ]);

        $storeResponse->assertRedirect(route('kesantrian.menu', [
            'menu' => 'data-raport-kesantrian',
            'periode' => '2024/2025 - GANJIL',
        ]));

        $this->assertDatabaseHas('akademik_raport_kesantrian', [
            'NOCUST' => '999999999',
            'periode' => '2024/2025 - GANJIL',
            'IbadahA' => 'A',
            'IbadahB' => 'B',
            'PengembanganA' => 'Organisasi Pramuka',
            'Catatan' => 'Catatan Wali Mantap',
        ]);
    }

    public function test_can_view_filter_and_delete_data_raport(): void
    {
        $this->signInAsKesantrian();

        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        Santri::create(['NOCUST' => '202001', 'NMCUST' => 'Ahmad Farel']);

        $raport = RaportKesantrian::create([
            'NOCUST' => '202001',
            'tgl' => '2024-10-29',
            'periode' => '2024/2025 - GANJIL',
            'Catatan' => 'Santri Rajin',
            'IbadahA' => 'A',
            'IbadahB' => 'A',
        ]);

        $response = $this->get(route('kesantrian.menu', 'data-raport-kesantrian'));
        $response->assertOk();
        $response->assertSee('Ahmad Farel');
        $response->assertSee('Santri Rajin');
        $response->assertSee('Tahun akademik');
        $response->assertSee('Kelompok');
        $response->assertSee('Berkata Sopan');
        $response->assertSee('Aktif Organisasi');
        $response->assertSee('Ekspor Excel');
        $response->assertDontSee('Diri 1');

        $this->get(route('kesantrian.data-raport-kesantrian.export'))
            ->assertOk()
            ->assertDownload('raport-kesantrian.xlsx');

        // Delete single
        $delResponse = $this->delete(route('kesantrian.data-raport-kesantrian.destroy', $raport->urut));
        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('akademik_raport_kesantrian', ['urut' => $raport->urut]);
    }

    public function test_can_preview_xlsx_file_and_bulk_delete(): void
    {
        $this->signInAsKesantrian();

        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        Santri::create(['NOCUST' => '999999999', 'NMCUST' => 'Burhan Santri']);

        $service = app(RaportKesantrianSpreadsheetService::class);
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
        $service->generateXlsxTemplate($tmpPath);

        $uploadedFile = new UploadedFile($tmpPath, 'test_template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $previewResponse = $this->post(route('kesantrian.input-raport-kesantrian.preview'), [
            'file' => $uploadedFile,
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
        ]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('preview_rows');
        $rows = session('preview_rows');
        $this->assertCount(1, $rows);
        $this->assertSame('999999999', $rows[0]['nis']);
        $this->assertSame('Burhan Santri', $rows[0]['nama']);
        $this->assertSame('A', $rows[0]['ibadah1']);
        $this->assertSame('B', $rows[0]['adab2']);
        $this->assertSame('E', $rows[0]['pribadi5']);
        $this->assertSame('B', $rows[0]['diri3']);
        $this->assertSame('Coba burhan 12mei26', $rows[0]['catatan']);

        // Store
        $this->post(route('kesantrian.input-raport-kesantrian.store'), [
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'rows_json' => json_encode($rows),
        ]);

        $created = RaportKesantrian::where('NOCUST', '999999999')->first();
        $this->assertNotNull($created);

        // Bulk delete
        $bulkResponse = $this->delete(route('kesantrian.data-raport-kesantrian.bulk'), [
            'ids' => [$created->urut],
        ]);
        $bulkResponse->assertRedirect();
        $this->assertDatabaseMissing('akademik_raport_kesantrian', ['urut' => $created->urut]);

        if (file_exists($tmpPath)) {
            @unlink($tmpPath);
        }
    }
}
