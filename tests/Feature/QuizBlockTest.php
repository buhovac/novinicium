<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\QuizBlock;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function quizQuestion(array $overrides = []): array
{
    return [
        'question' => 'Which ratio does WCAG AA require for normal text?',
        'options' => [
            'uuid-a' => ['text' => '3:1', 'correct' => false],
            'uuid-b' => ['text' => '4.5:1', 'correct' => true],
        ],
        'explanation' => 'Success criterion 1.4.3 requires 4.5:1.',
        ...$overrides,
    ];
}

/**
 * @param  list<array<string, mixed>>|null  $questions
 * @return array<string, mixed>
 */
function quizConfig(?array $questions = null, ?string $title = 'Check your understanding'): array
{
    return ['title' => $title, 'questions' => $questions ?? ['uuid-q' => quizQuestion()]];
}

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>|null
 */
function normalizedQuiz(array|string|null $config): ?array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => [customBlockNode('quiz', $config)]]]);

    return app(BuildPostContent::class)->handle($post)[0] ?? null;
}

/**
 * Server-render one question in a given (browser-only) state.
 *
 * @param  array<string, mixed>  $question  normalized question node
 */
function renderQuizQuestion(array $question, ?int $selected, string $status): string
{
    return squish(renderBlogComponent('QuizQuestion', [
        'question' => $question,
        'number' => 1,
        'total' => 2,
        'selected' => $selected,
        'status' => $status,
    ]));
}

/**
 * The visible/announced text of the live result region.
 */
function quizResultText(string $html): string
{
    preg_match('/<div class="ni-quiz-result" aria-live="polite" aria-atomic="true">(.*?)<\/div><\/fieldset>/', $html, $match);

    // Paragraphs are separate for screen readers; keep them separate here.
    return squish(strip_tags(str_replace('</p>', ' </p>', $match[1] ?? 'MISSING REGION')));
}

it('normalizes a valid quiz into plain-text data with a correct index', function () {
    expect(normalizedQuiz(quizConfig()))->toBe([
        'type' => 'quiz',
        'title' => 'Check your understanding',
        'questions' => [[
            'question' => 'Which ratio does WCAG AA require for normal text?',
            'options' => ['3:1', '4.5:1'],
            'correctIndex' => 1,
            'explanation' => 'Success criterion 1.4.3 requires 4.5:1.',
        ]],
    ]);
});

it('treats the title as optional', function () {
    expect(normalizedQuiz(quizConfig(title: '  '))['title'])->toBeNull();
});

it('drops an invalid question and keeps the valid ones', function (mixed $invalid) {
    $node = normalizedQuiz(quizConfig([quizQuestion(['question' => 'Valid one']), $invalid, quizQuestion(['question' => 'Valid two'])]));

    expect(array_column($node['questions'], 'question'))->toBe(['Valid one', 'Valid two']);
})->with([
    'missing explanation' => [quizQuestion(['explanation' => ''])],
    'whitespace explanation' => [quizQuestion(['explanation' => '   '])],
    'missing question text' => [quizQuestion(['question' => null])],
    'one option' => [quizQuestion(['options' => [['text' => 'Only', 'correct' => true]]])],
    'no correct option' => [quizQuestion(['options' => [['text' => 'A', 'correct' => false], ['text' => 'B', 'correct' => false]]])],
    'two correct options' => [quizQuestion(['options' => [['text' => 'A', 'correct' => true], ['text' => 'B', 'correct' => true]]])],
    'correct as a string' => [quizQuestion(['options' => [['text' => 'A', 'correct' => 'true'], ['text' => 'B', 'correct' => false]]])],
    'empty option text' => [quizQuestion(['options' => [['text' => '', 'correct' => true], ['text' => 'B', 'correct' => false]]])],
    'too many options' => [quizQuestion(['options' => [...array_fill(0, QuizBlock::MAX_OPTIONS, ['text' => 'x', 'correct' => false]), ['text' => 'y', 'correct' => true]]])],
    'options not a list' => [quizQuestion(['options' => 'A, B'])],
    'not an array' => ['question'],
]);

