<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class FooterManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Footer Manager';
    protected static ?string $navigationLabel = 'Footer';

    protected string $view = 'filament.pages.footer-manager';

    public array $data = [];
    public string $footer_about     = '';
    public string $footer_copyright = '';
    public string $footer_phone     = '';
    public string $footer_email     = '';
    public string $footer_address   = '';
    public string $footer_hours     = '';
    public array $shopLinks         = [];
    public array $supportLinks      = [];
    public array $legalLinks        = [];

    public function mount(): void
    {
        $this->footer_about     = Setting::get('footer.about', '') ?? '';
        $this->footer_copyright = Setting::get('footer.copyright', '© ' . date('Y') . ' MediNova Pharma. All rights reserved.') ?? '';
        $this->footer_phone     = Setting::get('contact.phone', '') ?? '';
        $this->footer_email     = Setting::get('contact.email', '') ?? '';
        $this->footer_address   = Setting::get('contact.address', '') ?? '';
        $this->footer_hours     = Setting::get('contact.hours', '') ?? '';

        $this->shopLinks    = json_decode(Setting::get('footer.shop_links', ''), true) ?: $this->defaultShopLinks();
        $this->supportLinks = json_decode(Setting::get('footer.support_links', ''), true) ?: $this->defaultSupportLinks();
        $this->legalLinks   = json_decode(Setting::get('footer.legal_links', ''), true) ?: $this->defaultLegalLinks();
    }

    private function defaultShopLinks(): array
    {
        return [
            ['label' => 'All Products', 'url' => '/products'],
            ['label' => 'New Arrivals', 'url' => '/products?sort=newest'],
            ['label' => 'Best Sellers', 'url' => '/products?sort=popular'],
            ['label' => 'On Sale', 'url' => '/products?on_sale=1'],
            ['label' => 'Upload Prescription', 'url' => '/prescriptions'],
        ];
    }

    private function defaultSupportLinks(): array
    {
        return [
            ['label' => 'Contact Us', 'url' => '/contact'],
            ['label' => 'About Us', 'url' => '/page/about-us'],
            ['label' => 'Shipping', 'url' => '/page/shipping-policy'],
            ['label' => 'Returns', 'url' => '/page/return-refund-policy'],
            ['label' => 'Track Order', 'url' => '/orders'],
        ];
    }

    private function defaultLegalLinks(): array
    {
        return [
            ['label' => 'Privacy Policy', 'url' => '/page/privacy-policy'],
            ['label' => 'Terms & Conditions', 'url' => '/page/terms-conditions'],
            ['label' => 'Shipping Policy', 'url' => '/page/shipping-policy'],
            ['label' => 'Refund Policy', 'url' => '/page/return-refund-policy'],
        ];
    }

    private function linkRepeater(string $label): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($label)
            ->label('')
            ->addActionLabel('Add Link')
            ->schema([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('label')
                        ->label('Text')->required()->maxLength(60),
                    Forms\Components\TextInput::make('url')
                        ->label('URL')->required()->maxLength(255)
                        ->placeholder('/page/about-us'),
                ]),
            ])
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'New Link')
            ->defaultItems(0);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Footer')->tabs([
                Tabs\Tab::make('About & Contact')->schema([
                    Section::make('Footer About')->schema([
                        Forms\Components\Textarea::make('footer_about')
                            ->label('About Text')->rows(4),
                    ]),
                    Section::make('Contact Info')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('footer_phone')->label('Phone Number'),
                            Forms\Components\TextInput::make('footer_email')->label('Email')->email(),
                            Forms\Components\TextInput::make('footer_address')->label('Address'),
                            Forms\Components\TextInput::make('footer_hours')->label('Business Hours'),
                        ]),
                    ]),
                    Section::make('Copyright')->schema([
                        Forms\Components\TextInput::make('footer_copyright')
                            ->label('Copyright Text')
                            ->helperText('Use {year} for dynamic year.'),
                    ]),
                ]),
                Tabs\Tab::make('Shop Links')->schema([
                    Section::make('Shop Column')->schema([
                        $this->linkRepeater('shopLinks'),
                    ]),
                ]),
                Tabs\Tab::make('Support Links')->schema([
                    Section::make('Support Column')->schema([
                        $this->linkRepeater('supportLinks'),
                    ]),
                ]),
                Tabs\Tab::make('Legal Links')->schema([
                    Section::make('Legal Column')->schema([
                        $this->linkRepeater('legalLinks'),
                    ]),
                ]),
            ]),
        ]);
    }

    public function save(): void
    {
        Setting::set('footer.about', $this->footer_about, 'footer');
        Setting::set('footer.copyright', $this->footer_copyright, 'footer');
        Setting::set('contact.phone', $this->footer_phone, 'footer');
        Setting::set('contact.email', $this->footer_email, 'footer');
        Setting::set('contact.address', $this->footer_address, 'footer');
        Setting::set('contact.hours', $this->footer_hours, 'footer');

        Setting::set('footer.shop_links', json_encode(array_values($this->shopLinks)), 'footer');
        Setting::set('footer.support_links', json_encode(array_values($this->supportLinks)), 'footer');
        Setting::set('footer.legal_links', json_encode(array_values($this->legalLinks)), 'footer');

        Notification::make()->title('Footer settings saved.')->success()->send();
    }
}
