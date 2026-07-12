<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
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
                    ->helperText('URL zapisa. Automatski se popuni iz naslova.'),

                TextInput::make('excerpt')
                    ->required()
                    ->maxLength(500)
                    ->helperText('Kratki izvadak prikazan u listi (do 500 znakova).'),

                RichEditor::make('body')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'blockquote',
                    ])
                    ->helperText('Koristi H2/H3 za naslove sekcija. Ne preskači razine (H2 prije H3).'),

                FileUpload::make('image_path')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('images')
                    ->visibility('public'),

                TextInput::make('author')
                    ->required()
                    ->default('Marko Buhovac'),

                DateTimePicker::make('published_at')
                    ->default(now())
                    ->helperText('Ostavi prazno za draft. Postavljen datum = objavljeno.'),
            ]);
    }
}
