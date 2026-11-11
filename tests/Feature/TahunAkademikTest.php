<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use App\Models\TahunAkademik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TahunAkademikTest extends TestCase
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
            $table->string('thn_aka', 18)->nullable();
        });
    }

    public function test_kesantrian_lists_academic_years_from_the_master_table(): void
    {
        TahunAkademik::query()->create(['thn_aka' => '2023/2024 - GANJIL']);
        TahunAkademik::query()->create(['thn_aka' => '2023/2024 - GENAP']);

        $response = $this->actingAs($this->kesantrianAdmin())
            ->get('/kesantrian/tahun-akademik');

        $response
            ->assertOk()
            ->assertSee('Master Tahun Akademik')
            ->assertSee('2023/2024 - GANJIL')
            ->assertSee('2023/2024 - GENAP')
            ->assertSee('class="col-no"', false)
            ->assertSee('Nomor urut berjalan dari yang terkecil.')
            ->assertSee('name="tahun"', false)
            ->assertSee('id="add-tahun"', false)
            ->assertSee('page-loader', false)
            ->assertDontSee('belum diisi.');

        $html = $response->getContent();
        $this->assertTrue(
            strpos($html, '2023/2024 - GANJIL') < strpos($html, '2023/2024 - GENAP')
        );
    }

    public function test_kesantrian_can_add_an_academic_year(): void
    {
        $this->actingAs($this->kesantrianAdmin())
            ->post('/kesantrian/tahun-akademik', [
                'tahun' => '2024',
                'semester' => 'GANJIL',
            ])
            ->assertRedirect(route('kesantrian.menu', 'tahun-akademik'))
            ->assertSessionHas('status', 'Tahun akademik 2024/2025 - GANJIL ditambahkan.');

        $this->assertDatabaseHas('mst_thn_aka', [
            'thn_aka' => '2024/2025 - GANJIL',
        ]);
    }

    public function test_duplicate_academic_year_is_rejected(): void
    {
        TahunAkademik::query()->create(['thn_aka' => '2024/2025 - GENAP']);

        $this->actingAs($this->kesantrianAdmin())
            ->from('/kesantrian/tahun-akademik')
            ->post('/kesantrian/tahun-akademik', [
                'tahun' => '2024',
                'semester' => 'GENAP',
            ])
            ->assertRedirect('/kesantrian/tahun-akademik')
            ->assertSessionHasErrors('tahun');

        $this->assertSame(1, TahunAkademik::query()->count());
    }

    public function test_other_systems_cannot_open_the_academic_year_master(): void
    {
        $tahfidz = CyberKey::query()->create([
            'users' => 'admtah',
            'ket' => 'Admin Tahfidz',
            'kel' => 'admtah',
            'password' => 'pintu-tahfidz',
        ]);

        $this->actingAs($tahfidz)
            ->get('/kesantrian/tahun-akademik')
            ->assertRedirect(route('tahfidz.home'));
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
