<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShippingManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Customers';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Shipping Fee';
    protected static ?string $navigationLabel = 'Shipping Fee';

    protected string $view = 'filament.pages.shipping-manager';

    public array $data = [];

    public int $free_shipping_threshold = 499;
    public int $delivery_fee = 50;
    public string $shipping_policy_text = '';
    public int $express_delivery_fee = 100;
    public int $same_day_delivery_fee = 200;
    public bool $express_delivery_enabled = false;
    public bool $same_day_delivery_enabled = false;

    public function mount(): void
    {
        $this->free_shipping_threshold = (int) Setting::get('pricing.free_shipping_threshold', 499);
        $this->delivery_fee            = (int) Setting::get('pricing.delivery_fee', 50);
        $this->express_delivery_fee    = (int) Setting::get('pricing.express_delivery_fee', 100);
        $this->same_day_delivery_fee   = (int) Setting::get('pricing.same_day_delivery_fee', 200);
        $this->express_delivery_enabled = (bool) Setting::get('pricing.express_delivery_enabled', false);
        $this->same_day_delivery_enabled = (bool) Setting::get('pricing.same_day_delivery_enabled', false);
        $this->shipping_policy_text    = Setting::get('pricing.shipping_policy_text', '') ?? '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Standard Delivery')->schema([
                Forms\Components\TextInput::make('delivery_fee')
                    ->label('Standard Delivery Fee ($)')
                    ->numeric()->required()->minValue(0)
                    ->helperText('Charged on all orders unless free shipping threshold is met.'),
                Forms\Components\TextInput::make('free_shipping_threshold')
                    ->label('Free Shipping Threshold ($)')
                    ->numeric()->required()->minValue(0)
                    ->helperText('Orders at or above this amount get free standard delivery. Set to 999999 to effectively disable free shipping.'),
            ])->columns(2),
            Section::make('Express & Same-Day Delivery')->schema([
                Forms\Components\Toggle::make('express_delivery_enabled')
                    ->label('Express Delivery Available')
                    ->helperText('Enable faster delivery option at checkout.'),
                Forms\Components\TextInput::make('express_delivery_fee')
                    ->label('Express Delivery Fee ($)')
                    ->numeric()->minValue(0),
                Forms\Components\Toggle::make('same_day_delivery_enabled')
                    ->label('Same-Day Delivery Available')
                    ->helperText('Enable same-day delivery for eligible locations.'),
                Forms\Components\TextInput::make('same_day_delivery_fee')
                    ->label('Same-Day Delivery Fee ($)')
                    ->numeric()->minValue(0),
            ])->columns(2),
            Section::make('Shipping Policy')->schema([
                Forms\Components\Textarea::make('shipping_policy_text')
                    ->label('Shipping Policy Summary')
                    ->rows(4)->maxLength(500)
                    ->helperText('Displayed on checkout/cart pages. Optional.'),
            ]),
        ]);
    }

    public function save(): void
    {
        Setting::set('pricing.delivery_fee', (string) $this->delivery_fee, 'pricing');
        Setting::set('pricing.free_shipping_threshold', (string) $this->free_shipping_threshold, 'pricing');
        Setting::set('pricing.express_delivery_fee', (string) $this->express_delivery_fee, 'pricing');
        Setting::set('pricing.same_day_delivery_fee', (string) $this->same_day_delivery_fee, 'pricing');
        Setting::set('pricing.express_delivery_enabled', (bool) $this->express_delivery_enabled, 'pricing', 'boolean');
        Setting::set('pricing.same_day_delivery_enabled', (bool) $this->same_day_delivery_enabled, 'pricing', 'boolean');
        Setting::set('pricing.shipping_policy_text', $this->shipping_policy_text, 'pricing');

        Notification::make()->title('Shipping settings saved.')->success()->send();
    }
}
