<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FoodSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_caches_branded_food_per_100g_and_hides_the_key(): void
    {
        config([
            'services.usda.key' => 'usda-secret-key',
            'services.usda.base_url' => 'https://api.nal.usda.gov/fdc',
        ]);
        Http::fake([
            'https://api.nal.usda.gov/*' => Http::response([
                'foods' => [[
                    'fdcId' => 171477,
                    'description' => 'Yogurt, plain',
                    'dataType' => 'Branded',
                    'brandOwner' => 'Example',
                    'servingSize' => 150,
                    'servingSizeUnit' => 'g',
                    'householdServingFullText' => '1 container',
                    'foodNutrients' => [
                        ['nutrientNumber' => '208', 'value' => 150],
                        ['nutrientNumber' => '203', 'value' => 12],
                        ['nutrientNumber' => '205', 'value' => 9],
                        ['nutrientNumber' => '204', 'value' => 4.5],
                        ['nutrientNumber' => '291', 'value' => 0],
                    ],
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        $response = $this->getJson('/api/foods/search?q=yogurt')
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Yogurt, plain')
            ->assertJsonPath('data.0.energy_kcal_per_100g', 100);

        $this->assertStringNotContainsString('usda-secret-key', $response->getContent());
        $this->assertSame(100.0, (float) Food::query()->first()->energy_kcal);
        $this->assertSame(150.0, (float) Food::query()->first()->gram_weight);
    }

    public function test_another_user_cannot_see_a_missing_food(): void
    {
        $user = User::factory()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        $this->getJson('/api/foods/999')->assertNotFound();
    }
}
