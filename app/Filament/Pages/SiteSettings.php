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
                    ->description('Business details shown in the site footer.')
                    ->components([
                        Textarea::make('footer_text')->label('Tagline')->rows(2)->required(),
                        TextInput::make('footer_company_name')->label('Company name')->required(),
                        Textarea::make('footer_address')->label('Address')->rows(2)
                            ->helperText('One line per row; line breaks are preserved.'),
                        TextInput::make('footer_email')->label('Email')->email(),
                        TextInput::make('footer_phone')->label('Phone')
                            ->helperText('Shown as-is; use international format, e.g. +32 479 08 98 44.'),
                        TextInput::make('footer_vat')->label('VAT / TVA number'),
                    ]),

                Section::make('Social links')
                    ->description('Leave a field empty to hide that icon. Paste the full profile URL.')
                    ->columns(2)
                    ->components([
                        TextInput::make('social_linkedin')->label('LinkedIn')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_youtube')->label('YouTube')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_instagram')->label('Instagram')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_x')->label('X (Twitter)')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_facebook')->label('Facebook')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_github')->label('GitHub')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_tiktok')->label('TikTok')->url()->prefixIcon('heroicon-o-link'),
                        TextInput::make('social_bluesky')->label('Bluesky')->url()->prefixIcon('heroicon-o-link'),
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
