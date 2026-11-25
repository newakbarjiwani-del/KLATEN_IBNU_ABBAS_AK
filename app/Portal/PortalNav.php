<?php

namespace App\Portal;

use App\Akademik\AkademikMenu;
use App\Kesantrian\KesantrianMenu;
use App\Models\CyberKey;
use App\Tahfidz\TahfidzMenu;

class PortalNav
{
    /**
     * @return array<int, array{name: string, menu: string, match: string, groups: array<string, array<int, array{slug: string, label: string, summary: string}>>}>
     */
    public static function sections(CyberKey $account): array
    {
        $sections = [];

        foreach ($account->accessIds() as $id) {
            $access = CyberKey::ACCESS[$id];
            $groups = match ($access['kel']) {
                'admkes' => KesantrianMenu::groups(),
                'admtah' => TahfidzMenu::groups(),
                'admaka' => AkademikMenu::groups(),
                default => [],
            };

            $sections[] = [
                'name' => $access['name'],
                'menu' => match ($access['kel']) {
                    'admkes' => 'kesantrian.menu',
                    'admtah' => 'tahfidz.menu',
                    'admaka' => 'akademik.menu',
                    default => 'portal.home',
                },
                'match' => match ($access['kel']) {
                    'admkes' => 'kesantrian.*',
                    'admtah' => 'tahfidz.*',
                    'admaka' => 'akademik.*',
                    default => 'portal.home',
                },
                'groups' => $groups,
            ];
        }

        return $sections;
    }
}
