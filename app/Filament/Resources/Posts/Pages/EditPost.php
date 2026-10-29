<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Shows the last saved version (drafts too) exactly as the public page renders it.
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => route('blog.preview', $this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    /**
     * Legacy posts have no TipTap document yet: open the editor on the old
     * `body` (HTML or plain text, parsed to TipTap JSON). Saving once migrates
     * the post; nothing is rewritten until then.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['body_json'] ??= app(BuildPostContent::class)->parseLegacyBody($data['body'] ?? null);

        return $data;
    }
}
