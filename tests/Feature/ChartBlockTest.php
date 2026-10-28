<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\ChartBlock;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function chartConfig(array $overrides = []): array
{
    return [
        'title' => 'Vision loss by severity, 2020',
        'type' => 'bar',
        'category_heading' => 'Severity',
        'series_1' => 'People',
        'series_2' => null,
        'series_3' => null,
        'rows' => [
            'uuid-a' => ['label' => 'Blindness', 'value_1' => '43.3'],
            'uuid-b' => ['label' => 'Mild', 'value_1' => 258],
        ],
        'unit' => 'million',
        'source' => 'GBD 2019 (2021)',
        'caption' => 'Central estimates.',
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function multiSeriesChartConfig(): array
{
    return chartConfig([
        'type' => 'line',
        'series_2' => 'Women',
        'series_3' => 'Men',
        'unit' => '%',
        'rows' => [
            ['label' => '2010', 'value_1' => '10', 'value_2' => '11.5', 'value_3' => '8.5'],
            ['label' => '2020', 'value_1' => '12', 'value_2' => '13', 'value_3' => '-1'],
        ],
    ]);
}

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>|null
 */
function normalizedChart(array|string|null $config): ?array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => [customBlockNode('chart', $config)]]]);

    return app(BuildPostContent::class)->handle($post)[0] ?? null;
}

it('normalizes a valid chart into structured data with formatted values', function () {
    expect(normalizedChart(chartConfig()))->toBe([
        'type' => 'chart',
        'chartType' => 'bar',
        'title' => 'Vision loss by severity, 2020',
        'categoryHeading' => 'Severity',
        'categories' => ['Blindness', 'Mild'],
        'series' => [['name' => 'People', 'values' => [43.3, 258], 'display' => ['43.3 million', '258 million']]],
        'unit' => 'million',
        'source' => 'GBD 2019 (2021)',
        'caption' => 'Central estimates.',
    ]);
});

it('keeps multiple series for bar and line charts', function () {
    $node = normalizedChart(multiSeriesChartConfig());

    expect($node['chartType'])->toBe('line')
        ->and(array_column($node['series'], 'name'))->toBe(['People', 'Women', 'Men'])
        ->and($node['series'][2]['values'])->toBe([8.5, -1])
        ->and($node['series'][1]['display'])->toBe(['11.5%', '13%']);
});

it('formats values: thousands separators, word units spaced, symbols attached', function () {
    expect(ChartBlock::formatValue(1234567, null))->toBe('1,234,567')
        ->and(ChartBlock::formatValue(0.125, '%'))->toBe('0.125%')
        ->and(ChartBlock::formatValue(43.3, 'million'))->toBe('43.3 million');
});

it('requires a title and a first series name', function (string $field, mixed $value) {
    expect(normalizedChart(chartConfig([$field => $value])))->toBeNull();
})->with([
    'missing title' => ['title', null],
    'blank title' => ['title', '   '],
    'missing series name' => ['series_1', ''],
]);

it('drops the whole chart when any value is not a finite number', function (mixed $value) {
    $config = chartConfig();
    $config['rows']['uuid-b']['value_1'] = $value;

    expect(normalizedChart($config))->toBeNull();
})->with([
    'text' => 'many',
    'empty' => '',
    'missing' => null,
    'decimal comma' => '43,3',
    'array' => [[1]],
    'infinite' => '1e999',
]);

it('drops the chart when a series has a name but a value is missing', function () {
    $config = multiSeriesChartConfig();
    unset($config['rows'][1]['value_3']);

    expect(normalizedChart($config))->toBeNull();
});

it('ignores values in unused series columns', function () {
    $config = chartConfig();
    $config['rows']['uuid-a']['value_2'] = 'leftover';

    expect(normalizedChart($config)['series'])->toHaveCount(1);
});

it('falls back to a bar chart for an unknown type', function (mixed $type) {
    expect(normalizedChart(chartConfig(['type' => $type]))['chartType'])->toBe('bar');
})->with(['unknown' => 'radar', 'missing' => null, 'not a string' => [['pie']]]);

it('keeps a valid single-series pie or doughnut', function (string $type) {
    expect(normalizedChart(chartConfig(['type' => $type]))['chartType'])->toBe($type);
})->with(['pie', 'doughnut']);

it('falls back to bar when the data cannot be a pie, keeping every value', function (array $overrides) {
    $node = normalizedChart([...multiSeriesChartConfig(), 'type' => 'pie', ...$overrides]);

    expect($node['chartType'])->toBe('bar')
        ->and($node['series'])->not->toBeEmpty();
})->with([
    'several series' => [[]],
    'negative value' => [['series_2' => null, 'series_3' => null, 'rows' => [['label' => 'a', 'value_1' => '-2'], ['label' => 'b', 'value_1' => '5']]]],
    'zero total' => [['series_2' => null, 'series_3' => null, 'rows' => [['label' => 'a', 'value_1' => 0]]]],
]);

