<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;

class HomepageManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Homepage Manager';
    protected static ?string $navigationLabel = 'Sections';

    protected string $view = 'filament.pages.homepage-manager';

    // ---- Section toggles ----
    public bool $home_show_feature_strip   = true;
    public bool $home_show_categories      = true;
    public bool $home_show_flash_sale      = true;
    public bool $home_show_promo_banners   = true;
    public bool $home_show_featured        = true;
    public bool $home_show_prescription_cta= true;
    public bool $home_show_new_arrivals    = true;
    public array $data = [];
    public bool $home_show_best_sellers    = true;
    public bool $home_show_faq             = true;

    // ---- Hero ----
    public string $hero_title       = 'Your Health, Our Priority';
    public string $hero_subtitle    = 'Genuine medicines, vitamins and wellness essentials delivered safely to your doorstep.';
    public string $hero_button_text = 'Shop Now';
    public string $hero_button_url  = '/products';
    public $hero_background = [];

    public function dehydrate(): void
    {
        if (is_string($this->hero_background)) {
            $this->hero_background = [$this->hero_background];
        }
        if ($this->hero_background === null) {
            $this->hero_background = [];
        }
    }

    // ---- Feature strip (comma-separated) ----
    public string $feature_1 = '100% Genuine Medicines';
    public string $feature_2 = 'Customer Satisfaction';
    public string $feature_3 = 'Trusted Pharmacy';
    public string $feature_4 = 'Fast Home Delivery';
    public string $feature_5 = 'Expert Pharmacist Support';

    // ---- Shop by Category ----
    public array $home_category_ids = [];
    public int $home_category_count = 12;

    // ---- Promo Banners ----
    public array $promoBanners      = [];
    public array $promoLeftBanner   = ['title' => '', 'subtitle' => '', 'url' => '', 'image' => ''];
    public array $promoRightBanner  = ['title' => '', 'subtitle' => '', 'url' => '', 'image' => ''];

    // ---- FAQ ----
    public array $home_faqs = [];

    // ---- Prescription CTA ----
    public string $rx_cta_title     = "Upload your prescription, we'll do the rest";
    public string $rx_cta_subtitle  = 'Quick verification by certified pharmacists. Get prescription medicines delivered safely to your doorstep.';

    public function mount(): void
    {
        // Section toggles
        $this->home_show_feature_strip    = (bool) Setting::get('home.show_feature_strip', true);
        $this->home_show_categories       = (bool) Setting::get('home.show_categories', true);
        $this->home_show_flash_sale       = (bool) Setting::get('home.show_flash_sale', true);
        $this->home_show_promo_banners    = (bool) Setting::get('home.show_promo_banners', true);
        $this->home_show_featured         = (bool) Setting::get('home.show_featured', true);
        $this->home_show_prescription_cta = (bool) Setting::get('home.show_prescription_cta', true);
        $this->home_show_new_arrivals     = (bool) Setting::get('home.show_new_arrivals', true);
        $this->home_show_best_sellers     = (bool) Setting::get('home.show_best_sellers', true);
        $this->home_show_faq              = (bool) Setting::get('home.show_faq', true);

        // Hero
        $this->hero_title       = Setting::get('home.hero_title', 'Your Health, Our Priority') ?? '';
        $this->hero_subtitle    = Setting::get('home.hero_subtitle', 'Genuine medicines, vitamins and wellness essentials delivered safely to your doorstep.') ?? '';
        $this->hero_button_text = Setting::get('home.hero_button_text', 'Shop Now') ?? '';
        $this->hero_button_url  = Setting::get('home.hero_button_url', '/products') ?? '';
        $existing = Setting::get('home.hero_background');
        $this->hero_background = $existing ? [$existing] : [];

        // Features
        $this->feature_1 = Setting::get('home.feature_1', '100% Genuine Medicines') ?? '';
        $this->feature_2 = Setting::get('home.feature_2', 'Customer Satisfaction') ?? '';
        $this->feature_3 = Setting::get('home.feature_3', 'Trusted Pharmacy') ?? '';
        $this->feature_4 = Setting::get('home.feature_4', 'Fast Home Delivery') ?? '';
        $this->feature_5 = Setting::get('home.feature_5', 'Expert Pharmacist Support') ?? '';

        // Categories
        $this->home_category_ids   = json_decode(Setting::get('home.category_ids', '[]'), true) ?: [];
        $this->home_category_count = (int) Setting::get('home.category_count', 12);

        // Promo Banners
        $this->promoBanners     = json_decode(Setting::get('home.promo_banners', '[]'), true) ?: [];
        $this->promoLeftBanner  = json_decode(Setting::get('home.promo_left_banner', '{}'), true) ?: ['title' => '', 'subtitle' => '', 'url' => '', 'image' => ''];
        $this->promoRightBanner = json_decode(Setting::get('home.promo_right_banner', '{}'), true) ?: ['title' => '', 'subtitle' => '', 'url' => '', 'image' => ''];

        // FAQ
        $this->home_faqs = json_decode(Setting::get('home.faqs', ''), true) ?: [
            ['question' => 'Are all medicines on MediNova Pharma 100% genuine?', 'answer' => 'Yes — every product is sourced directly from licensed manufacturers and authorised distributors. We operate as a registered pharmacy and provide an authenticity guarantee on every order, with batch-level traceability.'],
            ['question' => 'How do I order prescription medicines?', 'answer' => 'Simply upload a clear photo or PDF of your prescription at checkout or from your dashboard. Our licensed pharmacists verify it within 2–4 hours during business hours, and dispatch your order as soon as it is approved.'],
            ['question' => 'What is the delivery timeline?', 'answer' => 'Metro cities: 1–2 business days. Tier 2 cities: 2–4 business days. Express 4-hour delivery is available in select areas for an additional fee. You\'ll receive SMS & email tracking updates once shipped.'],
            ['question' => 'What is your return policy?', 'answer' => 'Sealed, unopened OTC products can be returned within 7 days of delivery. Prescription medicines, opened products, cold-chain items, and personal-care products are non-returnable for safety and hygiene reasons.'],
            ['question' => 'Do you accept cash on delivery?', 'answer' => 'Yes — COD is available on orders up to $5,000 in most serviceable pincodes. We also accept UPI, credit/debit cards, net banking, and MediNova Wallet. All transactions are secured with 256-bit SSL encryption.'],
            ['question' => 'Are your products cruelty-free and safe?', 'answer' => 'All medicines are manufactured per the Indian Pharmacopoeia and stored under controlled temperature. Personal-care and wellness brands on our platform explicitly state their cruelty-free/vegan certifications on the product page.'],
        ];

        // Rx CTA
        $this->rx_cta_title    = Setting::get('home.rx_cta_title', "Upload your prescription, we'll do the rest") ?? '';
        $this->rx_cta_subtitle = Setting::get('home.rx_cta_subtitle', 'Quick verification by certified pharmacists.') ?? '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Homepage')->tabs([
                Tabs\Tab::make('Sections')->schema([
                    Section::make('Visibility Toggles')->schema([
                        Grid::make(3)->schema([
                            Forms\Components\Toggle::make('home_show_feature_strip')->label('Feature Strip'),
                            Forms\Components\Toggle::make('home_show_categories')->label('Shop by Category'),
                            Forms\Components\Toggle::make('home_show_flash_sale')->label('Flash Sale'),
                            Forms\Components\Toggle::make('home_show_promo_banners')->label('Promo Banners'),
                            Forms\Components\Toggle::make('home_show_featured')->label('Featured Products'),
                            Forms\Components\Toggle::make('home_show_prescription_cta')->label('Prescription CTA'),
                            Forms\Components\Toggle::make('home_show_new_arrivals')->label('New Arrivals'),
                            Forms\Components\Toggle::make('home_show_best_sellers')->label('Best Sellers'),
                            Forms\Components\Toggle::make('home_show_faq')->label('FAQ Section'),
                        ]),
                    ]),
                ]),
                Tabs\Tab::make('Hero')->schema([
                    Forms\Components\TextInput::make('hero_title')->label('Title')->required(),
                    Forms\Components\Textarea::make('hero_subtitle')->label('Subtitle')->rows(3),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('hero_button_text')->label('Button Text'),
                        Forms\Components\TextInput::make('hero_button_url')->label('Button URL (e.g. /products)'),
                    ]),
                    Forms\Components\FileUpload::make('hero_background')->label('Background Image')->image()->directory('homepage')->disk('public')
                        ->formatStateUsing(function ($state) {
                            if (is_array($state) && count($state) === 1) {
                                $first = reset($state);
                                if (is_string($first)) return $first;
                            }
                            if (empty($state)) {
                                $existing = Setting::get('home.hero_background');
                                return $existing ?: null;
                            }
                            return $state;
                        }),
                ]),
                Tabs\Tab::make('Feature Strip')->schema([
                    Forms\Components\TextInput::make('feature_1')->label('Feature 1'),
                    Forms\Components\TextInput::make('feature_2')->label('Feature 2'),
                    Forms\Components\TextInput::make('feature_3')->label('Feature 3'),
                    Forms\Components\TextInput::make('feature_4')->label('Feature 4'),
                    Forms\Components\TextInput::make('feature_5')->label('Feature 5'),
                ]),
                Tabs\Tab::make('Categories')->schema([
                    Section::make('Shop by Category')->schema([
                        Forms\Components\Select::make('home_category_ids')
                            ->label('Featured Categories')
                            ->options(fn () => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->helperText('Select which categories appear on the homepage. Leave empty to show all.'),
                        Forms\Components\TextInput::make('home_category_count')
                            ->label('Categories to Show')
                            ->numeric()->minValue(4)->maxValue(24)->step(2)
                            ->helperText('Number of categories displayed (default: 12).'),
                    ]),
                ]),
                Tabs\Tab::make('Promo Banners')->schema([
                    Section::make('Promo Banner Cards (3-column grid)')->schema([
                        Forms\Components\Repeater::make('promoBanners')
                            ->label('')
                            ->addActionLabel('Add Promo Banner')
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('title')->label('Title')->required()->maxLength(100),
                                    Forms\Components\TextInput::make('badge_text')->label('Badge')->maxLength(30),
                                ]),
                                Forms\Components\Textarea::make('subtitle')->label('Subtitle')->rows(2),
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('button_text')->label('Button Text')->maxLength(30),
                                    Forms\Components\TextInput::make('button_url')->label('Button URL')->placeholder('/products?on_sale=1'),
                                ]),
                                Forms\Components\FileUpload::make('image')->label('Background Image')->image()->directory('promo-banners')->disk('public'),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Banner')
                            ->defaultItems(0),
                    ]),
                ]),
                Tabs\Tab::make('Promo Grid')->schema([
                    Section::make('Left Banner')->schema([
                        Forms\Components\TextInput::make('promoLeftBanner.title')->label('Title')->required(),
                        Forms\Components\TextInput::make('promoLeftBanner.subtitle')->label('Subtitle'),
                        Forms\Components\TextInput::make('promoLeftBanner.url')->label('Link URL')->placeholder('/products?on_sale=1'),
                        Forms\Components\FileUpload::make('promoLeftBanner.image')->label('Background Image')->image()->directory('promo-grid')->disk('public'),
                    ]),
                    Section::make('Right Banner')->schema([
                        Forms\Components\TextInput::make('promoRightBanner.title')->label('Title')->required(),
                        Forms\Components\TextInput::make('promoRightBanner.subtitle')->label('Subtitle'),
                        Forms\Components\TextInput::make('promoRightBanner.url')->label('Link URL')->placeholder('/products?category=vitamins-supplements'),
                        Forms\Components\FileUpload::make('promoRightBanner.image')->label('Background Image')->image()->directory('promo-grid')->disk('public'),
                    ]),
                ]),
                Tabs\Tab::make('FAQ')->schema([
                    Section::make('Frequently Asked Questions')->schema([
                        Forms\Components\Repeater::make('home_faqs')
                            ->label('')
                            ->addActionLabel('Add Question')
                            ->schema([
                                Forms\Components\TextInput::make('question')->label('Question')->required()->maxLength(255),
                                Forms\Components\Textarea::make('answer')->label('Answer')->required()->rows(3)->maxLength(1000),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => \Illuminate\Support\Str::limit($state['question'] ?? 'New Question', 60))
                            ->defaultItems(0),
                    ]),
                ]),
                Tabs\Tab::make('Prescription CTA')->schema([
                    Forms\Components\TextInput::make('rx_cta_title')->label('CTA Title'),
                    Forms\Components\Textarea::make('rx_cta_subtitle')->label('CTA Subtitle')->rows(3),
                ]),
            ]),
        ]);
    }

    public function save(): void
    {
        // Toggles
        foreach ([
            'home_show_feature_strip', 'home_show_categories', 'home_show_flash_sale',
            'home_show_promo_banners', 'home_show_featured', 'home_show_prescription_cta',
            'home_show_new_arrivals', 'home_show_best_sellers', 'home_show_faq',
        ] as $prop) {
            $key = 'home.'.str_replace('home_show_', 'show_', $prop);
            Setting::set($key, (bool) $this->{$prop}, 'homepage', 'boolean');
        }

        // Hero
        Setting::set('home.hero_title', $this->hero_title, 'homepage');
        Setting::set('home.hero_subtitle', $this->hero_subtitle, 'homepage');
        Setting::set('home.hero_button_text', $this->hero_button_text, 'homepage');
        Setting::set('home.hero_button_url', $this->hero_button_url, 'homepage');
        if (is_array($this->hero_background) && !empty($this->hero_background)) {
            foreach ($this->hero_background as $k => $v) {
                if ($v instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $path = $v->store('homepage', 'public');
                    Setting::set('home.hero_background', $path, 'homepage');
                    $this->hero_background[$k] = $path;
                    break;
                }
            }
        }

        // Features
        for ($i = 1; $i <= 5; $i++) {
            Setting::set("home.feature_{$i}", $this->{"feature_{$i}"}, 'homepage');
        }

        // Categories
        Setting::set('home.category_ids', json_encode(array_values($this->home_category_ids)), 'homepage');
        Setting::set('home.category_count', $this->home_category_count, 'homepage');

        // Promo Banners
        Setting::set('home.promo_banners', json_encode(array_values($this->promoBanners)), 'homepage');
        Setting::set('home.promo_left_banner', json_encode($this->promoLeftBanner), 'homepage');
        Setting::set('home.promo_right_banner', json_encode($this->promoRightBanner), 'homepage');

        // FAQ
        Setting::set('home.faqs', json_encode(array_values($this->home_faqs)), 'homepage');

        // Rx CTA
        Setting::set('home.rx_cta_title', $this->rx_cta_title, 'homepage');
        Setting::set('home.rx_cta_subtitle', $this->rx_cta_subtitle, 'homepage');

        Notification::make()->title('Homepage settings saved.')->success()->send();
    }
}
