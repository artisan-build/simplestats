<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TransactionStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = Transaction::query()
            ->where('status', 'completed')
            ->select([
                DB::raw('COALESCE(SUM(CAST(total AS numeric)), 0) as total_gross'),
                DB::raw('COALESCE(SUM(CAST(total AS numeric) - CAST(tax AS numeric)), 0) as total_net'),
                DB::raw('COALESCE(MAX(CAST(total AS numeric)), 0) as max_gross'),
                DB::raw('COUNT(*) as transaction_count'),
            ])
            ->first();

        return [
            Stat::make('Umsatz Brutto', '$'.number_format($stats->total_gross / 100, 2)),
            Stat::make('Anzahl Transaktionen', number_format($stats->transaction_count)),
            Stat::make('Umsatz Netto', '$'.number_format($stats->total_net / 100, 2)),
            Stat::make('Max Brutto (Einzeln)', '$'.number_format($stats->max_gross / 100, 2)),
        ];
    }
}
