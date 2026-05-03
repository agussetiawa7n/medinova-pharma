<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Single grouped query — 1 query for 30 days instead of 30
        $revenues = Order::where('created_at', '>=', now()->subDays(30))
            ->where('payment_status', 'paid')
            ->selectRaw("DATE(created_at) as date, SUM(total) as revenue")
            ->groupBy('date')
            ->pluck('revenue', 'date');

        $data = collect(range(29, 0))->map(function ($day) use ($revenues) {
            $date = now()->subDays($day)->toDateString();
            return [
                'date'    => Carbon::parse($date)->format('M d'),
                'revenue' => $revenues[$date] ?? 0,
            ];
        });

        return [
            'datasets' => [[
                'label'           => 'Revenue ($)',
                'data'            => $data->pluck('revenue')->toArray(),
            ]],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
