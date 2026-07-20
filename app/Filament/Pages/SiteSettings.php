<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * @property Schema $form
 */
class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Site Settings';

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::shared());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meta')
                    ->components([
                        TextInput::make('head_title')
                            ->label('Browser tab title (home page)')
                            ->required(),
                    ]),

                Section::make('Hero')
                    ->components([
                        TextInput::make('hero_kicker')->label('Kicker (small line above the title)')->required(),
                        TextInput::make('hero_title')->label('Title')->required(),
                        Textarea::make('hero_subtitle')->label('Subtitle')->rows(3)->required(),
                        TextInput::make('hero_primary_label')->label('Primary button label')->required(),
                        TextInput::make('hero_secondary_label')->label('Secondary button label')->required(),
                    ]),

                Section::make('Services')
                    ->components([
                        TextInput::make('services_title')->label('Section title')->required(),
                        Repeater::make('services')
                            ->label('Service cards')
                            ->schema([
                                TextInput::make('title')->required(),
                                Textarea::make('body')->rows(3)->required(),
                            ])
                            ->minItems(1)
                            ->maxItems(6)
                            ->reorderable()
                            ->required(),
                    ]),

                Section::make('Call to action')
                    ->components([
                        TextInput::make('cta_title')->label('Title')->required(),
                        Textarea::make('cta_subtitle')->label('Subtitle')->rows(2)->required(),
                        TextInput::make('cta_button_label')->label('Button label')->required(),
                    ]),

                Section::make('Footer')
                    ->components([
                        TextInput::make('footer_title')->label('Title')->required(),
                        Textarea::make('footer_text')->label('Text')->rows(2)->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            SiteSetting::set($key, $value);
        }

        Notification::make()
            ->title('Site settings saved')
            ->success()
            ->send();
    }
}
