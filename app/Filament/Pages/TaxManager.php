<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaxManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Customers';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Tax';
    protected static ?string $navigationLabel = 'Tax';

    protected string $view = 'filament.pages.tax-manager';

    public array $data = [];

    public int $tax_rate = 18;
    public string $tax_name = 'GST';
    public string $tax_label = 'Tax (18% GST)';
    public string $tax_number = '';
    public bool $tax_inclusive = false;
    public bool $show_tax_breakdown = true;

    public function mount(): void
    {
        $this->tax_rate            = (int) Setting::get('pricing.tax_rate', 18);
        $this->tax_name            = Setting::get('pricing.tax_name', 'GST') ?? 'GST';
        $this->tax_label           = Setting::get('pricing.tax_label', 'Tax (18% GST)') ?? 'Tax (18% GST)';
        $this->tax_number          = Setting::get('pricing.tax_number', '') ?? '';
        $this->tax_inclusive       = (bool) Setting::get('pricing.tax_inclusive', false);
        $this->show_tax_breakdown  = (bool) Setting::get('pricing.show_tax_breakdown', true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Tax Configuration')->schema([
                Forms\Components\TextInput::make('tax_name')
                    ->label('Tax Name')->required()->maxLength(20)
                    ->helperText('e.g. GST, VAT, HST, Sales Tax.'),
                Forms\Components\TextInput::make('tax_rate')
                    ->label('Tax Rate (%)')->numeric()->required()->minValue(0)->maxValue(100)
                    ->helperText('Percentage applied to order subtotal (after discount, before shipping).'),
                Forms\Components\TextInput::make('tax_label')
                    ->label('Display Label')->maxLength(50)
                    ->helperText('Shown on checkout, invoices, and order details. e.g. "Tax (18% GST)"'),
            ])->columns(3),
            Section::make('Business Details')->schema([
                Forms\Components\TextInput::make('tax_number')
                    ->label('Tax Registration Number')->maxLength(50)
                    ->helperText('GSTIN / VAT Number / HST Number — shown on invoices.'),
                Forms\Components\Toggle::make('tax_inclusive')
                    ->label('Prices Include Tax')
                    ->helperText('If ON, displayed prices already include tax. Tax is NOT added separately at checkout.')
                    ->live(),
                Forms\Components\Toggle::make('show_tax_breakdown')
                    ->label('Show Tax Breakdown')
                    ->helperText('Show tax as a separate line item on invoices and checkout.'),
            ])->columns(2),
        ]);
    }

    public function save(): void
    {
        Setting::set('pricing.tax_rate', (string) $this->tax_rate, 'pricing');
        Setting::set('pricing.tax_name', $this->tax_name, 'pricing');
        Setting::set('pricing.tax_label', $this->tax_label, 'pricing');
        Setting::set('pricing.tax_number', $this->tax_number, 'pricing');
        Setting::set('pricing.tax_inclusive', (bool) $this->tax_inclusive, 'pricing', 'boolean');
        Setting::set('pricing.show_tax_breakdown', (bool) $this->show_tax_breakdown, 'pricing', 'boolean');

        Notification::make()->title('Tax settings saved.')->success()->send();
    }
}
