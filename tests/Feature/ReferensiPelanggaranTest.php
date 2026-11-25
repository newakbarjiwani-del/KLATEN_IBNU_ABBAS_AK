<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\ReferensiPelanggaran;
use App\Services\SpreadsheetFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
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
            $table->string('unit', 50)->nullable();
        });

        Schema::create('akademik_ref_langgar', function (Blueprint $table) {
            $table->increments('urut');
            $table->text('jenis_pelangaran')->nullable();
            $table->smallInteger('skor')->nullable();
            $table->string('keterangan', 20)->nullable();
            $table->string('unit', 20)->nullable();
            $table->timestamp('deleted_at')->nullable();
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
            ->assertSee('Unggah Excel')
            ->assertSee('Hapus')
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
                'unit' => 'SMP',
            ])
            ->assertRedirect(route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => 'SMP']))
            ->assertSessionHas('status', 'Pelanggaran ditambahkan.');

        $this->assertDatabaseHas('akademik_ref_langgar', [
            'jenis_pelangaran' => 'Terlambat mengikuti kegiatan',
            'skor' => 5,
            'keterangan' => 'nasihat',
            'unit' => 'SMP',
        ]);
    }

    public function test_duplicate_violation_name_is_rejected(): void
    {
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Tidak memiliki Al-Qur\'an',
            'skor' => 20,
            'unit' => 'SMP',
        ]);

        $admin = $this->kesantrianAdmin();

        $this->actingAs($admin)
            ->from('/kesantrian/master-pelanggaran')
            ->post('/kesantrian/master-pelanggaran', [
                'nama' => 'Tidak memiliki Al-Qur\'an',
                'skor' => 20,
                'unit' => 'SMP',
            ])
            ->assertRedirect('/kesantrian/master-pelanggaran')
            ->assertSessionHasErrors('nama');

        $this->actingAs($admin)
            ->post('/kesantrian/master-pelanggaran', [
                'nama' => 'Tidak memiliki Al-Qur\'an',
                'skor' => 20,
                'unit' => 'KMI',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ReferensiPelanggaran::query()->count());
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

    public function test_kesantrian_can_edit_a_violation_reference(): void
    {
        $item = ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Terlambat apel',
            'skor' => 5,
            'keterangan' => 'SPT',
            'unit' => 'SMP',
        ]);

        $this->actingAs($this->kesantrianAdmin())
            ->put('/kesantrian/master-pelanggaran/'.$item->urut, [
                'nama' => 'Terlambat upacara',
                'skor' => 8,
                'keterangan' => 'baru',
                'unit' => 'SMA',
            ])
            ->assertRedirect(route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => 'SMA']))
            ->assertSessionHas('status', 'Pelanggaran diperbarui.');

        $this->assertDatabaseHas('akademik_ref_langgar', [
            'urut' => $item->urut,
            'jenis_pelangaran' => 'Terlambat upacara',
            'skor' => 8,
            'keterangan' => 'baru',
            'unit' => 'SMA',
        ]);
    }

    public function test_delete_sets_deleted_at_and_hides_the_row(): void
    {
        $item = ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Akan dihapus',
            'skor' => 3,
            'unit' => 'KMI',
        ]);
        $admin = $this->kesantrianAdmin();

        $this->actingAs($admin)
            ->delete('/kesantrian/master-pelanggaran/'.$item->urut)
            ->assertRedirect(route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => 'KMI']))
            ->assertSessionHas('status', 'Pelanggaran dihapus.');

        $this->assertNull(ReferensiPelanggaran::query()->find($item->urut));
        $trashed = ReferensiPelanggaran::withTrashed()->find($item->urut);
        $this->assertNotNull($trashed?->deleted_at);

        $this->actingAs($admin)
            ->get('/kesantrian/master-pelanggaran?unit=KMI')
            ->assertOk()
            ->assertDontSee('Akan dihapus');

        $kuttab = ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Tetap ada',
            'skor' => 2,
            'unit' => 'KUTTAB',
        ]);
        $smp = CyberKey::query()->create([
            'users' => 'admsmpkes',
            'ket' => 'Admin Kesantrian SMP',
            'kel' => 'admkes',
            'unit' => 'SMP',
            'password' => 'pintu-kesantrian',
        ]);

        $this->actingAs($smp)
            ->delete('/kesantrian/master-pelanggaran/'.$kuttab->urut)
            ->assertNotFound();

        $this->assertNull(ReferensiPelanggaran::withTrashed()->find($kuttab->urut)?->deleted_at);
    }

    public function test_unit_account_only_manages_its_own_violations(): void
    {
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Pelanggaran SMP',
            'skor' => 4,
            'unit' => 'SMP',
        ]);
        $kuttab = ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Pelanggaran Kuttab',
            'skor' => 6,
            'unit' => 'KUTTAB',
        ]);
        $admin = CyberKey::query()->create([
            'users' => 'admsmpkes',
            'ket' => 'Admin Kesantrian SMP',
            'kel' => 'admkes',
            'unit' => 'SMP',
            'password' => 'pintu-kesantrian',
        ]);

        $this->actingAs($admin)
            ->get('/kesantrian/master-pelanggaran')
            ->assertOk()
            ->assertSee('Pelanggaran SMP')
            ->assertDontSee('Pelanggaran Kuttab');

        $this->actingAs($admin)
            ->put('/kesantrian/master-pelanggaran/'.$kuttab->urut, [
                'nama' => 'Diubah',
                'skor' => 1,
                'unit' => 'KUTTAB',
            ])
            ->assertNotFound();
    }

    public function test_excel_upload_adds_and_updates_violations_per_unit(): void
    {
        ReferensiPelanggaran::query()->create([
            'jenis_pelangaran' => 'Sudah ada',
            'skor' => 1,
            'unit' => 'SMP',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'langgar');
        app(SpreadsheetFile::class)->write($path, [
            ['Unit', 'Nama pelanggaran', 'Skor', 'Keterangan'],
            ['SMP', 'Sudah ada', '15', 'SPT'],
            ['KMI', 'Baru dari excel', '7', ''],
        ], 'Master');

        $this->actingAs($this->kesantrianAdmin())
            ->post('/kesantrian/master-pelanggaran/unggah', [
                'berkas' => new UploadedFile($path, 'master.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])
            ->assertRedirect(route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => 'SMP']))
            ->assertSessionHas('status', 'Unggah selesai. 1 ditambahkan, 1 diperbarui, 0 dilewati.');

        $this->assertDatabaseHas('akademik_ref_langgar', [
            'jenis_pelangaran' => 'Sudah ada',
            'skor' => 15,
            'keterangan' => 'SPT',
            'unit' => 'SMP',
        ]);
        $this->assertDatabaseHas('akademik_ref_langgar', [
            'jenis_pelangaran' => 'Baru dari excel',
            'skor' => 7,
            'unit' => 'KMI',
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
