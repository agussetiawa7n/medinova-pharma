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

    public string $openai_api_key    = '';
    public string $anthropic_api_key = '';
    public string $google_ai_api_key = '';
    public string $stability_api_key = '';
    public string $flux_api_key      = '';
    public string $text_model        = 'openai/gpt-4o-mini';
    public string $image_model       = 'openai/dall-e-3';
    public string $image_style       = 'natural';
    public float  $ai_temperature    = 0.7;
    public int    $ai_max_tokens     = 2000;
    public bool   $generate_images   = true;
    public bool   $gen_description       = true;
    public bool   $gen_short_description = true;
    public bool   $gen_price             = true;
    public bool   $gen_compare_price     = true;
    public bool   $gen_category          = true;
    public bool   $gen_brand             = true;
    public bool   $gen_tags              = true;
    public bool   $gen_composition       = true;
    public bool   $gen_manufacturer      = true;
    public bool   $gen_storage           = true;
    public bool   $gen_sku               = true;
    public bool   $gen_meta_title        = true;
    public bool   $gen_meta_description  = true;
    public bool   $gen_unit              = true;
    public bool   $gen_weight            = true;

    public function mount(): void
    {
        $this->openai_api_key    = Setting::get('ai.openai_api_key', config('services.openai.api_key', '')) ?: '';
        $this->anthropic_api_key = Setting::get('ai.anthropic_api_key', config('services.anthropic.api_key', '')) ?: '';
        $this->google_ai_api_key = Setting::get('ai.google_ai_api_key', config('services.google_ai.api_key', '')) ?: '';
        $this->stability_api_key = Setting::get('ai.stability_api_key', config('services.stability.api_key', '')) ?: '';
        $this->flux_api_key      = Setting::get('ai.flux_api_key', config('services.flux.api_key', '')) ?: '';
        $this->text_model        = Setting::get('ai.text_model', 'openai/gpt-4o-mini') ?: 'openai/gpt-4o-mini';
        $this->image_model       = Setting::get('ai.image_model', 'openai/dall-e-3') ?: 'openai/dall-e-3';
        $this->image_style       = Setting::get('ai.image_style', 'natural') ?: 'natural';
        $this->ai_temperature    = (float) (Setting::get('ai.temperature', '0.7') ?: 0.7);
        $this->ai_max_tokens     = (int) (Setting::get('ai.max_tokens', '2000') ?: 2000);
        $this->generate_images   = (bool) Setting::get('ai.generate_images', 'true');

        foreach (['description', 'short_description', 'price', 'compare_price', 'category', 'brand', 'tags',
                     'composition', 'manufacturer', 'storage', 'sku', 'meta_title', 'meta_description', 'unit', 'weight'] as $field) {
            $this->{'gen_' . $field} = (bool) Setting::get("ai.gen_{$field}", 'true');
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('API Keys')->schema([
                Forms\Components\TextInput::make('openai_api_key')->label('OpenAI API Key')
                    ->password()->revealable()->placeholder('sk-...')
                    ->helperText('For GPT-4o text + DALL-E image generation'),
                Forms\Components\TextInput::make('anthropic_api_key')->label('Anthropic API Key')
                    ->password()->revealable()->placeholder('sk-ant-...')
                    ->helperText('For Claude 3.5 Sonnet text generation'),
                Forms\Components\TextInput::make('google_ai_api_key')->label('Google AI API Key')
                    ->password()->revealable()->placeholder('AIza...')
                    ->helperText('For Gemini 1.5 Pro text generation'),
                Forms\Components\TextInput::make('stability_api_key')->label('Stability AI API Key')
                    ->password()->revealable()->placeholder('sk-...')
                    ->helperText('For Stable Diffusion 3 image generation'),
                Forms\Components\TextInput::make('flux_api_key')->label('Flux API Key (Black Forest Labs)')
                    ->password()->revealable()
                    ->helperText('For Flux Pro image generation'),
            ])->columns(2),

            Section::make('Model Selection')->schema([
                Forms\Components\Select::make('text_model')->label('Text AI Model')
                    ->options([
                        'openai/gpt-4o'         => 'GPT-4o — Best quality (OpenAI)',
                        'openai/gpt-4o-mini'    => 'GPT-4o Mini — Fast & cheap (OpenAI)',
                        'anthropic/claude-3-5'  => 'Claude 3.5 Sonnet — Accurate (Anthropic)',
                        'google/gemini-1-5-pro' => 'Gemini 1.5 Pro (Google)',
                    ])->default('openai/gpt-4o-mini')->required(),
                Forms\Components\Select::make('image_model')->label('Image AI Model')
                    ->options([
                        'openai/dall-e-3'       => 'DALL-E 3 HD — Best (OpenAI)',
                        'openai/dall-e-2'       => 'DALL-E 2 — Cheaper (OpenAI)',
                        'stability/sd3'         => 'Stable Diffusion 3 (Stability AI)',
                        'blackforest/flux-pro'  => 'Flux Pro 1.1 (Black Forest Labs)',
                        'placeholder'           => 'No AI — Placeholder only',
                    ])->default('openai/dall-e-3')->required(),
                Forms\Components\Select::make('image_style')->label('Image Style')
                    ->options(['natural' => 'Natural — Realistic', 'vivid' => 'Vivid — Enhanced'])->default('natural'),
                Forms\Components\Toggle::make('generate_images')->label('Generate AI Images')->default(true),
            ])->columns(2),

            Section::make('Model Parameters')->schema([
                Forms\Components\TextInput::make('ai_temperature')->label('Temperature (0=focused, 2=creative)')
                    ->numeric()->minValue(0)->maxValue(2)->step(0.1)->default(0.7),
                Forms\Components\TextInput::make('ai_max_tokens')->label('Max Tokens')
                    ->numeric()->minValue(500)->maxValue(4000)->default(2000),
            ])->columns(2),

            Section::make('Fields to Auto-Generate')->description('Toggle off any field to fill manually.')
                ->schema([
                    Forms\Components\Toggle::make('gen_description')->label('Description (HTML)')->default(true),
                    Forms\Components\Toggle::make('gen_short_description')->label('Short Description')->default(true),
                    Forms\Components\Toggle::make('gen_price')->label('Price')->default(true),
                    Forms\Components\Toggle::make('gen_compare_price')->label('Compare Price')->default(true),
                    Forms\Components\Toggle::make('gen_category')->label('Category')->default(true),
                    Forms\Components\Toggle::make('gen_brand')->label('Brand')->default(true),
                    Forms\Components\Toggle::make('gen_tags')->label('Tags')->default(true),
                    Forms\Components\Toggle::make('gen_composition')->label('Composition')->default(true),
                    Forms\Components\Toggle::make('gen_manufacturer')->label('Manufacturer')->default(true),
                    Forms\Components\Toggle::make('gen_storage')->label('Storage Conditions')->default(true),
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
        $strFields = ['openai_api_key', 'anthropic_api_key', 'google_ai_api_key', 'stability_api_key', 'flux_api_key',
                       'text_model', 'image_model', 'image_style'];
        foreach ($strFields as $f) {
            Setting::set("ai.{$f}", (string) $this->{$f}, 'ai');
        }
        Setting::set('ai.temperature', (string) $this->ai_temperature, 'ai');
        Setting::set('ai.max_tokens', (string) $this->ai_max_tokens, 'ai');
        Setting::set('ai.generate_images', (string) $this->generate_images, 'ai', 'boolean');

        $genFields = ['description', 'short_description', 'price', 'compare_price', 'category', 'brand', 'tags',
                       'composition', 'manufacturer', 'storage', 'sku', 'meta_title', 'meta_description', 'unit', 'weight'];
        foreach ($genFields as $f) {
            Setting::set("ai.gen_{$f}", (string) $this->{'gen_' . $f}, 'ai', 'boolean');
        }

        Notification::make()->title('AI Settings saved.')->success()->send();
    }

    public function testConnection(): void
    {
        try {
            $service = app(AIProductService::class);
            $result  = $service->generateProductDetails('Paracetamol 500mg');
            Notification::make()->title('Connection OK!')->body('AI returned valid data.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Connection Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
