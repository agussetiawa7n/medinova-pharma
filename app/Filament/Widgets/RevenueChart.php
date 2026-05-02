<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue (Last 30 Days)';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $data = collect(range(29, 0))->map(function ($day) {
            $date = now()->subDays($day)->toDateString();

            return [
                'date'    => Carbon::parse($date)->format('M d'),
                'revenue' => Order::whereDate('created_at', $date)
                    ->where('payment_status', 'paid')
                    ->sum('total'),
            ];
        });

        return [
            'datasets' => [[
                'label'           => 'Revenue ($)',
                'data'            => $data->pluck('revenue')->toArray(),
                'borderColor'     => '#FF647B',
                'backgroundColor' => 'rgba(255, 100, 123, 0.15)',
                'fill'            => true,
                'tension'         => 0.4,
            ]],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
