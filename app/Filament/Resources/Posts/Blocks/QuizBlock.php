<?php

namespace App\Filament\Resources\Posts\Blocks;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Validator;

/**
 * Quiz: a self-check of 1–10 multiple-choice questions, each with 2–6
 * plain-text options, exactly one marked correct (a boolean data flag) and
 * a required explanation shown after answering.
 *
 * Data only: nothing here is ever evaluated or rendered as HTML. The correct
 * answer is part of the public page data by design: this is a learning aid,
 * not an exam. Visitors' answers stay in the browser and are never sent.
 */
class QuizBlock extends PostContentBlock
{
    public const MAX_QUESTIONS = 10;

    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 6;

    public const TITLE_MAX_LENGTH = 120;

    public const QUESTION_MAX_LENGTH = 300;

    public const OPTION_MAX_LENGTH = 200;

    public const EXPLANATION_MAX_LENGTH = 600;

    public static function getId(): string
    {
        return 'quiz';
    }

    public static function getLabel(): string
    {
        return 'Quiz';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('4xl')
            ->modalDescription('Plain text only. Mark exactly one correct answer per question; the explanation is shown to readers after they check their answer.')
            ->schema([
                TextInput::make('title')
                    ->label('Quiz title (optional)')
                    ->placeholder('Check your understanding')
                    ->maxLength(self::TITLE_MAX_LENGTH),
                Repeater::make('questions')
                    ->label('Questions')
                    ->schema([
                        Textarea::make('question')
                            ->required()
                            ->rows(2)
                            ->maxLength(self::QUESTION_MAX_LENGTH),
                        Repeater::make('options')
                            ->label('Answers')
                            ->table([
                                TableColumn::make('Answer')->markAsRequired(),
                                TableColumn::make('Correct'),
                            ])
                            ->schema([
                                TextInput::make('text')
                                    ->required()
                                    ->maxLength(self::OPTION_MAX_LENGTH),
                                Toggle::make('correct')
                                    ->default(false),
                            ])
                            ->minItems(self::MIN_OPTIONS)
                            ->maxItems(self::MAX_OPTIONS)
                            ->defaultItems(self::MIN_OPTIONS)
                            ->required()
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_array($value) || self::correctCount($value) !== 1) {
                                    $fail('Mark exactly one answer as correct.');
                                }
                            }),
                        Textarea::make('explanation')
                            ->required()
                            ->rows(2)
                            ->maxLength(self::EXPLANATION_MAX_LENGTH)
                            ->helperText('Why the correct answer is right. Shown after checking, whatever the reader chose.'),
                    ])
                    ->minItems(1)
                    ->maxItems(self::MAX_QUESTIONS)
                    ->defaultItems(1)
                    ->required(),
            ]);
    }

    public static function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:'.self::TITLE_MAX_LENGTH],
            'questions' => ['required', 'array', 'min:1', 'max:'.self::MAX_QUESTIONS],
            'questions.*' => ['array'],
            ...self::questionRules('questions.*.'),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private static function questionRules(string $prefix = ''): array
    {
        return [
            $prefix.'question' => ['required', 'string', 'max:'.self::QUESTION_MAX_LENGTH],
            $prefix.'options' => ['required', 'array', 'min:'.self::MIN_OPTIONS, 'max:'.self::MAX_OPTIONS],
            $prefix.'options.*' => ['required', 'array'],
            $prefix.'options.*.text' => ['required', 'string', 'max:'.self::OPTION_MAX_LENGTH],
            $prefix.'options.*.correct' => ['required', 'boolean'],
            $prefix.'explanation' => ['required', 'string', 'max:'.self::EXPLANATION_MAX_LENGTH],
        ];
    }

    /**
     * @param  array<mixed>  $options
     */
    private static function correctCount(array $options): int
    {
        return count(array_filter($options, fn (mixed $option): bool => is_array($option) && ($option['correct'] ?? false) === true));
    }

    /**
     * Repeaters are keyed by UUID, so both levels are re-indexed and text is
     * trimmed. `correct` must be a real boolean (no "1"/"true" strings). Each
     * question is validated on its own: invalid ones (no explanation, fewer
     * than two options, not exactly one correct answer) are dropped and the
     * others stay. With none left, rules() fails and the block is dropped.
     */
    protected static function prepareConfig(array $config): array
    {
        if (is_string($config['title'] ?? null)) {
            $config['title'] = trim($config['title']);
        }

        $questions = is_array($config['questions'] ?? null) ? array_values($config['questions']) : [];

        $questions = array_map(function (mixed $question): mixed {
            if (! is_array($question)) {
                return $question;
            }

            foreach (['question', 'explanation'] as $field) {
                if (is_string($question[$field] ?? null)) {
                    $question[$field] = trim($question[$field]);
                }
            }

            if (is_array($question['options'] ?? null)) {
                $question['options'] = array_values(array_map(fn (mixed $option): mixed => is_array($option) ? [
                    'text' => is_string($option['text'] ?? null) ? trim($option['text']) : ($option['text'] ?? null),
                    'correct' => ($option['correct'] ?? false) === true,
                ] : $option, $question['options']));
            }

            return $question;
        }, $questions);

        $config['questions'] = array_slice(array_values(array_filter(
            $questions,
            fn (mixed $question): bool => is_array($question)
                && Validator::make($question, self::questionRules())->passes()
                && self::correctCount($question['options']) === 1,
        )), 0, self::MAX_QUESTIONS);

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        return [
            'title' => ($config['title'] ?? '') ?: null,
            'questions' => array_map(fn (array $question): array => [
                'question' => $question['question'],
                'options' => array_column($question['options'], 'text'),
                'correctIndex' => (int) array_search(true, array_column($question['options'], 'correct'), true),
                'explanation' => $question['explanation'],
            ], $config['questions']),
        ];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return 'Quiz (incomplete)';
        }

        $count = count($node['questions']);

        return 'Quiz — '.$count.' '.($count === 1 ? 'question' : 'questions');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This quiz is incomplete (each question needs text, at least two answers with exactly one correct, and an explanation) and will not be shown. Click to edit.</em></p>';
        }

        return ($node['title'] === null ? '' : '<p><strong>'.e($node['title']).'</strong></p>')
            .'<ol>'.implode('', array_map(
                fn (array $question): string => '<li>'.e($question['question']).' <em>('.count($question['options']).' answers; correct: '.e($question['options'][$question['correctIndex']]).')</em></li>',
                $node['questions'],
            )).'</ol>';
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS): questions,
     * answers and the explanation as static text. The public site renders
     * the node through ArticleQuiz.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return null;
        }

        $questions = array_map(fn (array $question): string => '<li><p>'.e($question['question']).'</p><ul>'
            .implode('', array_map(fn (string $option): string => '<li>'.e($option).'</li>', $question['options']))
            .'</ul><p>Answer: '.e($question['options'][$question['correctIndex']]).'. '.e($question['explanation']).'</p></li>', $node['questions']);

        return ($node['title'] === null ? '' : '<p><strong>'.e($node['title']).'</strong></p>')
            .'<ol>'.implode('', $questions).'</ol>';
    }
}
