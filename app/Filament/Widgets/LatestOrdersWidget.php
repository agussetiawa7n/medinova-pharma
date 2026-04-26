<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Orders';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::with('user')->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->fontFamily('mono')->copyable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => $state->color()),
                Tables\Columns\TextColumn::make('total')->money('INR'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->paginated(false);
    }
}
