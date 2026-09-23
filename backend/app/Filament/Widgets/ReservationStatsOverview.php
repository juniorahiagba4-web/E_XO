<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\Reservation;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReservationStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /** Statuses that represent money actually booked, not just quoted. */
    private const CONFIRMED_STATUSES = ['confirmed', 'deposit_paid', 'ongoing', 'completed'];

    private const LOW_STOCK_THRESHOLD = 15;

    protected function getStats(): array
    {
        $revenueThisMonth = Reservation::query()
            ->whereIn('status', self::CONFIRMED_STATUSES)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $pendingQuotes = Reservation::query()->where('status', 'quote_sent')->count();

        $confirmedThisMonth = Reservation::query()
            ->whereIn('status', self::CONFIRMED_STATUSES)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lowStockCount = Item::query()
            ->where('is_active', true)
            ->where('total_stock', '<', self::LOW_STOCK_THRESHOLD)
            ->count();

        return [
            Stat::make('Chiffre d\'affaires (ce mois)', number_format((float) $revenueThisMonth, 0, ',', ' ').' XOF')
                ->description('Locations et achats confirmés')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make('Devis en attente', (string) $pendingQuotes)
                ->description('Bloquent du stock pendant 48h')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),

            Stat::make('Commandes confirmées', (string) $confirmedThisMonth)
                ->description('Ce mois-ci')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('info'),

            Stat::make('Articles en stock bas', (string) $lowStockCount)
                ->description('Moins de '.self::LOW_STOCK_THRESHOLD.' unités')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
