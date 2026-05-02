<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CheckoutManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Checkout Page';
    protected static ?string $navigationLabel = 'Checkout';

    protected string $view = 'filament.pages.checkout-manager';

    public array $data = [];
    public $checkout_breadcrumb_image = [];
    public string $checkout_breadcrumb_title = '';

    public function dehydrate(): void
    {
        if (is_string($this->checkout_breadcrumb_image)) {
            $this->checkout_breadcrumb_image = [$this->checkout_breadcrumb_image];
        }
        if ($this->checkout_breadcrumb_image === null) {
            $this->checkout_breadcrumb_image = [];
        }
    }

    public function mount(): void
    {
        $existing = Setting::get('checkout.breadcrumb_image');
        $this->checkout_breadcrumb_image = $existing ? [$existing] : [];
        $this->checkout_breadcrumb_title = Setting::get('checkout.breadcrumb_title', 'Complete Your Order') ?? '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Breadcrumb')->schema([
                Forms\Components\TextInput::make('checkout_breadcrumb_title')
                    ->label('Breadcrumb Title')
                    ->maxLength(100)
                    ->helperText('Shown as the page heading.'),
                Forms\Components\FileUpload::make('checkout_breadcrumb_image')
                    ->label('Background Image')
                    ->image()->directory('checkout')->disk('public')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && count($state) === 1) {
                            $first = reset($state);
                            if (is_string($first)) return $first;
                        }
                        if (empty($state)) {
                            $existing = Setting::get('checkout.breadcrumb_image');
                            return $existing ?: null;
                        }
                        return $state;
                    }),
            ]),
        ]);
    }

    public function save(): void
    {
        Setting::set('checkout.breadcrumb_title', $this->checkout_breadcrumb_title, 'checkout');

        if (is_array($this->checkout_breadcrumb_image) && !empty($this->checkout_breadcrumb_image)) {
            foreach ($this->checkout_breadcrumb_image as $k => $v) {
                if ($v instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $path = $v->store('checkout', 'public');
                    Setting::set('checkout.breadcrumb_image', $path, 'checkout');
                    $this->checkout_breadcrumb_image[$k] = $path;
                    break;
                }
            }
        }

        Notification::make()->title('Checkout page settings saved.')->success()->send();
    }
}
