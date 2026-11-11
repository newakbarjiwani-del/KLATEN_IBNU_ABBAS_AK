<?php

namespace App\Tables;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

final class ColumnGroup
{
    /**
     * @param  array<string, array{sql: string, label: string}>  $columns
     */
    public static function key(Request $request, array $columns): ?string
    {
        $key = trim((string) $request->query('grup', ''));

        return array_key_exists($key, $columns) ? $key : null;
    }

    /**
     * @param  array<string, array{sql: string, label: string}>  $columns
     */
    public static function label(?string $key, array $columns): ?string
    {
        if ($key === null) {
            return null;
        }

        return $columns[$key]['label'];
    }

    /**
     * @param  array<string, array{sql: string, label: string}>  $columns
     */
    public static function order(Builder $query, ?string $key, array $columns): Builder
    {
        if ($key === null) {
            return $query;
        }

        return $query->orderBy($columns[$key]['sql']);
    }

    public static function text(object $row, string $key): string
    {
        $text = trim((string) ($row->{$key} ?? ''));

        if ($key === 'tgl') {
            return substr($text, 0, 10);
        }

        if ($key === 'UJIAN') {
            return match ($text) {
                'AKHIR' => 'Akhir',
                'MID' => 'Mid',
                default => $text,
            };
        }

        return $text;
    }
}
