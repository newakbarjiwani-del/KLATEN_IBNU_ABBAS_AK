<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\ItemRaportKesantrian;
use App\Models\KategoriRaportKesantrian;
use App\Models\NilaiRaportKesantrian;
use App\Models\RaportKesantrian;
use App\Models\Santri;
use App\Models\TahunAkademik;
use App\Services\SpreadsheetFile;
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
            $table->string('unit', 50)->nullable();
        });

        Schema::create('mst_thn_aka', function (Blueprint $table) {
            $table->increments('urut');
            $table->string('thn_aka', 30)->nullable();
        });

        Schema::create('scctcust', function (Blueprint $table) {
            $table->increments('CUSTID');
            $table->string('NOCUST', 15)->nullable();
            $table->string('NMCUST', 70)->nullable();
            $table->string('CODE02', 50)->nullable();
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

        Schema::create('akademik_ref_raport_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 20);
            $table->string('nama', 80);
            $table->unsignedSmallInteger('urut')->default(1);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('akademik_ref_raport_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama', 80);
            $table->unsignedSmallInteger('urut')->default(1);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('akademik_raport_kesantrian_nilai', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('raport_urut');
            $table->unsignedBigInteger('item_id');
            $table->string('nilai', 255)->nullable();
            $table->unique(['raport_urut', 'item_id']);
        });
    }

    protected function signInAsKesantrian(?string $unit = null, string $username = 'admkesantrian'): CyberKey
    {
        $user = CyberKey::create([
            'users' => $username,
            'kel' => 'admkes',
            'ket' => 'Admin Kesantrian',
            'unit' => $unit,
        ]);

        $this->actingAs($user);

        return $user;
    }

    protected function buatButir(string $unit, string $kategori, string $butir): ItemRaportKesantrian
    {
        $kelompok = KategoriRaportKesantrian::query()->create([
            'unit' => $unit,
            'nama' => $kategori,
            'urut' => 1,
        ]);

        return $kelompok->items()->create([
            'nama' => $butir,
            'urut' => 1,
        ]);
    }

    public function test_input_page_asks_for_categories_first(): void
    {
        $this->signInAsKesantrian();

        $response = $this->get(route('kesantrian.menu', 'input-raport-kesantrian'));

        $response->assertOk();
        $response->assertSee('belum dibuat');
        $response->assertSee('Master Kategori Raport');
        $response->assertDontSee('OPEN (Preview)');
    }

    public function test_template_follows_the_unit_items(): void
    {
        $this->signInAsKesantrian();
        $this->buatButir('KUTTAB', 'Ibadah', 'Sholat Wajib');

        $response = $this->get(route('kesantrian.input-raport-kesantrian.template', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Sholat Wajib', $response->getContent());
        $this->assertStringContainsString('Catatan', $response->getContent());
        $this->assertStringNotContainsString('Wali Hujroh', $response->getContent());
    }

    public function test_store_writes_a_score_for_each_item(): void
    {
        $this->signInAsKesantrian();
        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        $item = $this->buatButir('KUTTAB', 'Ibadah', 'Sholat Wajib');
        Santri::create(['NOCUST' => '999999999', 'NMCUST' => 'Burhan Testing', 'CODE02' => 'KUTTAB']);

        $file = UploadedFile::fake()->createWithContent(
            'import_test.csv',
            "NIS,Sholat Wajib,Catatan\n999999999,A,Catatan Wali Mantap\n",
        );

        $preview = $this->post(route('kesantrian.input-raport-kesantrian.preview'), [
            'file' => $file,
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'unit' => 'KUTTAB',
        ]);

        $preview->assertRedirect();
        $preview->assertSessionHas('preview_rows');
        $rows = session('preview_rows');
        $this->assertSame('A', $rows[0]['nilai'][$item->id]);
        $this->assertTrue($rows[0]['unit_sesuai']);

        $store = $this->post(route('kesantrian.input-raport-kesantrian.store'), [
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'unit' => 'KUTTAB',
            'rows_json' => json_encode($rows),
        ]);

        $store->assertRedirect(route('kesantrian.menu', [
            'menu' => 'data-raport-kesantrian',
            'periode' => '2024/2025 - GANJIL',
            'unit' => 'KUTTAB',
        ]));

        $this->assertDatabaseHas('akademik_raport_kesantrian', [
            'NOCUST' => '999999999',
            'Catatan' => 'Catatan Wali Mantap',
            'IbadahA' => null,
        ]);
        $this->assertDatabaseHas('akademik_raport_kesantrian_nilai', [
            'item_id' => $item->id,
            'nilai' => 'A',
        ]);
    }

    public function test_data_page_shows_unit_items_and_can_delete(): void
    {
        $this->signInAsKesantrian();
        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        $item = $this->buatButir('KUTTAB', 'Adab', 'Berkata Sopan');
        Santri::create(['NOCUST' => '202001', 'NMCUST' => 'Ahmad Farel', 'CODE02' => 'KUTTAB']);

        $raport = RaportKesantrian::create([
            'NOCUST' => '202001',
            'tgl' => '2024-10-29',
            'periode' => '2024/2025 - GANJIL',
            'Catatan' => 'Santri Rajin',
        ]);
        NilaiRaportKesantrian::query()->create([
            'raport_urut' => $raport->urut,
            'item_id' => $item->id,
            'nilai' => 'A',
        ]);

        $response = $this->get(route('kesantrian.menu', 'data-raport-kesantrian'));
        $response->assertOk();
        $response->assertSee('Ahmad Farel');
        $response->assertSee('Santri Rajin');
        $response->assertSee('Berkata Sopan');
        $response->assertSee('Catatan');
        $response->assertDontSee('Wali Hujroh');

        $this->get(route('kesantrian.data-raport-kesantrian.export', ['unit' => 'KUTTAB']))
            ->assertOk()
            ->assertDownload('raport-kesantrian.xlsx');

        $this->delete(route('kesantrian.data-raport-kesantrian.destroy', $raport->urut), ['unit' => 'KUTTAB'])
            ->assertRedirect();
        $this->assertDatabaseMissing('akademik_raport_kesantrian', ['urut' => $raport->urut]);
    }

    public function test_xlsx_import_and_bulk_delete(): void
    {
        $this->signInAsKesantrian();
        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        $item = $this->buatButir('KUTTAB', 'Ibadah', 'Sholat Wajib');
        Santri::create(['NOCUST' => '999999999', 'NMCUST' => 'Burhan Santri', 'CODE02' => 'KUTTAB']);

        $tmpPath = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
        app(SpreadsheetFile::class)->write($tmpPath, [
            ['NIS', 'Sholat Wajib', 'Catatan'],
            ['999999999', 'A', 'Coba burhan 12mei26'],
        ], 'Raport');

        $uploadedFile = new UploadedFile($tmpPath, 'test_template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->post(route('kesantrian.input-raport-kesantrian.preview'), [
            'file' => $uploadedFile,
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'unit' => 'KUTTAB',
        ]);

        $preview->assertRedirect();
        $rows = session('preview_rows');
        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]['nilai'][$item->id]);
        $this->assertSame('Coba burhan 12mei26', $rows[0]['catatan']);

        $this->post(route('kesantrian.input-raport-kesantrian.store'), [
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'unit' => 'KUTTAB',
            'rows_json' => json_encode($rows),
        ]);

        $created = RaportKesantrian::query()->where('NOCUST', '999999999')->first();
        $this->assertNotNull($created);

        $this->delete(route('kesantrian.data-raport-kesantrian.bulk'), [
            'ids' => [$created->urut],
            'unit' => 'KUTTAB',
        ])->assertRedirect();
        $this->assertDatabaseMissing('akademik_raport_kesantrian', ['urut' => $created->urut]);

        if (file_exists($tmpPath)) {
            @unlink($tmpPath);
        }
    }

    public function test_another_unit_does_not_see_or_save_these_categories(): void
    {
        $this->buatButir('KUTTAB', 'Ibadah', 'Sholat Kuttab');
        $smp = $this->buatButir('SMP', 'Adab', 'Sopan SMP');
        $this->signInAsKesantrian('SMP', 'admsmpkes');
        TahunAkademik::create(['thn_aka' => '2024/2025 - GANJIL']);
        Santri::create(['NOCUST' => '1001', 'NMCUST' => 'Santri SMP', 'CODE02' => 'SMP']);
        Santri::create(['NOCUST' => '1002', 'NMCUST' => 'Santri SMA', 'CODE02' => 'SMA']);

        $page = $this->get(route('kesantrian.menu', 'input-raport-kesantrian'));
        $page->assertOk();
        $page->assertSee('Sopan SMP');
        $page->assertDontSee('Sholat Kuttab');

        $kuttab = KategoriRaportKesantrian::query()->where('unit', 'KUTTAB')->first();
        $this->put(route('kesantrian.master-kategori-raport.kategori.update', $kuttab->id), [
            'nama' => 'Diubah',
            'urut' => 1,
        ])->assertNotFound();

        $file = UploadedFile::fake()->createWithContent(
            'campuran.csv',
            "NIS,Sopan SMP,Catatan\n1001,A,Catatan SMP\n1002,B,Catatan SMA\n",
        );
        $preview = $this->post(route('kesantrian.input-raport-kesantrian.preview'), [
            'file' => $file,
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
        ]);
        $rows = session('preview_rows');
        $this->assertTrue($rows[0]['unit_sesuai']);
        $this->assertFalse($rows[1]['unit_sesuai']);

        $this->post(route('kesantrian.input-raport-kesantrian.store'), [
            'periode' => '2024/2025 - GANJIL',
            'tgl' => '2024-10-29',
            'rows_json' => json_encode($rows),
        ]);

        $this->assertDatabaseHas('akademik_raport_kesantrian_nilai', [
            'item_id' => $smp->id,
            'nilai' => 'A',
        ]);
        $this->assertDatabaseMissing('akademik_raport_kesantrian', ['NOCUST' => '1002']);
    }

    public function test_master_can_add_a_category_and_item_for_a_unit(): void
    {
        $this->signInAsKesantrian();

        $this->post(route('kesantrian.master-kategori-raport.kategori.store'), [
            'unit' => 'SMA',
            'nama' => 'Ibadah',
            'urut' => 1,
        ])->assertRedirect(route('kesantrian.menu', ['menu' => 'master-kategori-raport', 'unit' => 'SMA']));

        $kategori = KategoriRaportKesantrian::query()->where('unit', 'SMA')->first();
        $this->assertNotNull($kategori);

        $this->post(route('kesantrian.master-kategori-raport.item.store'), [
            'kategori_id' => $kategori->id,
            'nama' => 'Sholat Wajib',
            'urut' => 1,
        ])->assertRedirect();

        $this->get(route('kesantrian.menu', ['menu' => 'master-kategori-raport', 'unit' => 'SMA']))
            ->assertOk()
            ->assertSee('Sholat Wajib');

        $this->get(route('kesantrian.menu', ['menu' => 'master-kategori-raport', 'unit' => 'KMI']))
            ->assertOk()
            ->assertDontSee('Sholat Wajib');
    }

    public function test_a_student_report_downloads_with_the_akhlak_layout(): void
    {
        $this->signInAsKesantrian();
        $item = $this->buatButir('KUTTAB', 'Ibadah', 'Sholat Dhuha');
        Santri::create([
            'NOCUST' => '2266',
            'NMCUST' => 'Abida Taqiya',
            'CODE02' => 'KUTTAB',
            'DESC02' => 'X B1',
            'DESC03' => 'Alexandria 12',
        ]);
        $raport = RaportKesantrian::create([
            'NOCUST' => '2266',
            'tgl' => '2026-06-20',
            'periode' => '2025/2026 - GENAP',
            'Catatan' => 'Pertahankan semangat belajar.',
        ]);
        NilaiRaportKesantrian::query()->create([
            'raport_urut' => $raport->urut,
            'item_id' => $item->id,
            'nilai' => 'A',
        ]);

        $this->get(route('kesantrian.menu', ['menu' => 'data-raport-kesantrian', 'unit' => 'KUTTAB']))
            ->assertOk()
            ->assertSee('Unduh');

        $unduh = $this->get(route('kesantrian.data-raport-kesantrian.unduh', ['urut' => $raport->urut, 'unit' => 'KUTTAB']));
        $unduh->assertOk();
        $unduh->assertDownload('laporan-akhlak-2266.html');
        $unduh->assertSee('LAPORAN AKHLAK SANTRIWATI', false);
        $unduh->assertSee('Abida Taqiya', false);
        $unduh->assertSee('X B1', false);
        $unduh->assertSee('Alexandria 12', false);
        $unduh->assertSee('2025-2026/Genap', false);
        $unduh->assertSee('A. IBADAH', false);
        $unduh->assertSee('Sholat Dhuha', false);
        $unduh->assertSee('>A<', false);
        $unduh->assertSee('Catatan Wali Kamar', false);
        $unduh->assertSee('Pertahankan semangat belajar.', false);
        $unduh->assertSee('Klaten, 20 Juni 2026', false);
        $unduh->assertSee('Kepala Kesantrian Putri', false);

        $this->signInAsKesantrian('SMP', 'admsmpkes');
        $this->get(route('kesantrian.data-raport-kesantrian.unduh', $raport->urut))
            ->assertNotFound();
    }
}
