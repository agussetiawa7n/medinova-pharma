<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make()->tabs([
                Tab::make('General')->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(autoSlug()),
                        Forms\Components\TextInput::make('slug')
                            ->required()->maxLength(255)->unique(ignoreRecord: true),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(Category::pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->options(Brand::pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\TextInput::make('sku')->maxLength(100),
                        Forms\Components\TextInput::make('unit')->default('strip'),
                    ]),
                    Forms\Components\Textarea::make('short_description')->rows(2)->columnSpanFull(),
                    Forms\Components\RichEditor::make('description')->columnSpanFull(),
                ]),
                Tab::make('Pricing & Stock')->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()->prefix('$')->required(),
                        Forms\Components\TextInput::make('compare_price')
                            ->numeric()->prefix('$'),
                        Forms\Components\TextInput::make('cost_price')
                            ->numeric()->prefix('$'),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()->default(0),
                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->numeric()->default(5),
                        Forms\Components\TextInput::make('weight')
                            ->numeric()->suffix('g'),
                    ]),
                    Grid::make(3)->schema([
                        Forms\Components\Toggle::make('track_inventory')->default(true),
                        Forms\Components\Toggle::make('allow_backorder')->default(false),
                        Forms\Components\Toggle::make('requires_prescription')->default(false),
                    ]),
                ]),
                Tab::make('Media')->schema([
                    Forms\Components\FileUpload::make('thumbnail')
                        ->image()->disk('public')->directory('products/thumbnails')->columnSpanFull(),
                    Forms\Components\FileUpload::make('images')
                        ->image()->multiple()->disk('public')->directory('products/gallery')->columnSpanFull(),
                ]),
                Tab::make('Status & Visibility')->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Toggle::make('is_active')->default(true),
                        Forms\Components\Toggle::make('is_featured')->default(false),
                        Forms\Components\Toggle::make('is_new_arrival')->default(false),
                        Forms\Components\Toggle::make('is_best_seller')->default(false),
                    ]),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                ]),
                Tab::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(255),
                    Forms\Components\Textarea::make('meta_description')->rows(3),
                ]),
                Tab::make('Additional')->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('manufacturer'),
                        Forms\Components\TextInput::make('composition'),
                        Forms\Components\TextInput::make('storage_conditions'),
                        Forms\Components\DatePicker::make('expiry_date'),
                    ]),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->circular(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('category.name')->badge(),
                Tables\Columns\TextColumn::make('price')->money('INR')->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($record) => $record->stock_quantity <= $record->low_stock_threshold ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('requires_prescription')->boolean()->label('Rx'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\ToggleColumn::make('is_featured')->label('Featured'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('brand')->relationship('brand', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('requires_prescription')->label('Rx Required'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
