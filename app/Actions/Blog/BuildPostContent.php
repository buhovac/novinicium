<?php

namespace App\Actions\Blog;

use App\Filament\Resources\Posts\Blocks\PostContentBlocks;
use App\Models\Post;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Turns a post body (TipTap JSON, or legacy HTML / plain text) into the
 * whitelisted, typed node tree rendered by <BlogContent> on the public site.
 *
 * Only known node types and attributes survive; everything else is dropped.
 * The output never contains HTML strings, so the frontend never needs v-html.
 * Mirrors the TypeScript model in resources/js/types/blog-content.ts.
 *
 * @phpstan-type Node array<string, mixed>
 */
class BuildPostContent
{
    private const INLINE_TYPES = ['text', 'hardBreak', 'image'];

    private const ALLOWED_MARKS = ['bold', 'italic', 'code', 'link'];

    private const GRID_BREAKPOINTS = ['sm', 'md', 'lg', 'xl', '2xl'];

    /**
     * @return list<Node>
     */
    public function handle(Post $post): array
    {
        return $this->fromDocument($post->body_json ?? $this->parseLegacyBody($post->body));
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<Node>
     */
    public function fromDocument(array $document): array
    {
        return $this->blocks($document['content'] ?? []);
    }

    /**
     * Legacy bodies are either RichEditor HTML or (older seeded posts) plain
     * text with blank lines between paragraphs. Both become a TipTap document.
     *
     * @return array<string, mixed>
     */
    public function parseLegacyBody(?string $body): array
    {
        $body = trim((string) $body);

        if ($body === '') {
            return ['type' => 'doc', 'content' => []];
        }

        if (! preg_match('/<[a-z][^>]*>/i', $body)) {
            $body = collect(preg_split('/\R\s*\R/', $body) ?: [])
                ->map(fn (string $paragraph): string => trim($paragraph))
                ->filter()
                ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
                ->implode('');
        }

        return RichContentRenderer::make($body)->toArray();
    }

    /**
     * Normalize block-level children. Stray inline nodes (TipTap's PHP parser
     * leaves bare text at block level in some HTML) are wrapped in paragraphs.
     *
     * @param  array<mixed>  $nodes
     * @return list<Node>
     */
    private function blocks(array $nodes): array
    {
        $blocks = [];
        $pendingInline = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (in_array($node['type'] ?? null, self::INLINE_TYPES, true)) {
                $pendingInline[] = $node;

                continue;
            }

            if ($pendingInline !== []) {
                $blocks[] = ['type' => 'paragraph', 'content' => $this->inlines($pendingInline)];
                $pendingInline = [];
            }

            $block = $this->block($node);

            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        if ($pendingInline !== []) {
            $blocks[] = ['type' => 'paragraph', 'content' => $this->inlines($pendingInline)];
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return Node|null
     */
    private function block(array $node): ?array
    {
        $attrs = (array) ($node['attrs'] ?? []);
        $content = (array) ($node['content'] ?? []);

        return match ($node['type'] ?? null) {
            'paragraph' => ['type' => 'paragraph', 'content' => $this->inlines($content)],
            // The page title is the only h1: body headings are clamped to h2/h3.
            'heading' => [
                'type' => 'heading',
                'level' => ((int) ($attrs['level'] ?? 2)) <= 2 ? 2 : 3,
                'content' => $this->inlines($content),
            ],
            'bulletList' => ['type' => 'bulletList', 'content' => $this->listItems($content)],
            'orderedList' => [
                'type' => 'orderedList',
                'start' => max(1, (int) ($attrs['start'] ?? 1)),
                'content' => $this->listItems($content),
            ],
            'blockquote' => ['type' => 'blockquote', 'content' => $this->blocks($content)],
            'horizontalRule' => ['type' => 'horizontalRule'],
            'codeBlock' => [
                'type' => 'codeBlock',
                'language' => $this->codeLanguage($attrs['language'] ?? null),
                'text' => $this->plainText($content),
            ],
            'table' => $this->table($content),
            'grid' => $this->grid($attrs, $content),
            // Each custom block validates its own config; unknown ids are dropped.
            'customBlock' => $this->customBlock($attrs),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return Node|null
     */
    private function customBlock(array $attrs): ?array
    {
        $block = PostContentBlocks::find($attrs['id'] ?? null);

        return $block === null ? null : $block::normalize($attrs['config'] ?? null);
    }

    /**
     * @param  array<mixed>  $nodes
     * @return list<Node>
     */
    private function listItems(array $nodes): array
    {
        $items = [];

        foreach ($nodes as $node) {
            if (is_array($node) && ($node['type'] ?? null) === 'listItem') {
                $items[] = ['type' => 'listItem', 'content' => $this->blocks((array) ($node['content'] ?? []))];
            }
        }

        return $items;
    }

    /**
     * @param  array<mixed>  $rows
     * @return Node|null
     */
    private function table(array $rows): ?array
    {
        $normalizedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ($row['type'] ?? null) !== 'tableRow') {
                continue;
            }

            $cells = [];

            foreach ((array) ($row['content'] ?? []) as $cell) {
                $type = is_array($cell) ? ($cell['type'] ?? null) : null;

                if (! in_array($type, ['tableHeader', 'tableCell'], true)) {
                    continue;
                }

                $attrs = (array) ($cell['attrs'] ?? []);

                $cells[] = [
                    'type' => $type,
                    'colspan' => max(1, min(50, (int) ($attrs['colspan'] ?? 1))),
                    'rowspan' => max(1, min(50, (int) ($attrs['rowspan'] ?? 1))),
                    'content' => $this->blocks((array) ($cell['content'] ?? [])),
                ];
            }

            if ($cells !== []) {
                $normalizedRows[] = ['type' => 'tableRow', 'content' => $cells];
            }
        }

        return $normalizedRows === [] ? null : ['type' => 'table', 'content' => $normalizedRows];
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @param  array<mixed>  $columns
     * @return Node|null
     */
    private function grid(array $attrs, array $columns): ?array
    {
        $columnCount = max(1, min(12, (int) ($attrs['data-cols'] ?? 2)));
        $breakpoint = $attrs['data-from-breakpoint'] ?? null;
        $normalizedColumns = [];

        foreach ($columns as $column) {
            if (! is_array($column) || ($column['type'] ?? null) !== 'gridColumn') {
                continue;
            }

            $normalizedColumns[] = [
                'type' => 'gridColumn',
                'span' => max(1, min($columnCount, (int) (((array) ($column['attrs'] ?? []))['data-col-span'] ?? 1))),
                'content' => $this->blocks((array) ($column['content'] ?? [])),
            ];
        }

        if ($normalizedColumns === []) {
            return null;
        }

        return [
            'type' => 'grid',
            'columns' => $columnCount,
            'fromBreakpoint' => in_array($breakpoint, self::GRID_BREAKPOINTS, true) ? $breakpoint : 'lg',
            'content' => $normalizedColumns,
        ];
    }

    /**
     * @param  array<mixed>  $nodes
     * @return list<Node>
     */
    private function inlines(array $nodes): array
    {
        $inlines = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $attrs = (array) ($node['attrs'] ?? []);

            $inline = match ($node['type'] ?? null) {
                'text' => ['type' => 'text', 'text' => (string) ($node['text'] ?? ''), 'marks' => $this->marks((array) ($node['marks'] ?? []))],
                'hardBreak' => ['type' => 'hardBreak'],
                'image' => $this->image($attrs),
                default => null,
            };

            if ($inline !== null) {
                $inlines[] = $inline;
            }
        }

        return $inlines;
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return Node|null
     */
    private function image(array $attrs): ?array
    {
        $src = $this->safeUrl($attrs['src'] ?? null, ['http', 'https']);

        if ($src === null) {
            return null;
        }

        return [
            'type' => 'image',
            'src' => Post::storageUrlFor($src),
            'alt' => (string) ($attrs['alt'] ?? ''),
        ];
    }

    /**
     * @param  array<mixed>  $marks
     * @return list<Node>
     */
    private function marks(array $marks): array
    {
        $normalized = [];

        foreach ($marks as $mark) {
            $type = is_array($mark) ? ($mark['type'] ?? null) : null;

            if (! in_array($type, self::ALLOWED_MARKS, true)) {
                continue;
            }

            if ($type !== 'link') {
                $normalized[] = ['type' => $type];

                continue;
            }

            $attrs = (array) ($mark['attrs'] ?? []);
            $href = $this->safeUrl($attrs['href'] ?? null, ['http', 'https', 'mailto']);

            // An unsafe link keeps its text but loses the link.
            if ($href !== null) {
                $normalized[] = ['type' => 'link', 'href' => $href, 'newTab' => ($attrs['target'] ?? null) === '_blank'];
            }
        }

        return $normalized;
    }

    /**
     * Allow the given schemes and relative URLs; reject everything else
     * (javascript:, data:, protocol-relative //host, obfuscated schemes).
     *
     * @param  list<string>  $schemes
     */
    private function safeUrl(mixed $url, array $schemes): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        // Browsers ignore control characters and whitespace inside schemes.
        $url = trim($url);
        $compact = (string) preg_replace('/[\x00-\x20\x7F]+/', '', $url);

        if ($url === '' || str_starts_with($compact, '//') || str_starts_with($compact, '\\')) {
            return null;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $matches)) {
            return in_array(strtolower($matches[1]), $schemes, true) ? $url : null;
        }

        return $url;
    }

    private function codeLanguage(mixed $language): ?string
    {
        return is_string($language) && preg_match('/^[a-z0-9+#\-]{1,30}$/i', $language) ? $language : null;
    }

    /**
     * @param  array<mixed>  $nodes
     */
    private function plainText(array $nodes): string
    {
        $text = '';

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $text .= match ($node['type'] ?? null) {
                'text' => (string) ($node['text'] ?? ''),
                'hardBreak' => "\n",
                default => $this->plainText((array) ($node['content'] ?? [])),
            };
        }

        return $text;
    }
}
