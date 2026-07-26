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

    public string $deepseek_api_key   = '';
    public string $fal_api_key        = '';
    public string $text_model         = 'deepseek-v4-pro';
    public string $image_model        = 'gpt-image-1-mini';
    public string $serpapi_key        = '';
    public bool   $enable_image_search = true;
    public float  $ai_temperature     = 0.3;
    public int    $ai_max_tokens      = 8000;
    public bool   $generate_images    = false;
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
        $this->deepseek_api_key    = Setting::get('ai.deepseek_api_key', config('services.deepseek.api_key', '')) ?: '';
        $this->fal_api_key         = Setting::get('ai.fal_api_key', config('services.fal.api_key', '')) ?: '';
        $this->text_model          = Setting::get('ai.text_model', 'deepseek-v4-pro') ?: 'deepseek-v4-pro';
        $this->image_model         = Setting::get('ai.image_model', 'gpt-image-1-mini') ?: 'gpt-image-1-mini';
        $this->serpapi_key         = Setting::get('ai.serpapi_key', config('services.serpapi.key', '')) ?: '';
        $this->enable_image_search = Setting::get('ai.enable_image_search', '1') === '1';
        $this->ai_temperature      = (float) (Setting::get('ai.temperature', '0.7') ?: 0.7);
        $this->ai_max_tokens       = (int) (Setting::get('ai.max_tokens', '8000') ?: 8000);
        $this->generate_images     = Setting::get('ai.generate_images', '1') === '1';
        foreach (['description', 'short_description', 'price', 'compare_price', 'category', 'brand', 'tags',
                     'composition', 'manufacturer', 'storage', 'sku', 'meta_title', 'meta_description', 'unit', 'weight'] as $f) {
            $this->{'gen_' . $f} = Setting::get("ai.gen_{$f}", '1') === '1';
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('DeepSeek API')->description('Flagship reasoning & text models. Get yours at platform.deepseek.com')
                ->schema([
                    Forms\Components\TextInput::make('deepseek_api_key')->label('API Key')
                        ->password()->placeholder('sk-...')->required()
                        ->helperText('Stored securely. Not visible after save.'),
                    \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('test')
                            ->label('Test Connection')
                            ->action('testConnection')
                            ->icon('heroicon-o-signal'),
                    ]),
                ]),
            Section::make('Fal.ai API')->description('High-performance generative media platform. Get yours at fal.ai')
                ->schema([
                    Forms\Components\TextInput::make('fal_api_key')->label('API Key')
                        ->password()->placeholder('Key ...')->required()
                        ->helperText('Stored securely. Not visible after save.'),
                    \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('testImage')
                            ->label('Test Image API')
                            ->action('testImageConnection')
                            ->icon('heroicon-o-photo')
                            ->requiresConfirmation()
                            ->modalDescription('This sends one real request to Fal.ai with the selected image model, so it consumes a small amount of credit.'),
                    ]),
                ]),
            Section::make('Image Search (SerpAPI)')
                ->description('Search real pharma product images as reference for AI editing. Change API key anytime.')
                ->schema([
                    Forms\Components\TextInput::make('serpapi_key')->label('SerpAPI Key')
                        ->password()->placeholder('Your SerpAPI key from serpapi.com')
                        ->helperText('Get free key at serpapi.com — 100 searches/month free'),
                    Forms\Components\Toggle::make('enable_image_search')
                        ->label('Enable Image Search (Recommended ON)')
                        ->helperText('When ON, AI searches real product image before generating'),
                ])->columns(2),
            Section::make('Model Selection')->schema([
                Forms\Components\Select::make('text_model')->label('Text AI (product details)')
                    ->options([
                        'deepseek-v4-pro'   => 'DeepSeek V4 Pro — Flagship MoE ✅',
                        'deepseek-v4-flash' => 'DeepSeek V4 Flash — High Speed',
                    ])->default('deepseek-v4-pro')->required(),
                Forms\Components\Select::make('image_model')->label('Image AI')
                    ->options([
                        'gpt-image-1-mini' => 'GPT Image 1 Mini — Cost-effective & Fast ✅',
                    ])->default('gpt-image-1-mini')->required(),
                Forms\Components\Toggle::make('generate_images')->label('Generate Images')->default(true),
            ])->columns(2),
            Section::make('Parameters')->schema([
                Forms\Components\TextInput::make('ai_temperature')->label('Temperature (0=focused, 2=creative)')
                    ->numeric()->minValue(0)->maxValue(2)->step(0.1)->default(0.7),
                // 2000 was too small for the YMYL product payload (description,
                // how_it_works, side_effects, contraindications and the FAQ set),
                // so every response was cut off mid-JSON and the item failed with
                // "Control character error". 8192 is DeepSeek's output ceiling.
                Forms\Components\TextInput::make('ai_max_tokens')->label('Max Tokens')
                    ->helperText('8000 recommended. Below ~6000 the full product payload gets truncated.')
                    ->numeric()->minValue(500)->maxValue(8192)->default(8000),
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
        // Only overwrite a stored credential when a new one was actually typed.
        // These are password inputs, so an admin saving an unrelated setting can
        // submit them blank — writing that through would silently wipe a working
        // key and leave the next generation failing with a 401. serpapi_key was
        // already guarded this way; the other two were not.
        if (!empty($this->deepseek_api_key)) {
            Setting::set('ai.deepseek_api_key', $this->deepseek_api_key, 'ai');
        }
        if (!empty($this->fal_api_key)) {
            Setting::set('ai.fal_api_key', $this->fal_api_key, 'ai');
        }
        Setting::set('ai.text_model', $this->text_model, 'ai');
        Setting::set('ai.image_model', $this->image_model, 'ai');
        if (!empty($this->serpapi_key)) {
            Setting::set('ai.serpapi_key', $this->serpapi_key, 'ai');
        }
        Setting::set('ai.enable_image_search', $this->enable_image_search ? '1' : '0', 'ai');
        Setting::set('ai.temperature', (string) $this->ai_temperature, 'ai');
        Setting::set('ai.max_tokens', (string) $this->ai_max_tokens, 'ai');
        Setting::set('ai.generate_images', $this->generate_images ? '1' : '0', 'ai');
        foreach (['description', 'short_description', 'price', 'compare_price', 'category', 'brand', 'tags',
                     'composition', 'manufacturer', 'storage', 'sku', 'meta_title', 'meta_description', 'unit', 'weight'] as $f) {
            Setting::set("ai.gen_{$f}", $this->{'gen_' . $f} ? '1' : '0', 'ai');
        }
        Notification::make()->title('AI Settings saved.')->success()->send();
    }

    public function testConnection(): void
    {
        try {
            app(AIProductService::class)->ping();
            Notification::make()->title('Connected!')->body('DeepSeek API is working.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * There was no way to check the image credential, so a bad Fal.ai key only
     * showed up as products quietly finishing with a grey placeholder. This
     * exercises the exact call the pipeline makes and reports what came back.
     */
    public function testImageConnection(): void
    {
        $key = trim(Setting::get('ai.fal_api_key', config('services.fal.api_key', '')));

        if ($key === '') {
            Notification::make()
                ->title('No Fal.ai key saved')
                ->body('Enter a key and press Save Settings first — the test reads the stored value.')
                ->warning()
                ->send();
            return;
        }

        $model = Setting::get('ai.image_model', 'gpt-image-1-mini');

        try {
            $response = \Illuminate\Support\Facades\Http::connectTimeout(15)
                ->timeout(120)
                ->withHeaders([
                    'Authorization' => 'Key ' . $key,
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://fal.run/fal-ai/' . $model, [
                    'prompt'     => 'A plain white pharmaceutical box on a white background',
                    'image_size' => '1024x1024',
                ]);

            if ($response->successful() && $response->json('images.0.url')) {
                Notification::make()
                    ->title('Image API working')
                    ->body("Fal.ai responded with an image using \"{$model}\".")
                    ->success()
                    ->send();
                return;
            }

            $hint = match ($response->status()) {
                401, 403 => 'The key was rejected. Check it on fal.ai and confirm the account has credit.',
                404      => "The model \"{$model}\" is not available to this account. Pick a different Image AI.",
                429      => 'Rate limited by Fal.ai — try again shortly.',
                default  => 'Unexpected response from Fal.ai.',
            };

            Notification::make()
                ->title("Image API failed (HTTP {$response->status()})")
                ->body($hint . ' ' . \Illuminate\Support\Str::limit($response->body(), 200))
                ->danger()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()->title('Image API failed')->body($e->getMessage())->danger()->send();
        }
    }
}
