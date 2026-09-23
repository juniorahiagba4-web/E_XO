<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = "Chiffre d'affaires — 14 derniers jours";

    private const CONFIRMED_STATUSES = ['confirmed', 'deposit_paid', 'ongoing', 'completed'];

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $i) => Carbon::today()->subDays($i));

        $totals = $days->map(fn (Carbon $day) => Reservation::query()
            ->whereIn('status', self::CONFIRMED_STATUSES)
            ->whereDate('created_at', $day)
            ->sum('total'));

        return [
            'datasets' => [
                [
                    'label' => 'XOF',
                    'data' => $totals->values()->all(),
                    'borderColor' => '#f0b429',
                    'backgroundColor' => 'rgba(240, 180, 41, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (Carbon $d) => $d->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
