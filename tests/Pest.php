<?php

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Blog content helpers
|--------------------------------------------------------------------------
*/

/**
 * @return array<string, mixed>
 */
function paragraphNode(string $text): array
{
    return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
}

/**
 * @param  array<int, array<string, mixed>>  $content
 */
function postWithBody(array $content): Post
{
    return Post::factory()->create(['body_json' => ['type' => 'doc', 'content' => $content]]);
}

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>
 */
function customBlockNode(string $id, array|string|null $config): array
{
    return ['type' => 'customBlock', 'attrs' => ['id' => $id, 'config' => $config]];
}
