<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuthPageManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 8;
    protected static ?string $title = 'Auth Pages';
    protected static ?string $navigationLabel = 'Auth Pages';

    protected string $view = 'filament.pages.auth-page-manager';

    public array $data = [];

    // Login page
    public $login_breadcrumb_image = [];
    public $login_side_image = [];
    // Register page
    public $register_breadcrumb_image = [];
    public $register_side_image = [];
    // Wishlist page
    public $wishlist_breadcrumb_image = [];

    public function dehydrate(): void
    {
        foreach (['login_breadcrumb_image', 'login_side_image', 'register_breadcrumb_image', 'register_side_image', 'wishlist_breadcrumb_image'] as $prop) {
            if (is_string($this->{$prop})) {
                $this->{$prop} = [$this->{$prop}];
            }
            if ($this->{$prop} === null) {
                $this->{$prop} = [];
            }
        }
    }

    public function mount(): void
    {
        $this->login_breadcrumb_image    = $this->wrapExisting('auth.login_breadcrumb_image');
        $this->login_side_image          = $this->wrapExisting('auth.login_side_image');
        $this->register_breadcrumb_image = $this->wrapExisting('auth.register_breadcrumb_image');
        $this->register_side_image       = $this->wrapExisting('auth.register_side_image');
        $this->wishlist_breadcrumb_image = $this->wrapExisting('auth.wishlist_breadcrumb_image');
    }

    private function wrapExisting(string $key): array
    {
        $existing = Setting::get($key);
        return $existing ? [$existing] : [];
    }

    private function fileUploadField(string $name, string $label, string $key): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->label($label)->image()->directory('auth-pages')->disk('public')
            ->formatStateUsing(function ($state) use ($key) {
                if (is_array($state) && count($state) === 1) {
                    $first = reset($state);
                    if (is_string($first)) return $first;
                }
                if (empty($state)) {
                    $existing = Setting::get($key);
                    return $existing ?: null;
                }
                return $state;
            });
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Login Page')->schema([
                $this->fileUploadField('login_breadcrumb_image', 'Breadcrumb Background', 'auth.login_breadcrumb_image'),
                $this->fileUploadField('login_side_image', 'Side Image (Right Column)', 'auth.login_side_image'),
            ]),
            Section::make('Register Page')->schema([
                $this->fileUploadField('register_breadcrumb_image', 'Breadcrumb Background', 'auth.register_breadcrumb_image'),
                $this->fileUploadField('register_side_image', 'Side Image (Background)', 'auth.register_side_image'),
            ]),
            Section::make('Wishlist Page')->schema([
                $this->fileUploadField('wishlist_breadcrumb_image', 'Breadcrumb Background', 'auth.wishlist_breadcrumb_image'),
            ]),
        ]);
    }

    public function save(): void
    {
        $fields = [
            'login_breadcrumb_image'    => 'auth.login_breadcrumb_image',
            'login_side_image'          => 'auth.login_side_image',
            'register_breadcrumb_image' => 'auth.register_breadcrumb_image',
            'register_side_image'       => 'auth.register_side_image',
            'wishlist_breadcrumb_image' => 'auth.wishlist_breadcrumb_image',
        ];

        foreach ($fields as $prop => $settingKey) {
            $val = $this->{$prop};
            if (is_array($val) && !empty($val)) {
                foreach ($val as $k => $v) {
                    if ($v instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $path = $v->store('auth-pages', 'public');
                        Setting::set($settingKey, $path, 'auth');
                        $this->{$prop}[$k] = $path;
                        break;
                    }
                }
            }
        }

        Notification::make()->title('Auth page images saved.')->success()->send();
    }
}
