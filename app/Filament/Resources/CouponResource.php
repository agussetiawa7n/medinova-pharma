<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('code')
                    ->required()->maxLength(50)->uppercase(),
                Forms\Components\Select::make('type')
                    ->options(['fixed' => 'Fixed Amount', 'percentage' => 'Percentage', 'free_shipping' => 'Free Shipping'])
                    ->required(),
                Forms\Components\TextInput::make('value')->numeric()->required(),
                Forms\Components\TextInput::make('min_order_amount')->numeric()->prefix('$')->default(0),
                Forms\Components\TextInput::make('max_discount_amount')->numeric()->prefix('$')->nullable(),
                Forms\Components\TextInput::make('usage_limit')->numeric()->nullable(),
                Forms\Components\TextInput::make('usage_limit_per_user')->numeric()->default(1),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\DateTimePicker::make('starts_at'),
                Forms\Components\DateTimePicker::make('expires_at'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->badge()->color('primary'),
                Tables\Columns\TextColumn::make('type')->formatStateUsing(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->badge(),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('used_count')->label('Used'),
                Tables\Columns\TextColumn::make('usage_limit')->label('Limit'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->label('Expires'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit'   => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
