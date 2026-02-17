<?php

namespace App\Support;

use App\Models\Resor;

class ResorUserResolver
{
    public static function isScopedUsername(?string $username): bool
    {
        return (bool) preg_match('/^\s*LAA\b/i', (string) $username);
    }

    public static function resolveAllowedResorId(?string $username): ?int
    {
        $username = trim((string) $username);
        if ($username === '') {
            return null;
        }

        $suffix = self::extractSuffix($username);
        if (! $suffix) {
            return null;
        }

        $map = [
            'RK' => ['rangkas', 'rangkasbitung'],
            'DP' => ['depok'],
            'NMO' => ['nambo'],
            'BOO' => ['bogor', 'bojong'],
            'THB' => ['tanah abang', 'tanahabang'],
            'PRP' => ['parung panjang', 'parungpanjang'],
            'PSM' => ['pasar minggu', 'pasarminggu'],
        ];

        $candidates = $map[$suffix] ?? [];
        if (empty($candidates)) {
            // Fallback: coba dari suffix apa adanya jika belum ada mapping.
            $candidates[] = strtolower($suffix);
        }

        foreach ($candidates as $keyword) {
            $resorId = Resor::query()
                ->whereRaw('LOWER(nama) LIKE ?', ['%' . strtolower($keyword) . '%'])
                ->value('id');

            if ($resorId) {
                return (int) $resorId;
            }
        }

        return null;
    }

    private static function extractSuffix(string $username): ?string
    {
        $parts = preg_split('/\s+/', trim($username)) ?: [];
        if (empty($parts)) {
            return null;
        }

        return strtoupper((string) end($parts));
    }
}
