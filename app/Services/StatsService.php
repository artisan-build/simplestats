<?php

namespace App\Services;

use Illuminate\Support\Str;

class StatsService
{
    public function safeDivision($numerator, $denominator): float|int
    {
        return $denominator == 0 ? 0 : $numerator / $denominator;
    }

    public function groupStats($stats)
    {
        $result = ['stats' => []];
        $statsPrefixes = ['lt_' => 'lifetime', 'pd_' => 'per_date'];

        foreach ($stats as $key => $value) {
            if (Str::startsWith($key, array_keys($statsPrefixes))) {
                $parts = explode('_', $key);
                $statsKey = $statsPrefixes[$parts[0].'_'];
                $result['stats'][$statsKey][$parts[1]] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function addStatsDetails(array $stats): array
    {
        // Lifetime
        $stats['lifetime']['NET'] = round($stats['lifetime']['NET'], 2);
        $stats['lifetime']['REG2DAU'] = round($this->safeDivision($stats['per_date']['DAU'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['REG2PU'] = round($this->safeDivision($stats['lifetime']['PU'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['REG2TAU'] = round($this->safeDivision($stats['lifetime']['TAU'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['ARPU'] = round($this->safeDivision($stats['lifetime']['NET'], $stats['lifetime']['REG']), 2);
        $stats['lifetime']['ARPPU'] = round($this->safeDivision($stats['lifetime']['NET'], $stats['lifetime']['PU']), 2);

        // Per Date
        $stats['per_date']['NET'] = round($stats['per_date']['NET'], 2);

        return $stats;
    }

    public function calcStatsTotal($stats, array &$totalStats): void
    {
        foreach ($stats as $statCategory => $statsGroup) {
            if (!is_array($statsGroup)) continue;
            foreach ($statsGroup as $key => $value) {
                $totalStats[$statCategory][$key] = ($totalStats[$statCategory][$key] ?? 0) + $value;
            }
        }
    }
}