it('drops malformed chart blocks without breaking the article', function (array|string|null $config) {
    $post = postWithBody([paragraphNode('Before'), customBlockNode('chart', $config), paragraphNode('After')]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(2);
})->with([
    'null' => [null],
    'string' => ['x'],
    'empty' => [[]],
    'no rows' => [chartConfig(['rows' => []])],
    'rows not a list' => [chartConfig(['rows' => 'Blindness,43'])],
    'row not an array' => [chartConfig(['rows' => ['x']])],
    'row without label' => [chartConfig(['rows' => [['value_1' => 4]]])],
    'too many rows' => [chartConfig(['rows' => array_fill(0, ChartBlock::MAX_ROWS + 1, ['label' => 'a', 'value_1' => 1])])],
]);

it('writes the data table into the html snapshot', function () {
    $post = postWithBody([customBlockNode('chart', chartConfig())]);

    expect($post->body)
        ->toContain('<caption>Vision loss by severity, 2020</caption>')
        ->toContain('<th scope="row">Blindness</th><td>43.3 million</td>')
        ->toContain('Central estimates. Source: GBD 2019 (2021)');
});

it('shows a preview card in the editor, not a chart', function () {
    expect(ChartBlock::getPreviewLabel(chartConfig()))->toBe('Chart — Vision loss by severity, 2020')
        ->and(ChartBlock::getPreviewLabel(chartConfig(['title' => ''])))->toBe('Chart (incomplete)')
        ->and(ChartBlock::toPreviewHtml(chartConfig()))->toContain('Bar chart · 1 series · 2 categories: Blindness, Mild')
        ->not->toContain('<canvas')
        ->and(ChartBlock::toPreviewHtml(chartConfig(['title' => '<b>x</b>'])))->toContain('&lt;b&gt;x&lt;/b&gt;');
});

describe('rendered markup (server-side, no JavaScript)', function () {
    it('renders the data table with caption, scoped headers and values', function () {
        $html = squish(renderBlogContent([normalizedChart(chartConfig())]));

        expect($html)
            ->toMatch('/<div class="ni-table" role="region" tabindex="0" aria-labelledby="([^"]+)"><table><caption id="\1">Vision loss by severity, 2020<\/caption>/')
            ->toContain('<thead><tr><th scope="col">Severity</th><th scope="col">People</th></tr></thead>')
            ->toContain('<tr><th scope="row">Blindness</th><td>43.3 million</td></tr>')
            ->toContain('<tr><th scope="row">Mild</th><td>258 million</td></tr>')
            ->toContain('<figcaption><span>Central estimates.</span><span class="ni-chart-source">Source: GBD 2019 (2021)</span></figcaption>');
    });

    it('renders every series as its own scoped column', function () {
        $html = squish(renderBlogContent([normalizedChart(multiSeriesChartConfig())]));

        expect($html)
            ->toContain('<th scope="col">People</th><th scope="col">Women</th><th scope="col">Men</th>')
            ->toContain('<tr><th scope="row">2010</th><td>10%</td><td>11.5%</td><td>8.5%</td></tr>')
            ->toContain('<tr><th scope="row">2020</th><td>12%</td><td>13%</td><td>-1%</td></tr>');
    });

    it('does not load or draw the chart on the server: the table is the content', function () {
        $html = renderBlogContent([normalizedChart(chartConfig())]);

        expect($html)
            ->toContain('<table>')
            ->not->toContain('<canvas')
            ->not->toContain('ni-chart-visual');
    });

    it('defaults the category heading so no header cell is ever empty', function () {
        $html = squish(renderBlogContent([normalizedChart(chartConfig(['category_heading' => '']))]));

        expect($html)->toContain('<th scope="col">Category</th>');
    });
});

it('keeps Chart.js out of the main bundle: only the chart canvas imports it, lazily', function () {
    $article = (string) file_get_contents(resource_path('js/components/blog/ArticleChart.vue'));

    expect($article)
        ->toContain("defineAsyncComponent(\n    () => import('@/components/blog/ChartCanvas.vue'),\n)")
        ->toContain('<div v-if="canDrawChart" class="ni-chart-visual" aria-hidden="true">')
        ->not->toContain("from 'chart.js'");

    $importers = collect(File::allFiles(resource_path('js')))
        ->filter(fn (SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), "from 'chart.js'"))
        ->map(fn (SplFileInfo $file): string => $file->getFilename())
        ->values()
        ->all();

    expect($importers)->toBe(['ChartCanvas.vue']);
});

