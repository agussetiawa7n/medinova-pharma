<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;

class SmtpSettings extends Page
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'SMTP / Email Settings';
    protected static ?string $navigationLabel = 'SMTP';

    protected string $view = 'filament.pages.smtp-settings';

    public array $data = [];

    public string $mail_mailer = 'smtp';
    public string $mail_host = '';
    public string $mail_port = '587';
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = 'tls';
    public string $mail_from_address = '';
    public string $mail_from_name = '';

    public function mount(): void
    {
        $this->mail_mailer       = config('mail.default', 'smtp') ?: 'smtp';
        $this->mail_host         = config('mail.mailers.smtp.host') ?: '';
        $this->mail_port         = (string) (config('mail.mailers.smtp.port') ?: '587');
        $this->mail_username     = config('mail.mailers.smtp.username') ?: '';
        $this->mail_password     = config('mail.mailers.smtp.password') ?: '';
        $this->mail_encryption   = config('mail.mailers.smtp.encryption') ?: 'tls';
        $this->mail_from_address = config('mail.from.address') ?: '';
        $this->mail_from_name    = config('mail.from.name') ?: '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('SMTP Server')->schema([
                Forms\Components\TextInput::make('mail_host')
                    ->label('Host')->required()->maxLength(255)
                    ->placeholder('smtp.gmail.com'),
                Forms\Components\TextInput::make('mail_port')
                    ->label('Port')->required()->maxLength(10)
                    ->placeholder('587'),
                Forms\Components\Select::make('mail_encryption')
                    ->label('Encryption')
                    ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'])
                    ->default('tls'),
            ])->columns(3),
            Section::make('Authentication')->schema([
                Forms\Components\TextInput::make('mail_username')
                    ->label('Username / Email')->maxLength(255)
                    ->placeholder('your@email.com'),
                Forms\Components\TextInput::make('mail_password')
                    ->label('Password')->password()->revealable()
                    ->placeholder('App password for Gmail'),
            ])->columns(2),
            Section::make('From Address')->schema([
                Forms\Components\TextInput::make('mail_from_address')
                    ->label('From Email')->email()->maxLength(255)
                    ->placeholder('noreply@medinovapharma.com'),
                Forms\Components\TextInput::make('mail_from_name')
                    ->label('From Name')->maxLength(100)
                    ->placeholder('MediNova Pharma'),
            ])->columns(2),
        ]);
    }

    public function save(): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        $pairs = [
            'MAIL_MAILER'       => $this->mail_mailer,
            'MAIL_HOST'         => $this->mail_host,
            'MAIL_PORT'         => $this->mail_port,
            'MAIL_USERNAME'     => $this->mail_username,
            'MAIL_PASSWORD'     => $this->mail_password ? ('"' . $this->mail_password . '"') : '',
            'MAIL_ENCRYPTION'   => $this->mail_encryption,
            'MAIL_FROM_ADDRESS' => $this->mail_from_address,
            'MAIL_FROM_NAME'    => '"' . $this->mail_from_name . '"',
        ];

        foreach ($pairs as $key => $value) {
            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);

        Artisan::call('config:clear');

        Notification::make()
            ->title('SMTP settings saved.')
            ->body('Email configuration updated. Config cache cleared.')
            ->success()
            ->send();
    }
}
