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

    public function system(): ?array
    {
        return self::SYSTEMS[$this->kelCode()] ?? null;
    }

    public function systemName(): string
    {
        return $this->system()['name'] ?? 'Tidak dikenal';
    }

    public function homeRoute(): ?string
    {
        return $this->system()['route'] ?? null;
    }

    public function displayName(): string
    {
        $name = trim((string) $this->ket);

        return $name !== '' ? $name : (string) $this->getAuthIdentifier();
    }
}
