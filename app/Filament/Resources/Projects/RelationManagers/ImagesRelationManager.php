<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image_path')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('images')
                    ->visibility('public')
                    ->label('Screenshot'),

                // alt_text is required at the schema level (NOT NULL) — an image
                // cannot enter the gallery without a description. Accessibility
                // enforced in the admin, not just the front-end.
                TextInput::make('alt_text')
                    ->required()
                    ->maxLength(300)
                    ->label('Alt text (description for screen readers)')
                    ->helperText('Describe what the screenshot shows, e.g. "Booking page with a grid of available time slots".'),

                TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first in the gallery.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt_text')
            ->defaultSort('order')
            ->columns([
                ImageColumn::make('image_path')
                    ->disk('public')
                    ->label('Preview'),
                TextColumn::make('alt_text')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('order')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
