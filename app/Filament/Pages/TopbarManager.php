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

class TopbarManager extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';
    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Topbar Manager';
    protected static ?string $navigationLabel = 'Topbar';

    protected string $view = 'filament.pages.topbar-manager';

    public array $data = [];
    public string $contact_phone   = '';
    public string $contact_email   = '';
    public string $social_facebook  = '';
    public string $social_twitter   = '';
    public string $social_instagram = '';
    public string $social_linkedin  = '';

    public function mount(): void
    {
        $this->contact_phone    = Setting::get('contact.phone', '') ?? '';
        $this->contact_email    = Setting::get('contact.email', '') ?? '';
        $this->social_facebook  = Setting::get('social.facebook', '') ?? '';
        $this->social_twitter   = Setting::get('social.twitter', '') ?? '';
        $this->social_instagram = Setting::get('social.instagram', '') ?? '';
        $this->social_linkedin  = Setting::get('social.linkedin', '') ?? '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Topbar Contact Info')->schema([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('contact_phone')->label('Phone Number'),
                    Forms\Components\TextInput::make('contact_email')->label('Email')->email(),
                ]),
            ]),
            Section::make('Social Media Links')->schema([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('social_facebook')->label('Facebook URL')->url(),
                    Forms\Components\TextInput::make('social_twitter')->label('X (Twitter) URL')->url(),
                    Forms\Components\TextInput::make('social_instagram')->label('Instagram URL')->url(),
                    Forms\Components\TextInput::make('social_linkedin')->label('LinkedIn URL')->url(),
                ]),
            ]),
        ]);
    }

    public function save(): void
    {
        Setting::set('contact.phone', $this->contact_phone, 'topbar');
        Setting::set('contact.email', $this->contact_email, 'topbar');
        Setting::set('social.facebook', $this->social_facebook, 'topbar');
        Setting::set('social.twitter', $this->social_twitter, 'topbar');
        Setting::set('social.instagram', $this->social_instagram, 'topbar');
        Setting::set('social.linkedin', $this->social_linkedin, 'topbar');

        Notification::make()->title('Topbar settings saved.')->success()->send();
    }
}
