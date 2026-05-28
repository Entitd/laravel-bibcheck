<?php

use App\Models\CourseRequirement;

test('it returns supported bibtex types', function () {
    $response = $this->getJson('/api/bib/types');

    $response
        ->assertOk()
        ->assertJsonFragment([
            'name' => 'article',
        ])
        ->assertJsonFragment([
            'required_fields' => ['author', 'title', 'journal', 'year', 'pages', 'volume', 'number'],
        ]);
});

test('it returns fields for a supported bibtex type', function () {
    $response = $this->getJson('/api/bib/types/article/fields');

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'article')
        ->assertJsonPath('data.required_fields.0', 'author')
        ->assertJsonPath('data.recommended_fields.0', 'language');
});

test('it returns department requirements', function () {
    CourseRequirement::create([
        'course_number' => 2,
        'min_total_quantity' => 16,
        'min_foreign_lang' => 4,
        'min_current_periodicals' => 3,
        'min_21st_century' => 10,
    ]);

    $response = $this->getJson('/api/requirements');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.course_number', 2)
        ->assertJsonPath('data.0.min_total_quantity', 16);
});
