<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class CyberKey extends Authenticatable
{
    public const SYSTEMS = [
        'admtah' => [
            'name' => 'Tahfidz',
            'route' => 'tahfidz.home',
            'summary' => 'Hafalan dan setoran santri.',
        ],
        'admkes' => [
            'name' => 'Kesantrian',
            'route' => 'kesantrian.home',
            'summary' => 'Kedisiplinan dan keseharian santri.',
        ],
        'admaka' => [
            'name' => 'Raport Akademik',
            'route' => 'akademik.home',
            'summary' => 'Nilai dan rapor.',
        ],
    ];

    public const ACCESS = [
        1 => [
            'kel' => 'admkes',
            'name' => 'Kesantrian',
            'route' => 'kesantrian.home',
        ],
        2 => [
            'kel' => 'admtah',
            'name' => 'Tahfidz',
            'route' => 'tahfidz.home',
        ],
        3 => [
            'kel' => 'admaka',
            'name' => 'Raport Akademik',
            'route' => 'akademik.home',
        ],
    ];

    protected $table = 'cyber_key';

    protected $primaryKey = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [
        'kunci',
    ];

    protected $hidden = [
        'kunci',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function kelCode(): string
    {
        return str_replace(' ', '', trim((string) $this->kel));
    }

    /**
     * @return array<int, int>
     */
    public function accessIds(): array
    {
        $raw = trim((string) $this->akses);
        $ids = [];

        if ($raw !== '') {
            foreach (explode(',', $raw) as $part) {
                $id = (int) trim($part);

                if (isset(self::ACCESS[$id]) && ! in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        if ($ids !== []) {
            return $ids;
        }

        foreach (self::ACCESS as $id => $access) {
            if ($access['kel'] === $this->kelCode()) {
                return [$id];
            }
        }

        return [];
    }

    public function allows(string $kel): bool
    {
        foreach ($this->accessIds() as $id) {
            if (self::ACCESS[$id]['kel'] === $kel) {
                return true;
            }
        }

        return false;
    }

    public function system(): ?array
    {
        return self::SYSTEMS[$this->kelCode()] ?? null;
    }

    public function systemName(): string
    {
        return $this->system()['name'] ?? $this->portalTitle();
    }

    public function portalTitle(): string
    {
        $unit = trim((string) $this->unit);

        if ($unit !== '') {
            return $unit;
        }

        $ids = $this->accessIds();

        if (count($ids) === 1) {
            return self::ACCESS[$ids[0]]['name'];
        }

        if (count($ids) > 1) {
            return 'Portal';
        }

        return 'Tidak dikenal';
    }

    public function homeRoute(): ?string
    {
        $ids = $this->accessIds();

        if (count($ids) > 1) {
            return 'portal.home';
        }

        if (count($ids) === 1) {
            return self::ACCESS[$ids[0]]['route'];
        }

        return null;
    }

    public function displayName(): string
    {
        $name = trim((string) $this->ket);

        return $name !== '' ? $name : (string) $this->getAuthIdentifier();
    }
}
