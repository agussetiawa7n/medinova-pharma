<?php

namespace App\Filament\Resources;

use App\Enums\PrescriptionStatus;
use App\Filament\Resources\PrescriptionResource\Pages;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class PrescriptionResource extends Resource
{
    protected static ?string $model = Prescription::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Customers';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('status')
                ->options(PrescriptionStatus::options())
                ->required(),
            Forms\Components\Textarea::make('admin_notes')->rows(3)->label('Admin Notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('file_name')->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PrescriptionStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('patient_notes')->limit(40)->label('Patient Notes'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(PrescriptionStatus::options()),
            ])
            ->recordActions([
                FilamentAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Prescription $r) => $r->status === PrescriptionStatus::Pending)
                    ->action(function (Prescription $record) {
                        app(PrescriptionService::class)->approve($record);
                        Notification::make()->title('Prescription approved.')->success()->send();
                    }),
                FilamentAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Prescription $r) => $r->status === PrescriptionStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (Prescription $record) {
                        app(PrescriptionService::class)->reject($record);
                        Notification::make()->title('Prescription rejected.')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrescriptions::route('/'),
            'edit'  => Pages\EditPrescription::route('/{record}/edit'),
        ];
    }
}
