<?php

namespace App\Console\Commands;

use App\Models\CyberKey;
use Illuminate\Console\Command;
use PDOException;

class SetCyberKeyPassword extends Command
{
    protected $signature = 'cyber-key:password {users : Username pada tabel cyber_key} {--password= : Password baru, jika tidak diisi akan diminta}';

    protected $description = 'Mengisi kolom password satu akun cyber_key tanpa mengubah kolom lain';

    public function handle(): int
    {
        $username = (string) $this->argument('users');
        $password = (string) ($this->option('password') ?: $this->secret('Password baru'));

        if ($password === '') {
            $this->error('Password tidak boleh kosong.');

            return self::FAILURE;
        }

        try {
            $account = CyberKey::query()->where('users', $username)->first();
        } catch (PDOException $exception) {
            report($exception);
            $this->error('Tidak dapat membaca tabel cyber_key. Pastikan koneksi database hidup dan kolom password sudah ditambahkan.');

            return self::FAILURE;
        }

        if ($account === null) {
            $this->error("Akun {$username} tidak ditemukan.");

            return self::FAILURE;
        }

        $account->password = $password;
        $account->save();

        $this->info("Password {$username} diperbarui. Kelompok akun: {$account->kelCode()} ({$account->systemName()}).");

        return self::SUCCESS;
    }
}