it('drops malformed quiz blocks without breaking the article', function (array|string|null $config) {
    $post = postWithBody([paragraphNode('Before'), customBlockNode('quiz', $config), paragraphNode('After')]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(2);
})->with([
    'null' => [null],
    'string' => ['x'],
    'empty' => [[]],
    'no questions' => [quizConfig([])],
    'only invalid questions' => [quizConfig([quizQuestion(['explanation' => ''])])],
]);

it('caps the quiz at the maximum number of questions', function () {
    $questions = array_fill(0, QuizBlock::MAX_QUESTIONS + 2, quizQuestion());

    expect(normalizedQuiz(quizConfig($questions))['questions'])->toHaveCount(QuizBlock::MAX_QUESTIONS);
});

it('writes questions, answers and explanations into the html snapshot as text', function () {
    $post = postWithBody([customBlockNode('quiz', quizConfig([quizQuestion(['explanation' => '<script>alert(1)</script>'])]))]);

    expect($post->body)
        ->toContain('<li>3:1</li><li>4.5:1</li>')
        ->toContain('Answer: 4.5:1. &lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>');
});

it('shows a preview card in the editor', function () {
    expect(QuizBlock::getPreviewLabel(quizConfig()))->toBe('Quiz — 1 question')
        ->and(QuizBlock::getPreviewLabel(quizConfig([])))->toBe('Quiz (incomplete)')
        ->and(QuizBlock::toPreviewHtml(quizConfig()))->toContain('(2 answers; correct: 4.5:1)')
        ->not->toContain('<input')
        ->and(QuizBlock::toPreviewHtml(quizConfig([quizQuestion(['question' => '<b>x</b>'])])))->toContain('&lt;b&gt;x&lt;/b&gt;');
});

describe('rendered markup', function () {
    it('server-renders a fieldset/legend radio group per question and a real button', function () {
        $html = squish(renderBlogContent([normalizedQuiz(quizConfig([quizQuestion(), quizQuestion(['question' => 'Second?'])]))]));

        expect($html)
            ->toMatch('/<section class="ni-quiz" aria-labelledby="([^"]+)"><p id="\1" class="ni-quiz-title">Check your understanding<\/p>/')
            ->and(substr_count($html, '<fieldset class="ni-quiz-question">'))->toBe(2)
            ->and($html)->toContain('<legend><span class="ni-quiz-number">Question 1 of 2</span> Which ratio does WCAG AA require for normal text?</legend>')
            ->toContain('<legend><span class="ni-quiz-number">Question 2 of 2</span> Second?</legend>')
            ->and(substr_count($html, '<button type="button" class="ni-quiz-check">'))->toBe(2)
            ->and(substr_count($html, '<div class="ni-quiz-result" aria-live="polite" aria-atomic="true"></div>'))->toBe(2);

        preg_match_all('/<input id="([^"]+)" type="radio" name="([^"]+)" value="(\d)"><label for="([^"]+)">([^<]+)<\/label>/', $html, $radios, PREG_SET_ORDER);

        expect($radios)->toHaveCount(4)
            ->and(array_map(fn (array $radio): string => trim($radio[5]), $radios))->toBe(['3:1', '4.5:1', '3:1', '4.5:1'])
            ->and(array_unique(array_column($radios, 2)))->toHaveCount(2);

        foreach ($radios as $radio) {
            expect($radio[4])->toBe($radio[1])->and($radio[1])->toStartWith($radio[2]);
        }
    });

    it('uses a generic name when the quiz has no title', function () {
        expect(renderBlogContent([normalizedQuiz(quizConfig(title: null))]))
            ->toContain('<section class="ni-quiz" aria-label="Quiz">')
            ->not->toContain('ni-quiz-title');
    });

    it('does not reveal the result before the reader checks', function () {
        $html = renderQuizQuestion(normalizedQuiz(quizConfig())['questions'][0], 0, 'open');

        expect(quizResultText($html))->toBe('')
            ->and($html)->not->toContain('ni-quiz-tag')
            ->not->toContain('data-result');
    });

    it('announces a correct answer in text with the explanation', function () {
        $html = renderQuizQuestion(normalizedQuiz(quizConfig())['questions'][0], 1, 'checked');

        expect(quizResultText($html))->toBe('Correct. Success criterion 1.4.3 requires 4.5:1.')
            ->and($html)->toContain('4.5:1 <span class="ni-quiz-tag">(your answer, correct)</span>');
    });

    it('announces an incorrect answer in text, naming the correct one', function () {
        $html = renderQuizQuestion(normalizedQuiz(quizConfig())['questions'][0], 0, 'checked');

        expect(quizResultText($html))->toBe('Not quite. The correct answer is: 4.5:1. Success criterion 1.4.3 requires 4.5:1.')
            ->and($html)
            ->toContain('3:1 <span class="ni-quiz-tag">(your answer)</span>')
            ->toContain('4.5:1 <span class="ni-quiz-tag">(correct answer)</span>');
    });

    it('asks for a choice when checking without one', function () {
        $html = renderQuizQuestion(normalizedQuiz(quizConfig())['questions'][0], null, 'needsAnswer');

        expect(quizResultText($html))->toBe('Choose an answer first.');
    });

    it('keeps script in question, option and explanation as inert text', function () {
        $node = normalizedQuiz(quizConfig([quizQuestion([
            'question' => '<script>alert("q")</script>',
            'options' => [
                ['text' => '<img src=x onerror=alert(1)>', 'correct' => true],
                ['text' => 'B', 'correct' => false],
            ],
            'explanation' => '<script>alert("e")</script>',
        ])]));

        // Initial server markup plus the revealed (checked) state.
        $html = renderBlogContent([$node]).renderQuizQuestion($node['questions'][0], 1, 'checked');

        expect($html)
            ->not->toContain('<script')
            ->not->toContain('<img')
            ->toContain('&lt;script&gt;alert(&quot;q&quot;)&lt;/script&gt;')
            ->toContain('&lt;img src=x onerror=alert(1)&gt;')
            ->toContain('&lt;script&gt;alert(&quot;e&quot;)&lt;/script&gt;');
    });
});

it('never renders quiz text with v-html', function () {
    foreach (['ArticleQuiz.vue', 'QuizQuestion.vue'] as $component) {
        expect((string) file_get_contents(resource_path("js/components/blog/{$component}")))->not->toMatch('/\sv-html=/');
    }
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
     * Fill the default (UUID-keyed) question and option rows like an author.
     *
     * @param  array{question: string, options: list<array{text: string, correct: bool}>, explanation: string}  $question
     */
    function callQuizAction(Post $post, array $question): mixed
    {
        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->mountAction(
                TestAction::make('customBlock')
                    ->schemaComponent('body_json')
                    ->arguments(['id' => 'quiz', 'mode' => 'insert', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]]),
            );

        $questionKey = array_key_first($component->get('mountedActions.0.data.questions'));
        $optionKeys = array_keys($component->get("mountedActions.0.data.questions.{$questionKey}.options"));
        $options = [];

        foreach ($question['options'] as $position => $option) {
            $options[$optionKeys[$position] ?? "extra-{$position}"] = $option;
        }

        return $component
            ->set("mountedActions.0.data.questions.{$questionKey}", [...$question, 'options' => $options])
            ->callMountedAction();
    }

    /**
     * @return list<string>
     */
    function quizErrorKeys(mixed $component): array
    {
        return array_map(
            fn (string $key): string => (string) preg_replace('/(questions|options)\.[^.]+/', '$1.*', $key),
            array_keys($component->errors()->toArray()),
        );
    }

    it('requires question text and an explanation', function () {
        $component = callQuizAction($this->post, [
            'question' => '',
            'options' => [['text' => 'A', 'correct' => true], ['text' => 'B', 'correct' => false]],
            'explanation' => '',
        ]);

        expect(quizErrorKeys($component))->toContain(
            'mountedActions.0.data.questions.*.question',
            'mountedActions.0.data.questions.*.explanation',
        );
    });

    it('requires exactly one correct answer', function (bool $first, bool $second) {
        $component = callQuizAction($this->post, [
            'question' => 'Q',
            'options' => [['text' => 'A', 'correct' => $first], ['text' => 'B', 'correct' => $second]],
            'explanation' => 'E',
        ]);

        expect($component->errors()->toArray())->toHaveKey(
            'mountedActions.0.data.questions.'.array_key_first($component->get('mountedActions.0.data.questions')).'.options',
        );
    })->with(['none' => [false, false], 'both' => [true, true]]);

    it('requires at least two answers', function () {
        $component = callQuizAction($this->post, [
            'question' => 'Q',
            'options' => [['text' => 'A', 'correct' => true]],
            'explanation' => 'E',
        ]);

        expect(quizErrorKeys($component))->toBe(['mountedActions.0.data.questions.*.options']);
    });

    it('inserts a valid quiz whose stored config the normalizer accepts', function () {
        callQuizAction($this->post, [
            'question' => 'Which element groups radio buttons?',
            'options' => [['text' => 'div', 'correct' => false], ['text' => 'fieldset', 'correct' => true], ['text' => 'span', 'correct' => false]],
            'explanation' => 'A fieldset with a legend names the group.',
        ])
            ->assertHasNoActionErrors()
            ->assertDispatched('run-rich-editor-commands', function (string $event, array $params): bool {
                $node = QuizBlock::normalize($params['commands'][0]['arguments'][0]['attrs']['config']);

                return $node !== null
                    && $node['questions'][0]['options'] === ['div', 'fieldset', 'span']
                    && $node['questions'][0]['correctIndex'] === 1;
            });
    });

    it('round-trips the demo quiz: seed, reopen, edit, save, render', function () {
        $this->seed(DemoArticleSeeder::class);
        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $post->update(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
        $document = $component->get('data.body_json');
        $index = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['id'] ?? null) === 'quiz');
        $questions = $document['content'][$index]['attrs']['config']['questions'];

        expect($questions)->toHaveCount(3)
            ->and($questions[0]['options'][1])->toBe(['text' => '1.4.1 Use of Color', 'correct' => true]);

        $document['content'][$index]['attrs']['config']['questions'][1]['explanation'] = 'Edited explanation.';

        $component
            ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('blog.show', $post->refresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
                $quiz = collect($content)->firstWhere('type', 'quiz');

                return $quiz['title'] === 'Check your understanding'
                    && count($quiz['questions']) === 3
                    && $quiz['questions'][1]['explanation'] === 'Edited explanation.'
                    && $quiz['questions'][2]['correctIndex'] === 1;
            }));
    });
});

it('completes the demo article: all nine block types are present', function () {
    $this->seed(DemoArticleSeeder::class);
    $types = collect(app(BuildPostContent::class)->handle(Post::where('slug', DemoArticleSeeder::SLUG)->sole()))->pluck('type');

    expect($types)->toContain('callout', 'image', 'code', 'table', 'grid', 'statistics', 'imageComparison', 'chart', 'quiz')
        ->and(Post::where('slug', DemoArticleSeeder::SLUG)->sole()->published_at)->toBeNull();
});
