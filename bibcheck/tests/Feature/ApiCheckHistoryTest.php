<?php

use App\Models\CheckHistory;
use App\Models\User;

test('it returns authenticated user check history as json', function () {
    $user = User::factory()->create();

    CheckHistory::create([
        'user_id' => $user->id,
        'filename' => 'example.bib',
        'content' => '@article{example}',
        'status' => 'completed',
        'stats' => ['totalQuantity' => 1],
        'analysis_data' => ['entries' => []],
        'verdict' => 'ok',
        'total_entries' => 1,
        'error_count' => 0,
        'warning_count' => 0,
    ]);

    $response = $this->actingAs($user)->getJson('/api/check-history');

    $response
        ->assertOk()
        ->assertJsonPath('0.filename', 'example.bib')
        ->assertJsonPath('0.total_entries', 1);
});
