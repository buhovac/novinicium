<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set, $context) {
                        if ($context === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('URL projekta. Automatski se popuni iz naslova.'),

                TextInput::make('description')
                    ->required()
                    ->maxLength(500)
                    ->helperText('Kratki opis prikazan u listi (do 500 znakova).'),

                RichEditor::make('body')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'blockquote',
                    ])
                    ->helperText('Puni opis projekta. Koristi H2/H3 za sekcije.'),

                TextInput::make('live_url')
                    ->url()
                    ->helperText('Link na živi projekt (opcionalno).'),

                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Manji broj = ranije u listi.'),
            ]);
    }
}
