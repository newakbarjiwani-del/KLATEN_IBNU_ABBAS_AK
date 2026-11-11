<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RaportFileStorage
{
    public const AKADEMIK = 'akademik';

    public const TAHFIDZ = 'tahfidz';

    public function store(string $kind, UploadedFile $file, string $filename, string $nis, string $year, string $exam): string
    {
        $filename = $this->availableName($kind, $filename);
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RaportStorageException('Berkas tidak dapat dibaca.');
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(60)
                ->attach('file', $contents, $filename)
                ->post($this->endpoint($kind), [
                    'nis' => $nis,
                    'tahun_aka' => $year,
                    'ujian' => $exam,
                    'submit' => 'Rapot',
                ]);
        } catch (ConnectionException) {
            throw new RaportStorageException('Server raport tidak dapat dihubungi. Coba lagi.');
        }

        if (! $response->successful() || ! str_contains($response->body(), 'has been uploaded')) {
            throw new RaportStorageException('Berkas tidak tersimpan di server raport. Coba lagi.');
        }

        return $filename;
    }

    public function url(string $kind, ?string $name): ?string
    {
        $name = $this->safeName((string) $name);

        if ($name === '') {
            return null;
        }

        return $this->base($kind).'/uploads/'.rawurlencode($name);
    }

    public function filename(string $original): string
    {
        $name = $this->safeName($original);

        if ($name === '' || ! str_ends_with(strtolower($name), '.pdf')) {
            $name = ($name === '' ? 'raport' : $name).'.pdf';
        }

        if (strlen($name) > 250) {
            $name = substr($name, 0, 246).'.pdf';
        }

        return $name;
    }

    private function availableName(string $kind, string $filename): string
    {
        $candidate = $filename;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            if (! $this->exists($kind, $candidate)) {
                return $candidate;
            }

            $candidate = Str::beforeLast($filename, '.').'-'.Str::lower(Str::random(4)).'.pdf';
        }

        return $candidate;
    }

    private function exists(string $kind, string $filename): bool
    {
        $url = $this->url($kind, $filename);

        if ($url === null) {
            return false;
        }

        try {
            return Http::connectTimeout(3)->timeout(5)->head($url)->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    private function endpoint(string $kind): string
    {
        return $this->base($kind).'/index.php';
    }

    private function base(string $kind): string
    {
        $root = rtrim((string) config('services.raport.host'), '/');
        $path = $kind === self::TAHFIDZ
            ? (string) config('services.raport.tahfidz_path')
            : (string) config('services.raport.akademik_path');

        return $root.'/'.trim($path, '/');
    }

    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N}.\- _()]+/u', '_', $name) ?? '';

        return trim($name, '._');
    }
}
