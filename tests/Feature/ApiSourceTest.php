<?php

use Illuminate\Support\Facades\Http;

test('it verifies one source by fields', function () {
    config(['services.openalex.url' => 'https://api.openalex.org/']);

    Http::fake([
        'https://api.openalex.org/works*' => Http::response([
            'results' => [
                [
                    'title' => 'Example Article',
                    'publication_year' => 2020,
                    'authorships' => [
                        ['author' => ['display_name' => 'John Smith']],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->postJson('/api/sources/verify', [
        'fields' => [
            'title' => 'Example Article',
            'author' => 'Smith, John',
            'year' => '2020',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.entry', null)
        ->assertJsonPath('data.api_check.found', true)
        ->assertJsonPath('data.api_check.external_title', 'Example Article');
});

test('it verifies one source by bibtex text', function () {
    config(['services.openalex.url' => 'https://api.openalex.org/']);

    Http::fake([
        'https://api.openalex.org/works*' => Http::response([
            'results' => [
                [
                    'title' => 'Example Article',
                    'publication_year' => 2020,
                    'authorships' => [
                        ['author' => ['display_name' => 'John Smith']],
                    ],
                ],
            ],
        ]),
    ]);

    $bib = <<<'BIB'
@article{smith2020,
  title = {Example Article},
  author = {Smith, John},
  year = {2020}
}
BIB;

    $response = $this->postJson('/api/sources/verify', [
        'text' => $bib,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.entry.key', 'smith2020')
        ->assertJsonPath('data.api_check.found', true);
});