describe('filament editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
        Filament::setCurrentPanel('admin');
        Storage::fake('public');
        Storage::disk('public')->put('images/cover.jpg', 'fake');
        $this->post = Post::factory()->create(['image_path' => 'images/cover.jpg']);
    });

    /**
     * @param  array<string, mixed>  $data
     */
    function callChartAction(Post $post, array $data): mixed
    {
        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->mountAction(
                TestAction::make('customBlock')
                    ->schemaComponent('body_json')
                    ->arguments(['id' => 'chart', 'mode' => 'insert', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]]),
            );

        // Fill the repeater's default (UUID-keyed) rows like an author would,
        // adding keys only for extra rows.
        $rowKeys = array_keys($component->get('mountedActions.0.data.rows'));
        $rows = [];

        foreach (array_values($data['rows'] ?? []) as $position => $row) {
            $rows[$rowKeys[$position] ?? "extra-{$position}"] = $row;
        }

        return $component
            ->set('mountedActions.0.data.rows', $rows)
            ->fillForm(array_diff_key($data, ['rows' => true]))
            ->callMountedAction();
    }

    /**
     * Repeater rows are keyed by UUID in the form state.
     *
     * @return list<string>
     */
    function chartErrorKeys(mixed $component): array
    {
        return array_map(
            fn (string $key): string => (string) preg_replace('/rows\.[^.]+\./', 'rows.*.', $key),
            array_keys($component->errors()->toArray()),
        );
    }

    it('requires a title, a series name and numeric values in the form', function () {
        $component = callChartAction($this->post, [
            'title' => '',
            'type' => 'bar',
            'series_1' => '',
            'rows' => [['label' => 'A', 'value_1' => 'lots']],
        ]);

        expect(chartErrorKeys($component))->toContain(
            'mountedActions.0.data.title',
            'mountedActions.0.data.series_1',
            'mountedActions.0.data.rows.*.value_1',
        );
    });

    it('requires a value for every named series', function () {
        $component = callChartAction($this->post, [
            'title' => 'T',
            'type' => 'bar',
            'series_1' => 'A',
            'series_2' => 'B',
            'rows' => [['label' => 'x', 'value_1' => '1', 'value_2' => '']],
        ]);

        expect(chartErrorKeys($component))->toContain('mountedActions.0.data.rows.*.value_2')
            ->not->toContain('mountedActions.0.data.rows.*.value_1');
    });

    it('rejects a second series on a pie chart', function () {
        $component = callChartAction($this->post, [
            'title' => 'T',
            'type' => 'pie',
            'series_1' => 'A',
            'series_2' => 'B',
            'rows' => [['label' => 'x', 'value_1' => '1', 'value_2' => '2']],
        ]);

        expect(chartErrorKeys($component))->toContain('mountedActions.0.data.series_2');
    });

    it('inserts a valid chart whose stored config the normalizer accepts', function () {
        callChartAction($this->post, [
            'title' => 'Readers by device',
            'type' => 'doughnut',
            'series_1' => 'Share',
            'rows' => [['label' => 'Mobile', 'value_1' => '60'], ['label' => 'Desktop', 'value_1' => '40']],
            'unit' => '%',
        ])
            ->assertHasNoActionErrors()
            ->assertDispatched('run-rich-editor-commands', function (string $event, array $params): bool {
                $node = ChartBlock::normalize($params['commands'][0]['arguments'][0]['attrs']['config']);

                return $node !== null
                    && $node['chartType'] === 'doughnut'
                    && $node['categories'] === ['Mobile', 'Desktop']
                    && $node['series'][0]['display'] === ['60%', '40%'];
            });
    });

    it('round-trips the demo chart: seed, reopen, edit a value, save, render', function () {
        $this->seed(DemoArticleSeeder::class);
        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $post->update(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
        $document = $component->get('data.body_json');
        $index = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['id'] ?? null) === 'chart');
        $config = $document['content'][$index]['attrs']['config'];

        expect($config['title'])->toBe('Global vision loss by severity, 2020')
            ->and(array_column($config['rows'], 'value_1'))->toBe([43.3, 295, 258, 510]);

        $document['content'][$index]['attrs']['config']['rows'][0]['value_1'] = '43.4';

        $component
            ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('blog.show', $post->refresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
                $chart = collect($content)->firstWhere('type', 'chart');

                return $chart['chartType'] === 'bar'
                    && $chart['series'][0]['values'] === [43.4, 295, 258, 510]
                    && $chart['series'][0]['display'][0] === '43.4 million';
            }));

        // The re-saved chart still renders its table from the stored data.
        $chart = collect(app(BuildPostContent::class)->handle($post->refresh()))->firstWhere('type', 'chart');
        $html = squish(renderBlogContent([$chart]));

        expect($html)->toContain('<tr><th scope="row">Blindness</th><td>43.4 million</td></tr>')
            ->and($post->body)->toContain('<td>43.4 million</td>');
    });
});
