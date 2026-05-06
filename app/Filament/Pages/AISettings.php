<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\AIProductService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AISettings extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'AI Settings';
    protected static ?string $navigationLabel = 'AI Settings';

    protected string $view = 'filament.pages.ai-settings';

    public array $data = [];

    public string $openrouter_api_key = '';
    public string $text_model         = 'openai/gpt-5-mini';
    public string $image_model        = 'openai/gpt-5-image-mini';
    public float  $ai_temperature     = 0.7;
    public int    $ai_max_tokens      = 2000;
    public bool   $generate_images    = true;
    public bool $gen_description       = true;
    public bool $gen_short_description = true;
    public bool $gen_price             = true;
    public bool $gen_compare_price     = true;
    public bool $gen_category          = true;
    public bool $gen_brand             = true;
    public bool $gen_tags              = true;
    public bool $gen_composition       = true;
    public bool $gen_manufacturer      = true;
    public bool $gen_storage           = true;
    public bool $gen_sku               = true;
    public bool $gen_meta_title        = true;
    public bool $gen_meta_description  = true;
    public bool $gen_unit              = true;
    public bool $gen_weight            = true;

    public function mount(): void
    {
        $this->openrouter_api_key = Setting::get('ai.openrouter_api_key', config('services.openrouter.api_key', '')) ?: '';
        $this->text_model         = Setting::get('ai.text_model', 'openai/gpt-5-mini') ?: 'openai/gpt-5-mini';
        $this->image_model        = Setting::get('ai.image_model', 'openai/gpt-5-image-mini') ?: 'openai/gpt-5-image-mini';
        $this->ai_temperature     = (float) (Setting::get('ai.temperature', '0.7') ?: 0.7);
        $this->ai_max_tokens      = (int) (Setting::get('ai.max_tokens', '2000') ?: 2000);
        $this->generate_images    = (bool) Setting::get('ai.generate_images', 'true');
        foreach (['description', 'short_description', 'price', 'compare_price', 'category', 'brand', 'tags',
                     'composition', 'manufacturer', 'storage', 'sku', 'meta_title', 'meta_description', 'unit', 'weight'] as $f) {
            $this->{'gen_' . $f} = (bool) Setting::get("ai.gen_{$f}", 'true');
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('OpenRouter API')->description('One API key → 200+ AI models. Get yours at openrouter.ai/keys')
                ->schema([
                    Forms\Components\TextInput::make('openrouter_api_key')->label('API Key')
                        ->password()->revealable()->placeholder('sk-or-v1-...')->required()
                        ->helperText('Single key for text + image generation.'),
                ]),
            Section::make('Model Selection')->schema([
                Forms\Components\Select::make('text_model')->label('Text AI (product details)')
                    ->options([
                        'openai/gpt-5-mini'     => 'GPT-5 Mini — Fast & cheap',
                        'openai/gpt-5'          => 'GPT-5 — Best quality',
                        'anthropic/claude-4'    => 'Claude 4 — Most accurate',
                        'google/gemini-2.5-pro' => 'Gemini 2.5 Pro',
                    ])->default('openai/gpt-5-mini')->required(),
                Forms\Components\Select::make('image_model')->label('Image AI')
                    ->options([
                        'openai/gpt-5-image-mini' => 'GPT-5 Image Mini — Recommended',
                        'openai/gpt-5-image'      => 'GPT-5 Image — Best',
                    ])->default('openai/gpt-5-image-mini')->required(),
                Forms\Components\Toggle::make('generate_images')->label('Generate Images')->default(true),
            ])->columns(2),
            Section::make('Parameters')->schema([
                Forms\Components\TextInput::make('ai_temperature')->label('Temperature (0=focused, 2=creative)')
                    ->numeric()->minValue(0)->maxValue(2)->step(0.1)->default(0.7),
                Forms\Components\TextInput::make('ai_max_tokens')->label('Max Tokens')
                    ->numeric()->minValue(500)->maxValue(4000)->default(2000),
            ])->columns(2),
            Section::make('Fields to Auto-Generate')->description('Toggle off to fill manually.')
                ->schema([
                    Forms\Components\Toggle::make('gen_description')->label('Description')->default(true),
                    Forms\Components\Toggle::make('gen_short_description')->label('Short Description')->default(true),
                    Forms\Components\Toggle::make('gen_price')->label('Price')->default(true),
                    Forms\Components\Toggle::make('gen_compare_price')->label('Compare Price')->default(true),
                    Forms\Components\Toggle::make('gen_category')->label('Category')->default(true),
                    Forms\Components\Toggle::make('gen_brand')->label('Brand')->default(true),
                    Forms\Components\Toggle::make('gen_tags')->label('Tags')->default(true),
                    Forms\Components\Toggle::make('gen_composition')->label('Composition')->default(true),
                    Forms\Components\Toggle::make('gen_manufacturer')->label('Manufacturer')->default(true),
                    Forms\Components\Toggle::make('gen_storage')->label('Storage')->default(true),
                    Forms\Components\Toggle::make('gen_sku')->label('SKU')->default(true),
                    Forms\Components\Toggle::make('gen_meta_title')->label('Meta Title')->default(true),
                    Forms\Components\Toggle::make('gen_meta_description')->label('Meta Description')->default(true),
                    Forms\Components\Toggle::make('gen_unit')->label('Unit')->default(true),
                    Forms\Components\Toggle::make('gen_weight')->label('Weight')->default(true),
                ])->columns(3),
        ]);
    }

    public function save(): void
    {
        Setting::set('ai.openrouter_api_key', $this->openrouter_api_key, 'ai');
        Setting::set('ai.text_model', $this->text_model, 'ai');
        Setting::set('ai.image_model', $this->image_model, 'ai');
        Setting::set('ai.temperature', (string) $this->ai_temperature, 'ai');
        Setting::set('ai.max_tokens', (string) $this->ai_max_tokens, 'ai');
        Setting::set('ai.generate_images', (string) $this->generate_images, 'ai', 'boolean');
        foreach (['description', 'short_description', 'price', 'compare_price', 'category', 'brand', 'tags',
                     'composition', 'manufacturer', 'storage', 'sku', 'meta_title', 'meta_description', 'unit', 'weight'] as $f) {
            Setting::set("ai.gen_{$f}", (string) $this->{'gen_' . $f}, 'ai', 'boolean');
        }
        Notification::make()->title('AI Settings saved.')->success()->send();
    }

    public function testConnection(): void
    {
        try {
            app(AIProductService::class)->generateProductDetails('Paracetamol 500mg');
            Notification::make()->title('Connected!')->body('OpenRouter is working.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
