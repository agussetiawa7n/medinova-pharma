<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Banners (Legacy)';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\Select::make('position')
                    ->options(['hero' => 'Hero', 'promo' => 'Promo', 'sidebar' => 'Sidebar'])
                    ->required()->default('hero'),
                Forms\Components\FileUpload::make('image')->image()->required()->disk('public')->directory('banners')->columnSpanFull(),
                Forms\Components\FileUpload::make('mobile_image')->image()->disk('public')->directory('banners')->label('Mobile Image'),
                Forms\Components\TextInput::make('link')->url()->label('Link URL'),
                Forms\Components\TextInput::make('button_text'),
                Forms\Components\Textarea::make('subtitle'),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                Forms\Components\DateTimePicker::make('starts_at')->label('Start Date'),
                Forms\Components\DateTimePicker::make('expires_at')->label('End Date'),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Toggle::make('open_in_new_tab')->label('Open in new tab'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('position')->badge(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->label('Expires'),
            ])
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit'   => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
