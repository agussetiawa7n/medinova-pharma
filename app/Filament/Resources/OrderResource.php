<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Customers';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Order Summary')->schema([
                Grid::make(3)->schema([
                    Forms\Components\TextInput::make('order_number')->disabled(),
                    Forms\Components\Select::make('status')
                        ->options(OrderStatus::options())
                        ->required(),
                    Forms\Components\Select::make('payment_status')
                        ->options(PaymentStatus::options())
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('tracking_number'),
                    Forms\Components\TextInput::make('tracking_url'),
                ]),
                Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Order Items')
                ->schema(function ($record) {
                    if (!$record?->items?->count()) {
                        return [Forms\Components\Placeholder::make('empty')->content('No items found.')];
                    }
                    $fields = [];
                    foreach ($record->items as $i => $item) {
                        $fields[] = \Filament\Schemas\Components\Fieldset::make($item->product_name)
                            ->schema([
                                Forms\Components\Placeholder::make("_ri{$item->id}_sku")
                                    ->label('SKU')->content($item->product_sku),
                                Forms\Components\Placeholder::make("_ri{$item->id}_qty")
                                    ->label('Quantity')->content((string) $item->quantity),
                                Forms\Components\Placeholder::make("_ri{$item->id}_price")
                                    ->label('Price')->content('$'.number_format($item->unit_price, 2)),
                                Forms\Components\Placeholder::make("_ri{$item->id}_total")
                                    ->label('Line Total')->content('$'.number_format($item->total, 2)),
                            ]);
                    }
                    // Shipping + Tax summary
                    $fields[] = \Filament\Schemas\Components\Fieldset::make('Order Totals')
                        ->schema([
                            Forms\Components\Placeholder::make('order_subtotal')
                                ->label('Subtotal')->content('$'.number_format($record->subtotal, 2)),
                            Forms\Components\Placeholder::make('order_shipping')
                                ->label('Shipping Fee')->content('$'.number_format($record->shipping_amount, 2)),
                            Forms\Components\Placeholder::make('order_tax')
                                ->label(\App\Models\Setting::get('pricing.tax_label', 'Tax (18% GST)'))
                                ->content('$'.number_format($record->tax_amount, 2)),
                        ]);
                    return $fields;
                })
                ->collapsed(false),
            Section::make('Shipping Address')->schema([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('shipping_name'),
                    Forms\Components\TextInput::make('shipping_phone'),
                    Forms\Components\TextInput::make('shipping_address_line_1')->columnSpanFull(),
                    Forms\Components\TextInput::make('shipping_address_line_2')->columnSpanFull(),
                    Forms\Components\TextInput::make('shipping_city'),
                    Forms\Components\TextInput::make('shipping_state'),
                    Forms\Components\TextInput::make('shipping_postal_code'),
                    Forms\Components\TextInput::make('shipping_country'),
                ]),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('items_count')->label('Items')->counts('items'),
                Tables\Columns\TextColumn::make('total')->money('INR')->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn ($state) => $state?->label()),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(OrderStatus::options()),
                Tables\Filters\SelectFilter::make('payment_status')->options(PaymentStatus::options()),
                Tables\Filters\Filter::make('created_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                        ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                    ),
            ])
            ->recordActions([
                FilamentAction::make('view')
                    ->url(fn (Order $record) => static::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_shipped')
                        ->label('Mark as Shipped')
                        ->icon('heroicon-o-truck')
                        ->action(function ($records) {
                            $service = app(OrderService::class);
                            $records->each(fn ($order) => $service->updateStatus($order, OrderStatus::Shipped->value));
                            Notification::make()->title('Orders marked as shipped.')->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
            'view'   => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
