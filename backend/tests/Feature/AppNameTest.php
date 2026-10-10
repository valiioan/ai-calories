<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppNameTest extends TestCase
{
    public function test_the_public_name_comes_from_configuration(): void
    {
        config(['app.name' => 'Nume Test']);

        $this->getJson('/api/app')
            ->assertOk()
            ->assertExactJson(['name' => 'Nume Test']);
    }
}
