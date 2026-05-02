<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Pages';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')
                ->required()->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')
                ->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\RichEditor::make('content')
                ->label('Content')->columnSpanFull(),
            Section::make('Breadcrumb')->schema([
                Forms\Components\TextInput::make('breadcrumb_title')
                    ->label('Breadcrumb Title')->maxLength(255)
                    ->helperText('Leave empty to use page title.'),
                Forms\Components\FileUpload::make('breadcrumb_image')
                    ->label('Breadcrumb Background')->image()->directory('pages/breadcrumbs')->disk('public'),
            ]),
            Forms\Components\TextInput::make('meta_title')
                ->label('Meta Title')->maxLength(255),
            Forms\Components\Textarea::make('meta_description')
                ->label('Meta Description')->rows(2)->maxLength(300),
            Forms\Components\Toggle::make('is_active')
                ->label('Published')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Published'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([1 => 'Published', 0 => 'Draft']),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
