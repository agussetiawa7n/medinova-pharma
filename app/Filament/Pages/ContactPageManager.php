<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ContactPageManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-phone';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Contact Page';
    protected static ?string $navigationLabel = 'Contact';

    public array $data = [];

    protected string $view = 'filament.pages.contact-page-manager';

    // Breadcrumb
    public $contact_breadcrumb_image = [];
    public string $contact_breadcrumb_title  = '';

    public function dehydrate(): void
    {
        if (is_string($this->contact_breadcrumb_image)) {
            $this->contact_breadcrumb_image = [$this->contact_breadcrumb_image];
        }
        if ($this->contact_breadcrumb_image === null) {
            $this->contact_breadcrumb_image = [];
        }
    }

    // Form section
    public string $contact_form_heading  = '';
    public string $contact_form_subtitle = '';

    // Sidebar section
    public string $contact_info_heading  = '';
    public string $contact_info_subtitle = '';

    // Contact details
    public string $contact_address = '';
    public string $contact_phone   = '';
    public string $contact_email   = '';
    public string $contact_hours   = '';

    // Map
    public string $contact_map_url = '';

    // Sidebar item labels
    public string $contact_label_email   = '';
    public string $contact_label_phone   = '';
    public string $contact_label_address = '';

    public function mount(): void
    {
        // Breadcrumb
        $existing = Setting::get('contact.breadcrumb_image');
        $this->contact_breadcrumb_image = $existing ? [$existing] : [];
        $this->contact_breadcrumb_title = Setting::get('contact.breadcrumb_title', 'Contact Us') ?? '';

        // Form
        $this->contact_form_heading  = Setting::get('contact.form_heading', 'GET IN TOUCH') ?? '';
        $this->contact_form_subtitle = Setting::get('contact.form_subtitle', 'Have a question or need assistance? Fill out the form below and our team will get back to you as soon as possible — usually within 2 hours.') ?? '';

        // Sidebar
        $this->contact_info_heading  = Setting::get('contact.info_heading', 'CONTACT INFORMATION') ?? '';
        $this->contact_info_subtitle = Setting::get('contact.info_subtitle', 'For immediate assistance, reach us directly:') ?? '';

        // Details
        $this->contact_address = Setting::get('contact.address', 'Jariptaka, Nagpur, Maharashtra') ?? '';
        $this->contact_phone   = Setting::get('contact.phone', '+91 000000 00000') ?? '';
        $this->contact_email   = Setting::get('contact.email', 'support@medinovapharma.com') ?? '';
        $this->contact_hours   = Setting::get('contact.hours', 'Mon-Sun: 8 AM - 11 PM') ?? '';

        // Map
        $this->contact_map_url = Setting::get('contact.map_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241317.11281381823!2d72.7104273614083!3d19.082502457710602!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7c6306644edc1%3A0x5da4ed8f8d648c69!2sMumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1700000000000') ?? '';

        // Labels
        $this->contact_label_email   = Setting::get('contact.label_email', 'Customer Support') ?? '';
        $this->contact_label_phone   = Setting::get('contact.label_phone', 'Phone (24/7)') ?? '';
        $this->contact_label_address = Setting::get('contact.label_address', 'Head Office') ?? '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Breadcrumb')->schema([
                Forms\Components\TextInput::make('contact_breadcrumb_title')
                    ->label('Page Title')->maxLength(100),
                Forms\Components\FileUpload::make('contact_breadcrumb_image')
                    ->label('Background Image')->image()->directory('contact')->disk('public')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && count($state) === 1) {
                            $first = reset($state);
                            if (is_string($first)) return $first;
                        }
                        if (empty($state)) {
                            $existing = Setting::get('contact.breadcrumb_image');
                            return $existing ?: null;
                        }
                        return $state;
                    }),
            ]),
            Section::make('Form Section')->schema([
                Forms\Components\TextInput::make('contact_form_heading')
                    ->label('Heading')->maxLength(100),
                Forms\Components\Textarea::make('contact_form_subtitle')
                    ->label('Subtitle')->rows(2)->maxLength(300),
            ]),
            Section::make('Contact Info Sidebar')->schema([
                Forms\Components\TextInput::make('contact_info_heading')
                    ->label('Sidebar Heading')->maxLength(100),
                Forms\Components\Textarea::make('contact_info_subtitle')
                    ->label('Sidebar Subtitle')->rows(2)->maxLength(300),
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('contact_label_email')
                        ->label('Email Label'),
                    Forms\Components\TextInput::make('contact_label_phone')
                        ->label('Phone Label'),
                    Forms\Components\TextInput::make('contact_label_address')
                        ->label('Address Label'),
                ]),
            ]),
            Section::make('Contact Details')->schema([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('contact_phone')->label('Phone Number'),
                    Forms\Components\TextInput::make('contact_email')->label('Email')->email(),
                    Forms\Components\TextInput::make('contact_address')->label('Address'),
                    Forms\Components\TextInput::make('contact_hours')->label('Business Hours'),
                ]),
            ]),
            Section::make('Google Map')->schema([
                Forms\Components\TextInput::make('contact_map_url')
                    ->label('Map Embed URL')->url()->maxLength(500),
            ]),
        ]);
    }

    public function save(): void
    {
        $str = fn ($key, $val) => Setting::set($key, (string) $val, 'contact');

        $str('contact.breadcrumb_title', $this->contact_breadcrumb_title);
        if (is_array($this->contact_breadcrumb_image) && !empty($this->contact_breadcrumb_image)) {
            foreach ($this->contact_breadcrumb_image as $k => $v) {
                if ($v instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $path = $v->store('contact', 'public');
                    Setting::set('contact.breadcrumb_image', $path, 'contact');
                    $this->contact_breadcrumb_image[$k] = $path;
                    break;
                }
            }
        }

        $str('contact.form_heading', $this->contact_form_heading);
        $str('contact.form_subtitle', $this->contact_form_subtitle);
        $str('contact.info_heading', $this->contact_info_heading);
        $str('contact.info_subtitle', $this->contact_info_subtitle);

        $str('contact.address', $this->contact_address);
        $str('contact.phone', $this->contact_phone);
        $str('contact.email', $this->contact_email);
        $str('contact.hours', $this->contact_hours);

        $str('contact.map_url', $this->contact_map_url);

        $str('contact.label_email', $this->contact_label_email);
        $str('contact.label_phone', $this->contact_label_phone);
        $str('contact.label_address', $this->contact_label_address);

        Notification::make()->title('Contact page settings saved.')->success()->send();
    }
}
