<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Soft-deleted products are excluded by the global scope, which also made
     * them unreachable from the panel — there was no way to see or restore one.
     * Dropping the scope here lets the Trashed filter decide instead; its
     * default is still "without trashed", so the normal view is unchanged.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'composition', 'manufacturer'];
    }

    /**
     * Global search inherits getEloquentQuery(), so dropping the soft-delete
     * scope above would otherwise surface deleted products in the panel-wide
     * search box. The table has the Trashed filter for that; search should not.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->withoutTrashed()->with('category');
    }

    /** @return array<string, string> */
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        // filled(), not array_filter()'s default truthiness — a stock of "0" is
        // exactly the detail worth showing, and "0" is falsy.
        return array_filter([
            'Category' => $record->category?->name ?? 'Uncategorised',
            'SKU'      => $record->sku,
            'Stock'    => (string) $record->stock_quantity,
        ], fn ($value): bool => filled($value));
    }

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
                        ->image()->disk('public')->directory('products/thumbnails')->columnSpanFull()
                        ->acceptedFileTypes(['image/jpeg','image/png','image/webp','image/gif']),
                    Forms\Components\FileUpload::make('images')
                        ->image()->multiple()->disk('public')->directory('products/gallery')->columnSpanFull()
                        ->acceptedFileTypes(['image/jpeg','image/png','image/webp','image/gif']),
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
                // Read through the model accessor rather than the raw column:
                // it resolves the public disk, passes absolute URLs straight
                // through, and falls back to the same placeholder the
                // storefront uses — so "no image" is visible here, not blank.
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('')
                    ->circular()
                    ->state(fn (Product $record): string => $record->thumbnail_url),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('medium')
                    // SKU under the name instead of its own column: it is what
                    // you search by, but rarely what you scan across a row.
                    ->description(fn (Product $record): string => $record->sku ?: '—'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->placeholder('Uncategorised')
                    ->color(fn ($state): string => filled($state) ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('composition')
                    ->label('Salt')
                    ->searchable()
                    ->limit(28)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable()
                    ->limit(24)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('price')->money('INR')->sortable(),

                // Was a plain number in red or green, so "8 in stock" and "0"
                // read the same at a glance. Say which of the three states it is.
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state, Product $record): string => match (true) {
                        (int) $state <= 0                          => 'Out of stock',
                        (int) $state <= $record->low_stock_threshold => $state . ' · low',
                        default                                     => (string) $state,
                    })
                    ->color(fn ($state, Product $record): string => match (true) {
                        (int) $state <= 0                          => 'danger',
                        (int) $state <= $record->low_stock_threshold => 'warning',
                        default                                     => 'success',
                    }),

                // The YMYL gate parks risky AI content as needs_review and hides
                // it from the storefront. Until now nothing in the table said so.
                Tables\Columns\TextColumn::make('content_status')
                    ->label('Content')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state === 'needs_review' ? 'Needs review' : 'Published')
                    ->color(fn (?string $state): string => $state === 'needs_review' ? 'warning' : 'success'),

                Tables\Columns\IconColumn::make('requires_prescription')->boolean()->label('Rx'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\ToggleColumn::make('is_featured')->label('Featured'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    // AI products land uncategorised when the model returns a
                    // category that does not exist; this is how you find them.
                    ->emptyRelationshipOptionLabel('Uncategorised'),

                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->emptyRelationshipOptionLabel('No brand'),

                Tables\Filters\SelectFilter::make('salt')
                    ->label('Composition (salt)')
                    ->relationship('compositionModel', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('content_status')
                    ->label('Content status')
                    ->options([
                        'published'    => 'Published',
                        'needs_review' => 'Needs review',
                    ]),

                Tables\Filters\SelectFilter::make('stock_level')
                    ->label('Stock level')
                    ->options([
                        'in'  => 'In stock',
                        'low' => 'Low stock',
                        'out' => 'Out of stock',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $value): Builder => match ($value) {
                            'in'    => $q->where('stock_quantity', '>', 0),
                            'low'   => $q->lowStock(),
                            'out'   => $q->where('stock_quantity', '<=', 0),
                            default => $q,
                        },
                    )),

                Tables\Filters\Filter::make('price_range')
                    ->label('Price range')
                    ->schema([
                        Forms\Components\TextInput::make('min')->label('Min price')->numeric()->prefix('₹'),
                        Forms\Components\TextInput::make('max')->label('Max price')->numeric()->prefix('₹'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['min'] ?? null, fn (Builder $q, $v): Builder => $q->where('price', '>=', $v))
                        ->when($data['max'] ?? null, fn (Builder $q, $v): Builder => $q->where('price', '<=', $v)))
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['min'] ?? null) && blank($data['max'] ?? null)) {
                            return null;
                        }

                        return 'Price ₹' . ($data['min'] ?? '0') . ' – ₹' . ($data['max'] ?? 'any');
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->label('Date added')
                    ->schema([
                        Forms\Components\DatePicker::make('from')->label('Added from'),
                        Forms\Components\DatePicker::make('until')->label('Added until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $v): Builder => $q->whereDate('created_at', '>=', $v))
                        ->when($data['until'] ?? null, fn (Builder $q, $v): Builder => $q->whereDate('created_at', '<=', $v)))
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['from'] ?? null) && blank($data['until'] ?? null)) {
                            return null;
                        }

                        return 'Added ' . ($data['from'] ?? 'any') . ' → ' . ($data['until'] ?? 'today');
                    }),

                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('requires_prescription')->label('Rx required'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\TernaryFilter::make('is_new_arrival')->label('New arrival'),
                Tables\Filters\TernaryFilter::make('is_best_seller')->label('Best seller'),

                // The two quality gaps worth a one-click sweep. Both are wrapped
                // in a nested where() so the OR cannot leak past the other
                // filters and widen the whole result set.
                Tables\Filters\Filter::make('missing_image')
                    ->label('Missing image')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where(
                        fn (Builder $q): Builder => $q->whereNull('thumbnail')->orWhere('thumbnail', ''),
                    )),

                Tables\Filters\Filter::make('missing_seo')
                    ->label('Missing SEO')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where(
                        fn (Builder $q): Builder => $q
                            ->whereNull('meta_title')->orWhere('meta_title', '')
                            ->orWhereNull('meta_description')->orWhere('meta_description', ''),
                    )),

                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(['sm' => 2, 'lg' => 3, 'xl' => 4])
            ->deferFilters()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->groups([
                Group::make('category.name')->label('Category')->collapsible(),
                Group::make('brand.name')->label('Brand')->collapsible(),
                Group::make('content_status')->label('Content status')->collapsible(),
            ])
            ->recordActions([
                Action::make('view_live')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Product $record): string => route('products.show', $record->slug))
                    ->openUrlInNewTab()
                    // The storefront route only serves active products, so an
                    // inactive one would open a 404. Hide the link instead.
                    ->visible(fn (Product $record): bool => $record->is_active && ! $record->trashed()),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => self::bulkUpdate($records, ['is_active' => true], 'activated'))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => self::bulkUpdate($records, ['is_active' => false], 'deactivated'))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('feature')
                        ->label('Mark as featured')
                        ->icon('heroicon-o-star')
                        ->action(fn ($records) => self::bulkUpdate($records, ['is_featured' => true], 'featured'))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unfeature')
                        ->label('Remove from featured')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(fn ($records) => self::bulkUpdate($records, ['is_featured' => false], 'unfeatured'))
                        ->deselectRecordsAfterCompletion(),

                    // The reason this exists: AI batches used to scatter products
                    // across categories, and fixing them one edit form at a time
                    // is the slowest job in the panel.
                    BulkAction::make('move_category')
                        ->label('Move to category')
                        ->icon('heroicon-o-folder-arrow-down')
                        ->schema([
                            Forms\Components\Select::make('category_id')
                                ->label('Category')
                                ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn ($records, array $data) => self::bulkUpdate(
                            $records,
                            ['category_id' => $data['category_id']],
                            'moved',
                        ))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('set_stock')
                        ->label('Set stock quantity')
                        ->icon('heroicon-o-cube')
                        ->schema([
                            Forms\Components\TextInput::make('stock_quantity')
                                ->label('Stock quantity')
                                ->numeric()
                                ->minValue(0)
                                ->default(100)
                                ->required(),
                        ])
                        ->action(fn ($records, array $data) => self::bulkUpdate(
                            $records,
                            ['stock_quantity' => (int) $data['stock_quantity']],
                            'restocked',
                        ))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('publish_content')
                        ->label('Mark content as reviewed')
                        ->icon('heroicon-o-shield-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('This publishes the medical content as-is. Only do it for products you have actually read.')
                        ->action(fn ($records) => self::bulkUpdate(
                            $records,
                            ['content_status' => 'published', 'is_active' => true],
                            'published',
                        ))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * One UPDATE for the whole selection instead of a save per record, and one
     * notification saying how many rows actually changed.
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $records
     * @param  array<string, mixed>  $values
     */
    private static function bulkUpdate($records, array $values, string $verb): void
    {
        $count = Product::whereKey($records->pluck('id'))->update($values);

        Notification::make()
            ->title("{$count} product(s) {$verb}.")
            ->success()
            ->send();
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
