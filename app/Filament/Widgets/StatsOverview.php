<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $heading = null;

    protected function getStats(): array
    {
        $todayRevenue   = Order::whereDate('created_at', today())->where('payment_status', 'paid')->sum('total');
        $monthRevenue   = Order::whereMonth('created_at', now()->month)->where('payment_status', 'paid')->sum('total');
        $pendingOrders  = Order::where('status', 'pending')->count();
        $totalCustomers = User::count();
        $lowStock       = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0)->count();
        $outOfStock     = Product::where('stock_quantity', 0)->count();

        return [
            Stat::make("Today's Revenue", '₹' . number_format($todayRevenue, 2))
                ->description('Paid orders today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Monthly Revenue', '₹' . number_format($monthRevenue, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 10 ? 'danger' : 'warning'),

            Stat::make('Total Customers', $totalCustomers)
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Low Stock Products', $lowStock)
                ->description("Out of stock: {$outOfStock}")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'warning' : 'success'),
        ];
    }
}
