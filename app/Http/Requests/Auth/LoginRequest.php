<?php

namespace App\Http\Requests\Auth;

use App\Models\CyberKey;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDOException;
use RuntimeException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'users' => ['required', 'string', 'max:15'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'users.required' => 'Username wajib diisi.',
            'users.max' => 'Username terlalu panjang.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        try {
            $account = CyberKey::query()->where('users', $this->string('users'))->first();
        } catch (PDOException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'users' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.',
            ]);
        }

        $plainPassword = (string) $this->string('password');
        $storedPassword = $account?->getRawOriginal('password');

        try {
            $passwordMatches = $account !== null && $this->passwordMatches($plainPassword, $storedPassword);
        } catch (RuntimeException $exception) {
            report($exception);
            $passwordMatches = false;
        }

        if (! $passwordMatches && $account !== null) {
            $passwordMatches = $this->keyMatches($plainPassword, $account->getRawOriginal('kunci'));
        }

        if (! $passwordMatches) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'users' => 'Username atau password salah.',
            ]);
        }

        if ($account->homeRoute() === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'users' => 'Akun ini belum memiliki akses aplikasi.',
            ]);
        }

        if (! Hash::isHashed((string) $storedPassword)) {
            $account->password = $plainPassword;
            $account->save();
        }

        Auth::login($account);

        RateLimiter::clear($this->throttleKey());
    }

    private function passwordMatches(string $plainPassword, mixed $storedPassword): bool
    {
        $storedPassword = (string) $storedPassword;

        if ($storedPassword === '') {
            return false;
        }

        if (Hash::isHashed($storedPassword)) {
            return Hash::check($plainPassword, $storedPassword);
        }

        if (preg_match('/^[0-9a-fA-F]{40}$/', $storedPassword) === 1
            && hash_equals(strtolower($storedPassword), sha1($plainPassword))) {
            return true;
        }

        return strlen($storedPassword) === strlen($plainPassword)
            && hash_equals($storedPassword, $plainPassword);
    }

    private function keyMatches(string $plainPassword, mixed $kunci): bool
    {
        $kunci = trim((string) $kunci);

        if ($kunci === '' || $plainPassword === '') {
            return false;
        }

        return strlen($kunci) === strlen($plainPassword)
            && hash_equals($kunci, $plainPassword);
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'users' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('users')).'|'.$this->ip());
    }
}
