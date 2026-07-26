<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('ai_generate')
                ->label('AI Generate')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->url(\App\Filament\Pages\AIGenerateProducts::getUrl()),
        ];
    }

    /**
     * Saved views for the states an admin actually chases day to day.
     *
     * These are deliberately the things a filter cannot express in one click:
     * anything the AI pipeline parked for review, anything missing a photo, and
     * the two stock states that cost sales. Category and brand stay in the
     * filter panel, where multi-select belongs.
     *
     * Badges are deferred so the page paints before seven COUNT queries run —
     * shared hosting has a small PHP process pool and this page is opened often.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'active' => Tab::make('Active')
                ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                ->badge(fn (): int => Product::where('is_active', true)->count())
                ->deferBadge(),

            'inactive' => Tab::make('Inactive')
                ->query(fn (Builder $query): Builder => $query->where('is_active', false))
                ->badge(fn (): int => Product::where('is_active', false)->count())
                ->badgeColor('gray')
                ->deferBadge(),

            'needs_review' => Tab::make('Needs review')
                ->query(fn (Builder $query): Builder => $query->where('content_status', 'needs_review'))
                ->badge(fn (): int => Product::where('content_status', 'needs_review')->count())
                ->badgeColor('warning')
                ->deferBadge(),

            'no_image' => Tab::make('No image')
                ->query(fn (Builder $query): Builder => $query->where(
                    fn (Builder $q): Builder => $q->whereNull('thumbnail')->orWhere('thumbnail', ''),
                ))
                ->badge(fn (): int => Product::where(
                    fn (Builder $q): Builder => $q->whereNull('thumbnail')->orWhere('thumbnail', ''),
                )->count())
                ->badgeColor('danger')
                ->deferBadge(),

            'uncategorised' => Tab::make('Uncategorised')
                ->query(fn (Builder $query): Builder => $query->whereNull('category_id'))
                ->badge(fn (): int => Product::whereNull('category_id')->count())
                ->badgeColor('warning')
                ->deferBadge(),

            'low_stock' => Tab::make('Low stock')
                ->query(fn (Builder $query): Builder => $query->lowStock())
                ->badge(fn (): int => Product::lowStock()->count())
                ->badgeColor('warning')
                ->deferBadge(),

            'out_of_stock' => Tab::make('Out of stock')
                ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<=', 0))
                ->badge(fn (): int => Product::where('stock_quantity', '<=', 0)->count())
                ->badgeColor('danger')
                ->deferBadge(),
        ];
    }
}
