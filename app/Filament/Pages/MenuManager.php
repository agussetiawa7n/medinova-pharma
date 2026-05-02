<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-4';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Menu Manager';
    protected static ?string $navigationLabel = 'Menu';

    protected string $view = 'filament.pages.menu-manager';

    public array $data = [];
    public array $menuCategories = [];
    public $megaMenuImage = [];
    public string $megaMenuTagline = 'SPECIAL OFFER';
    public string $megaMenuTitle = 'Save 20%';
    public string $megaMenuButtonText = 'Shop Now';

    public function dehydrate(): void
    {
        if (is_string($this->megaMenuImage)) {
            $this->megaMenuImage = [$this->megaMenuImage];
        }
        if ($this->megaMenuImage === null) {
            $this->megaMenuImage = [];
        }
    }

    public function mount(): void
    {
        $this->menuCategories = Category::query()
            ->where('show_in_menu', true)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->pluck('id')
            ->toArray();

        $existing = Setting::get('menu.mega_menu_image');
        if ($existing) {
            $this->megaMenuImage = [$existing];
        }
        $this->megaMenuTagline = Setting::get('menu.mega_menu_tagline', 'SPECIAL OFFER');
        $this->megaMenuTitle = Setting::get('menu.mega_menu_title', 'Save 20%');
        $this->megaMenuButtonText = Setting::get('menu.mega_menu_button_text', 'Shop Now');
    }

    public function form(Schema $schema): Schema
    {
        $catOptions = Category::query()
            ->where('is_active', true)->whereNull('parent_id')
            ->orderBy('sort_order')->pluck('name', 'id')->toArray();

        return $schema->schema([
            Section::make('Navigation Categories')->schema([
                Forms\Components\Select::make('menuCategories')
                    ->label('Categories in Header Menu')
                    ->options($catOptions)
                    ->multiple()
                    ->searchable()
                    ->helperText('Selected categories appear in header navigation and mega menu dropdowns.'),
                Forms\Components\FileUpload::make('megaMenuImage')
                    ->label('Mega Menu Promo Image')
                    ->image()->directory('menu')->disk('public')
                    ->helperText('Shown in the mega menu dropdown.')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && count($state) === 1) {
                            $first = reset($state);
                            if (is_string($first)) return $first;
                        }
                        if (empty($state)) {
                            $existing = Setting::get('menu.mega_menu_image');
                            return $existing ?: null;
                        }
                        return $state;
                    }),
            ]),
            Section::make('Mega Menu Banner Text')->schema([
                Forms\Components\TextInput::make('megaMenuTagline')
                    ->label('Tagline')
                    ->maxLength(50)
                    ->helperText('Small text above the title (e.g. SPECIAL OFFER)'),
                Forms\Components\TextInput::make('megaMenuTitle')
                    ->label('Title')
                    ->maxLength(80)
                    ->helperText('Large heading (e.g. Save 20%)'),
                Forms\Components\TextInput::make('megaMenuButtonText')
                    ->label('Button Text')
                    ->maxLength(30)
                    ->helperText('Text on the CTA button (e.g. Shop Now)'),
            ]),
        ]);
    }

    public function save(): void
    {
        Category::query()->whereNull('parent_id')->update(['show_in_menu' => false]);
        Category::query()->whereIn('id', $this->menuCategories)->update(['show_in_menu' => true]);

        $image = $this->megaMenuImage;
        if (is_array($image) && !empty($image)) {
            foreach ($image as $k => $v) {
                if ($v instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $path = $v->store('menu', 'public');
                    Setting::set('menu.mega_menu_image', $path, 'menu');
                    // Replace temp file with final path so snapshot saves a string
                    $this->megaMenuImage[$k] = $path;
                    break;
                }
            }
        }

        Setting::set('menu.mega_menu_tagline', $this->megaMenuTagline, 'menu');
        Setting::set('menu.mega_menu_title', $this->megaMenuTitle, 'menu');
        Setting::set('menu.mega_menu_button_text', $this->megaMenuButtonText, 'menu');

        Notification::make()->title('Menu settings saved.')->success()->send();
    }
}
