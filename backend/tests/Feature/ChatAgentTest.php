<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_preview_does_not_save_until_the_user_confirms(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-11 09:00:00', 'Europe/Bucharest'));
        config([
            'services.openai.key' => 'sk-test-secret',
            'services.openai.model' => 'gpt-5.4-mini',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.usda.key' => 'usda-secret-key',
            'services.usda.base_url' => 'https://api.nal.usda.gov/fdc',
        ]);

        $step = 0;
        Http::fake(function ($request) use (&$step) {
            $url = $request->url();
            if (str_contains($url, 'nal.usda.gov')) {
                return Http::response([
                    'foods' => [[
                        'fdcId' => 1001,
                        'description' => 'Chicken, breast, raw',
                        'dataType' => 'Foundation',
                        'foodNutrients' => [
                            ['nutrientNumber' => '208', 'value' => 120],
                            ['nutrientNumber' => '203', 'value' => 22.5],
                            ['nutrientNumber' => '205', 'value' => 0],
                            ['nutrientNumber' => '204', 'value' => 2.6],
                        ],
                    ]],
                ]);
            }

            $step++;
            if ($step === 1) {
                return Http::response([
                    'id' => 'resp_1',
                    'output' => [[
                        'type' => 'function_call',
                        'call_id' => 'call_search',
                        'name' => 'search_food',
                        'arguments' => json_encode(['query' => 'chicken breast', 'page' => 1]),
                    ]],
                ]);
            }
            if ($step === 2) {
                $foodId = \App\Models\Food::query()->value('id');

                return Http::response([
                    'id' => 'resp_2',
                    'output' => [[
                        'type' => 'function_call',
                        'call_id' => 'call_preview',
                        'name' => 'preview_meal',
                        'arguments' => json_encode([
                            'eaten_on' => '2026-10-11',
                            'time' => '13:40',
                            'meal_type' => 'lunch',
                            'original_text' => '200 g piept de pui',
                            'assumptions' => null,
                            'uncertainty' => null,
                            'items' => [[
                                'food_id' => $foodId,
                                'quantity' => 200,
                                'unit' => 'g',
                                'display_name' => 'Piept de pui',
                            ]],
                        ]),
                    ]],
                ]);
            }

            return Http::response([
                'id' => 'resp_3',
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'Am pregătit estimarea.',
                    ]],
                ]],
            ]);
        });

        $user = User::factory()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $conversationId = $this->postJson('/api/conversations', [])->assertCreated()->json('id');

        $reply = $this->postJson('/api/conversations/'.$conversationId.'/messages', [
            'content' => 'La prânz am mâncat 200 g piept de pui',
        ])->assertOk();

        $reply->assertJsonPath('assistant_message.content', 'Am pregătit estimarea.');
        $reply->assertJsonPath('proposal.status', 'pending');
        $reply->assertJsonPath('proposal.payload.items.0.custom_name', 'Piept de pui');
        $reply->assertJsonPath('proposal.payload.items.0.energy_kcal', 240);
        $this->assertStringNotContainsString('sk-test-secret', $reply->getContent());
        $this->assertStringNotContainsString('usda-secret-key', $reply->getContent());
        $this->assertSame(0, Meal::query()->count());

        $proposalId = $reply->json('proposal.id');
        $this->postJson('/api/proposals/'.$proposalId.'/confirm')
            ->assertOk()
            ->assertJsonPath('proposal.status', 'confirmed');
        $this->assertSame(1, $user->meals()->count());
        $this->assertSame('200 g piept de pui', $user->meals()->first()->original_text);
    }

    public function test_unknown_tool_is_rejected_and_delete_waits_for_confirmation(): void
    {
        config(['services.openai.key' => 'sk-test-secret', 'services.openai.model' => 'gpt-5.4-mini']);
        $user = User::factory()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $mealId = $this->postJson('/api/meals', [
            'eaten_on' => '2026-10-11',
            'time' => '08:00',
            'meal_type' => 'breakfast',
            'items' => [[
                'custom_name' => 'Iaurt',
                'quantity' => 150,
                'unit' => 'g',
                'energy_kcal' => 90,
                'protein_g' => 8,
                'carbohydrate_g' => 6,
                'fat_g' => 3,
            ]],
        ])->assertCreated()->json('id');

        $step = 0;
        Http::fake(function () use (&$step, $mealId) {
            $step++;
            if ($step === 1) {
                return Http::response([
                    'id' => 'resp_unknown',
                    'output' => [
                        [
                            'type' => 'function_call',
                            'call_id' => 'call_bad',
                            'name' => 'save_meal',
                            'arguments' => '{}',
                        ],
                        [
                            'type' => 'function_call',
                            'call_id' => 'call_delete',
                            'name' => 'preview_delete',
                            'arguments' => json_encode(['meal_id' => $mealId]),
                        ],
                    ],
                ]);
            }

            return Http::response([
                'id' => 'resp_done',
                'output' => [[
                    'type' => 'message',
                    'content' => [['type' => 'output_text', 'text' => 'Confirmi ștergerea?']],
                ]],
            ]);
        });

        $conversationId = $this->postJson('/api/conversations', [])->json('id');
        $this->postJson('/api/conversations/'.$conversationId.'/messages', ['content' => 'șterge micul dejun'])
            ->assertOk()
            ->assertJsonPath('proposal.kind', 'delete-meal')
            ->assertJsonPath('actions.0.status', 'rejected')
            ->assertJsonPath('actions.0.tool_name', 'save_meal');

        $this->assertNotNull(Meal::query()->find($mealId));
        $this->assertDatabaseHas('agent_actions', ['tool_name' => 'save_meal', 'status' => 'rejected']);
    }

    public function test_confirmation_and_undo_stay_on_the_session_user(): void
    {
        config(['services.openai.key' => 'sk-test-secret']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->postJson('/login', ['email' => $owner->email, 'password' => 'password'])->assertOk();
        $conversationId = $this->postJson('/api/conversations', [])->json('id');
        $proposal = $owner->proposals()->create([
            'conversation_id' => $conversationId,
            'kind' => 'weight',
            'status' => 'pending',
            'payload' => ['recorded_on' => '2026-10-11', 'weight_kg' => 72.4, 'note' => null],
        ]);
        $owner->conversations()->find($conversationId)->messages()->create([
            'role' => 'assistant',
            'content' => 'Confirmă greutatea.',
            'proposal_id' => $proposal->id,
        ]);

        $this->postJson('/logout')->assertNoContent();
        Auth::forgetGuards();
        $this->postJson('/login', ['email' => $other->email, 'password' => 'password'])->assertOk();
        $this->postJson('/api/proposals/'.$proposal->id.'/confirm')->assertNotFound();
        $this->assertSame(0, $other->weights()->count());
        $this->assertSame('pending', $proposal->refresh()->status);

        $this->postJson('/logout')->assertNoContent();
        Auth::forgetGuards();
        $this->postJson('/login', ['email' => $owner->email, 'password' => 'password'])->assertOk();
        $confirmed = $this->postJson('/api/conversations/'.$conversationId.'/messages', ['content' => 'da'])
            ->assertOk()
            ->assertJsonPath('proposal.status', 'confirmed');
        $this->assertSame(72.4, (float) $owner->weights()->first()->weight_kg);
        $this->assertSame(0, $other->weights()->count());

        $this->postJson('/api/journal-revisions/'.$confirmed->json('assistant_message.revision_id').'/undo')->assertOk();
        $this->assertSame(0, $owner->weights()->count());
    }

    public function test_yesterday_follows_bucharest_when_utc_is_still_the_previous_day(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-10 21:30:00', 'UTC'));
        config(['services.openai.key' => 'sk-test-secret', 'services.openai.model' => 'gpt-5.4-mini']);
        $user = User::factory()->create();
        $user->ensureProfile();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        Http::fake([
            'https://api.openai.com/v1/responses' => function ($request) {
                $instructions = $request->data()['instructions'] ?? '';
                $this->assertStringContainsString('Today for this user is 2026-10-11', $instructions);
                $this->assertStringContainsString('Yesterday is 2026-10-10', $instructions);
                $this->assertStringContainsString('Europe/Bucharest', $instructions);

                return Http::response([
                    'id' => 'resp_day',
                    'output' => [[
                        'type' => 'message',
                        'content' => [['type' => 'output_text', 'text' => 'Azi este 11 octombrie.']],
                    ]],
                ]);
            },
        ]);

        $conversationId = $this->postJson('/api/conversations', [])->json('id');
        $this->postJson('/api/conversations/'.$conversationId.'/messages', ['content' => 'ce zi este azi?'])
            ->assertOk()
            ->assertJsonPath('assistant_message.content', 'Azi este 11 octombrie.');
    }
}
