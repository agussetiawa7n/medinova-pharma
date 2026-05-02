<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Customers';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create'),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                Forms\Components\Select::make('roles')
                    ->label('Role')
                    ->options(Role::pluck('name', 'name'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')->circular(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('wallet.balance')
                    ->label('Wallet Balance')
                    ->money('USD')
                    ->sortable()
                    ->description(fn ($record) => $record->wallet?->balance ? money($record->wallet->balance) . ' MNP' : '—'),
                Tables\Columns\TextColumn::make('roles.name')->badge()->color('primary')->label('Roles'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')->query(fn ($q) => $q->where('is_active', true)),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('topupWallet')
                    ->label('Top-up Wallet')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(fn (User $record) => "Top-up Wallet: {$record->name}")
                    ->modalDescription('Add balance to this user\'s wallet. A transaction record will be created.')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount ($)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100000)
                            ->step(1)
                            ->prefix('$')
                            ->helperText('Enter the amount to add to the wallet.'),
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->rows(2)
                            ->placeholder('Optional: Reason for this top-up...')
                            ->helperText('This will be saved in the transaction description.'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $walletService = app(WalletService::class);

                        $amount = (float) $data['amount'];
                        $note = $data['note'] ?? 'Manual top-up by admin';

                        $walletService->credit(
                            $record->id,
                            $amount,
                            $note,
                            'manual',
                            null
                        );

                        Notification::make()
                            ->title('Wallet topped up successfully!')
                            ->body("\${$amount} added to {$record->name}'s wallet.")
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('Add Balance'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
