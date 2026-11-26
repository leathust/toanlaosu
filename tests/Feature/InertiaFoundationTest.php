<?php

it('renders the inertia pilot with typed shared props', function () {
    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
    ])->get(route('ui.inertia-pilot'));

    $response->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Ui/InertiaPilot')
        ->assertJsonPath('props.pilot.status', 'ready-for-vertical-slice')
        ->assertJsonPath('props.app.name', config('app.name'));
});
