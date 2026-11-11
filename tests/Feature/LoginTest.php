<?php

namespace Tests\Feature;

use App\Models\CyberKey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginTest extends TestCase
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
    }

    public function test_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')
            ->assertOk()
            ->assertSee('Aplikasi')
            ->assertSee('Tahfidz')
            ->assertSee('name="aplikasi"', false)
            ->assertDontSee('toastify.min.js', false)
            ->assertSee('name="description"', false)
            ->assertSee('index, follow', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('theme-toggle', false)
            ->assertSee('icon-moon', false)
            ->assertSee('page-loader', false)
            ->assertSee('data-theme', false);
        $this->get('/tahfidz')->assertRedirect(route('login'));
    }

    public function test_each_group_enters_its_own_system(): void
    {
        $this->account('admtah', 'admtah', 'Admin Tahfidz', 'pintu-tahfidz');
        $this->account('admkes', 'admkes', 'Admin Kesantrian', 'pintu-kesantrian');
        $this->account('admaka', 'admaka', 'Admin Akademik', 'pintu-akademik');

        $this->post('/login', ['aplikasi' => 'admtah', 'users' => 'admtah', 'password' => 'pintu-tahfidz'])
            ->assertRedirect('/tahfidz');
        $this->get('/tahfidz')->assertOk()->assertSee('Admin Tahfidz');
        $this->get('/kesantrian')->assertRedirect('/tahfidz');

        auth()->logout();

        $this->from('/login')
            ->post('/login', ['aplikasi' => 'admkes', 'users' => 'admtah', 'password' => 'pintu-tahfidz'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('aplikasi');
        $this->assertGuest();

        $this->post('/login', ['aplikasi' => 'admkes', 'users' => 'admkes', 'password' => 'pintu-kesantrian'])
            ->assertRedirect('/kesantrian');
        $this->get('/kesantrian')->assertOk()->assertSee('Kesantrian');
        $this->get('/akademik')->assertRedirect('/kesantrian');

        auth()->logout();

        $this->post('/login', ['aplikasi' => 'admaka', 'users' => 'admaka', 'password' => 'pintu-akademik'])
            ->assertRedirect('/akademik');
        $this->get('/akademik')->assertOk()->assertSee('Raport Akademik');
        $this->get('/tahfidz')->assertRedirect('/akademik');
    }

    public function test_wrong_password_and_other_groups_stay_outside(): void
    {
        $this->account('admtah', 'admtah', 'Admin Tahfidz', 'pintu-tahfidz');
        $this->account('admkeu', 'admkeu', 'Admin Keuangan', 'pintu-keuangan');
        DB::table('cyber_key')->insert([
            'users' => 'kosong',
            'ket' => 'Belum ada password',
            'kel' => 'admtah',
            'password' => null,
        ]);

        $this->from('/login')
            ->post('/login', ['aplikasi' => 'admtah', 'users' => 'admtah', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('users');
        $this->followRedirects(
            $this->from('/login')->post('/login', ['aplikasi' => 'admtah', 'users' => 'admtah', 'password' => 'masih-salah'])
        )->assertSee('toastify.min.js', false)
            ->assertSee('Username atau password salah.', false);
        $this->assertGuest();

        $this->from('/login')
            ->post('/login', ['aplikasi' => 'admtah', 'users' => 'admkeu', 'password' => 'pintu-keuangan'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('aplikasi');
        $this->assertGuest();

        $this->from('/login')
            ->post('/login', ['aplikasi' => 'admtah', 'users' => 'kosong', 'password' => 'apa-saja'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('users');
        $this->assertGuest();
    }

    public function test_legacy_sha1_password_logs_in_and_is_stored_as_bcrypt(): void
    {
        DB::table('cyber_key')->insert([
            'users' => 'admtah',
            'kunci' => 'tetap',
            'ket' => 'Admin Tahfidz',
            'kel' => 'admtah',
            'password' => sha1('pintu-lama'),
        ]);

        $this->post('/login', [
            'aplikasi' => 'admtah',
            'users' => 'admtah',
            'password' => 'pintu-lama',
        ])->assertRedirect('/tahfidz');

        $row = DB::table('cyber_key')->where('users', 'admtah')->first();

        $this->assertSame('tetap', $row->kunci);
        $this->assertTrue(Hash::check('pintu-lama', $row->password));
        $this->assertNotSame(sha1('pintu-lama'), $row->password);
    }

    public function test_empty_password_accepts_the_legacy_key_and_stores_bcrypt(): void
    {
        DB::table('cyber_key')->insert([
            'users' => 'admaka',
            'kunci' => 'pintu',
            'ket' => 'Admin Akademik',
            'kel' => 'admaka',
            'password' => null,
        ]);

        $this->post('/login', [
            'aplikasi' => 'admaka',
            'users' => 'admaka',
            'password' => 'pintu',
        ])->assertRedirect('/akademik');

        $row = DB::table('cyber_key')->where('users', 'admaka')->first();

        $this->assertSame('pintu', $row->kunci);
        $this->assertTrue(Hash::check('pintu', $row->password));
    }

    public function test_legacy_key_logs_in_without_replacing_an_existing_password(): void
    {
        DB::table('cyber_key')->insert([
            'users' => 'admtah',
            'kunci' => 'kunci-tahfidz',
            'ket' => 'Admin Tahfidz',
            'kel' => 'admtah',
            'password' => Hash::make('pintu-baru'),
        ]);
        DB::table('cyber_key')->insert([
            'users' => 'admkes',
            'kunci' => 'kunci-kesantrian',
            'ket' => 'Admin Kesantrian',
            'kel' => 'adm kes',
            'password' => null,
        ]);

        $this->post('/login', [
            'aplikasi' => 'admtah',
            'users' => 'admtah',
            'password' => 'kunci-tahfidz',
        ])->assertRedirect('/tahfidz');

        $tahfidz = DB::table('cyber_key')->where('users', 'admtah')->first();
        $this->assertSame('kunci-tahfidz', $tahfidz->kunci);
        $this->assertTrue(Hash::check('pintu-baru', $tahfidz->password));

        auth()->logout();

        $this->post('/login', [
            'aplikasi' => 'admkes',
            'users' => 'admkes',
            'password' => 'kunci-kesantrian',
        ])->assertRedirect('/kesantrian');

        auth()->logout();

        $this->from('/login')
            ->post('/login', [
                'aplikasi' => 'admaka',
                'users' => 'admtah',
                'password' => 'kunci-tahfidz',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('aplikasi');
    }

    public function test_logout_returns_to_login(): void
    {
        $this->account('admaka', 'admaka', 'Admin Akademik', 'pintu-akademik');

        $this->post('/login', ['aplikasi' => 'admaka', 'users' => 'admaka', 'password' => 'pintu-akademik']);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/akademik')->assertRedirect(route('login'));
    }

    public function test_password_command_updates_only_the_password_column(): void
    {
        DB::table('cyber_key')->insert([
            'users' => 'admtah',
            'kunci' => 'tetap',
            'ket' => 'Admin Tahfidz',
            'kel' => 'admtah',
            'urut' => 9,
            'password' => null,
        ]);

        $this->artisan('cyber-key:password', [
            'users' => 'admtah',
            '--password' => 'pintu-baru',
        ])->assertSuccessful();

        $row = DB::table('cyber_key')->where('users', 'admtah')->first();

        $this->assertSame('tetap', $row->kunci);
        $this->assertSame('Admin Tahfidz', $row->ket);
        $this->assertSame('admtah', $row->kel);
        $this->assertSame(9, (int) $row->urut);
        $this->assertTrue(Hash::check('pintu-baru', $row->password));

        $this->post('/login', ['aplikasi' => 'admtah', 'users' => 'admtah', 'password' => 'pintu-baru'])
            ->assertRedirect('/tahfidz');
    }

    private function account(string $username, string $kel, string $name, string $password): void
    {
        CyberKey::query()->create([
            'users' => $username,
            'ket' => $name,
            'kel' => $kel,
            'password' => $password,
        ]);
    }
}
