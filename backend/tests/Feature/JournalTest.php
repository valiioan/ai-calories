<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_manual_meal_is_private_and_uses_the_local_day(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-10 21:30:00', 'UTC'));
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->ensureProfile();

        $this->postJson('/login', ['email' => $owner->email, 'password' => 'password'])->assertOk();

        $created = $this->postJson('/api/meals', [
            'eaten_on' => '2026-10-11',
            'time' => '00:30',
            'meal_type' => 'snack',
            'original_text' => 'un măr',
            'assumptions' => 'Porție medie.',
            'items' => [[
                'custom_name' => 'Măr',
                'quantity' => 1,
                'unit' => 'piece',
                'energy_kcal' => 95,
                'protein_g' => 0.3,
                'carbohydrate_g' => 25,
                'fat_g' => 0.2,
            ]],
        ])->assertCreated()
            ->assertJsonPath('date', '2026-10-11')
            ->assertJsonPath('time', '00:30')
            ->assertJsonPath('original_text', 'un măr')
            ->assertJsonPath('items.0.unit', 'serving')
            ->assertJsonPath('items.0.nutrition_source', 'manual');

        $mealId = $created->json('id');
        $this->getJson('/api/meals?date=2026-10-11')->assertOk()->assertJsonPath('0.id', $mealId);
        $this->getJson('/api/meals?date=2026-10-10')->assertOk()->assertJsonCount(0);

        $this->postJson('/logout')->assertNoContent();
        Auth::forgetGuards();
        $this->postJson('/login', ['email' => $other->email, 'password' => 'password'])->assertOk();
        $this->getJson('/api/meals/'.$mealId)->assertNotFound();
        $this->deleteJson('/api/meals/'.$mealId)->assertNotFound();
        $this->assertNotNull(Meal::query()->find($mealId));
    }

    public function test_undo_restores_a_deleted_meal(): void
    {
        $user = User::factory()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $mealId = $this->postJson('/api/meals', $this->mealBody())->assertCreated()->json('id');
        $revisionId = $this->deleteJson('/api/meals/'.$mealId)->assertOk()->json('revision_id');

        $this->assertNull(Meal::query()->find($mealId));
        $this->postJson('/api/journal-revisions/'.$revisionId.'/undo')->assertOk();
        $this->assertSame(1, $user->meals()->count());
        $this->assertNotNull($user->revisions()->find($revisionId)->undone_at);
    }

    public function test_summary_for_an_empty_day_is_zero(): void
    {
        $user = User::factory()->create();
        $user->ensureProfile()->forceFill(['calorie_target' => 2200])->save();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

        $this->getJson('/api/summary?date=2026-10-11')
            ->assertOk()
            ->assertJsonPath('energy_in_kcal', 0)
            ->assertJsonPath('energy_out_kcal', 0)
            ->assertJsonPath('weight_kg', null)
            ->assertJsonPath('calorie_target', 2200);
    }

    /**
     * @return array<string, mixed>
     */
    private function mealBody(): array
    {
        return [
            'eaten_on' => '2026-10-11',
            'time' => '13:00',
            'meal_type' => 'lunch',
            'items' => [[
                'custom_name' => 'Orez',
                'quantity' => 200,
                'unit' => 'g',
                'energy_kcal' => 260,
                'protein_g' => 5,
                'carbohydrate_g' => 56,
                'fat_g' => 1,
            ]],
        ];
    }
}
