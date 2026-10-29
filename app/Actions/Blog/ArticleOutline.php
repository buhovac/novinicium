<?php

namespace App\Actions\Blog;

use Illuminate\Support\Str;

/**
 * Outline of a normalized article (BuildPostContent output): heading
 * anchors, the table of contents and the reading time.
 *
 * Heading IDs are a pure function of the content: headings are visited in
 * document order (depth-first, so headings inside columns count too), each
 * gets the slug of its text, and a repeated slug gets "-2", "-3", … in the
 * order it appears. The same content therefore always yields the same IDs,
 * on the server and in every render. IDs used elsewhere on the page are
 * reserved so an anchor can never collide with them.
 *
 * @phpstan-type Node array<string, mixed>
 * @phpstan-type TocEntry array{id: string, text: string, level: int, children: list<array{id: string, text: string, level: int, children: array{}}>}
 */
class ArticleOutline
{
    /** IDs owned by the layout (skip link target) or Vue's useId() ("v-0"). */
    private const RESERVED_ID_PATTERN = '/^(main|app|v-\d+)$/';

    private const FALLBACK_ID = 'section';

    public const WORDS_PER_MINUTE = 200;

    /**
     * @param  list<Node>  $nodes
     * @return list<Node>
     */
    public function withHeadingIds(array $nodes): array
    {
        $usedIds = [];

        return $this->assignIds($nodes, $usedIds);
    }

    /**
     * @param  array<mixed>  $nodes
     * @param  array<string, true>  $usedIds
     * @return list<Node>
     */
    private function assignIds(array $nodes, array &$usedIds): array
    {
        foreach ($nodes as $index => $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['type'] ?? null) === 'heading') {
                $nodes[$index]['id'] = $this->uniqueId($this->headingText($node), $usedIds);

                continue;
            }

            if (is_array($node['content'] ?? null)) {
                $nodes[$index]['content'] = $this->assignIds($node['content'], $usedIds);
            }
        }

        return array_values($nodes);
    }

    /**
     * @param  array<string, true>  $usedIds
     */
    private function uniqueId(string $text, array &$usedIds): string
    {
        $base = Str::slug($text) ?: self::FALLBACK_ID;

        if (preg_match(self::RESERVED_ID_PATTERN, $base) === 1) {
            $base .= '-section';
        }

        $id = $base;

        for ($suffix = 2; isset($usedIds[$id]); $suffix++) {
            $id = "{$base}-{$suffix}";
        }

        $usedIds[$id] = true;

        return $id;
    }

    /**
     * Nested H2 > H3 entries in document order. An H3 before any H2 becomes
     * a top-level entry rather than being dropped.
     *
     * @param  list<Node>  $nodes  normalized nodes that already have heading IDs
     * @return list<TocEntry>
     */
    public function tableOfContents(array $nodes): array
    {
        $entries = [];

        foreach ($this->headings($nodes) as $heading) {
            $entry = [...$heading, 'children' => []];

            if ($heading['level'] === 3 && $entries !== [] && $entries[array_key_last($entries)]['level'] === 2) {
                $entries[array_key_last($entries)]['children'][] = $entry;
            } else {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Headings (with IDs) in document order, as plain id/text/level data.
     *
     * @param  array<mixed>  $nodes
     * @return list<array{id: string, text: string, level: int}>
     */
    private function headings(array $nodes): array
    {
        $headings = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['type'] ?? null) === 'heading' && is_string($node['id'] ?? null)) {
                $headings[] = [
                    'id' => $node['id'],
                    'text' => $this->headingText($node),
                    'level' => ($node['level'] ?? 2) === 3 ? 3 : 2,
                ];
            } elseif (is_array($node['content'] ?? null)) {
                array_push($headings, ...$this->headings($node['content']));
            }
        }

        return $headings;
    }

    /**
     * @param  Node  $heading
     */
    public function headingText(array $heading): string
    {
        return trim((string) preg_replace('/\s+/', ' ', implode('', array_map(
            fn (mixed $inline): string => is_array($inline) && ($inline['type'] ?? null) === 'text' ? (string) ($inline['text'] ?? '') : ' ',
            (array) ($heading['content'] ?? []),
        ))));
    }

    /**
     * Minutes to read the article's visible prose: text nodes, headings,
     * lists, tables, callouts, captions, statistics and quiz text. Code,
     * image paths and alt text, and chart data are not counted.
     *
     * @param  list<Node>  $nodes
     */
    public function readingMinutes(array $nodes): int
    {
        $words = preg_split('/\s+/u', trim(implode(' ', $this->readableText($nodes))), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / self::WORDS_PER_MINUTE));
    }

    /**
     * @param  array<mixed>  $nodes
     * @return list<string>
     */
    public function readableText(array $nodes): array
    {
        $texts = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $fields = match ($node['type'] ?? null) {
                'text' => [$node['text'] ?? null],
                'image' => [$node['caption'] ?? null, $node['credit'] ?? null],
                'table' => [$node['caption'] ?? null],
                'callout' => [$node['title'] ?? null, ...(array) ($node['paragraphs'] ?? [])],
                'statistics' => array_merge(...array_map(
                    fn (mixed $item): array => is_array($item) ? [$item['value'] ?? null, $item['unit'] ?? null, $item['label'] ?? null, $item['explanation'] ?? null] : [],
                    (array) ($node['items'] ?? []),
                ) ?: [[]]),
                'imageComparison' => [$node['first']['label'] ?? null, $node['second']['label'] ?? null, $node['caption'] ?? null],
                'chart' => [$node['title'] ?? null, $node['caption'] ?? null],
                'quiz' => [$node['title'] ?? null, ...array_merge(...array_map(
                    fn (mixed $question): array => is_array($question) ? [$question['question'] ?? null, ...(array) ($question['options'] ?? []), $question['explanation'] ?? null] : [],
                    (array) ($node['questions'] ?? []),
                ) ?: [[]])],
                default => [],
            };

            array_push($texts, ...array_filter($fields, fn (mixed $field): bool => is_string($field) && $field !== ''));

            if (($node['type'] ?? null) !== 'code' && is_array($node['content'] ?? null)) {
                array_push($texts, ...$this->readableText($node['content']));
            }
        }

        return $texts;
    }
}
