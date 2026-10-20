<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Feature;

use Domain\Finance\Tests\Concerns\FinanceFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpensesTest extends TestCase
{
    use FinanceFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToLocal('2026-10-15 22:30');
    }

    public function test_creates_edits_removes_and_restores_a_fixed_expense(): void
    {
        // Arrange
        $this->actingAsBarber();
        $created = $this->postJson('/api/finance/fixed-expenses', ['name' => ' Aluguel ', 'amount_in_cents' => 150_000, 'due_day' => 5]);
        $id = $created->json('id');

        // Act
        $updated = $this->putJson("/api/finance/fixed-expenses/{$id}", ['name' => 'Aluguel da cadeira', 'amount_in_cents' => 160_000, 'due_day' => 6]);
        $deleted = $this->deleteJson("/api/finance/fixed-expenses/{$id}");
        $afterDelete = $this->getJson('/api/finance/expenses');
        $restored = $this->postJson("/api/finance/fixed-expenses/{$id}/restore");

        // Assert
        $created->assertCreated()->assertJsonPath('name', 'Aluguel');
        $updated->assertOk()->assertJson(['id' => $id, 'name' => 'Aluguel da cadeira', 'amount_in_cents' => 160_000, 'due_day' => 6]);
        $deleted->assertNoContent();
        $afterDelete->assertJsonPath('fixed', []);
        $restored->assertJsonPath('amount_in_cents', 160_000);
        $this->getJson('/api/finance/expenses')->assertJsonPath('fixed.0.id', $id);
    }

    public function test_creates_edits_removes_and_restores_a_one_off_expense(): void
    {
        // Arrange
        $this->actingAsBarber();
        $id = $this->postJson('/api/finance/one-off-expenses', ['description' => 'Lâminas', 'amount_in_cents' => 2100, 'date' => '2026-10-02'])->json('id');

        // Act
        $updated = $this->putJson("/api/finance/one-off-expenses/{$id}", ['description' => 'Lâminas e pomadas', 'amount_in_cents' => 21_000, 'date' => '2026-10-03']);
        $this->deleteJson("/api/finance/one-off-expenses/{$id}")->assertNoContent();
        $afterDelete = $this->getJson('/api/finance/expenses');
        $this->postJson("/api/finance/one-off-expenses/{$id}/restore")->assertSuccessful();

        // Assert
        $updated->assertOk()->assertJson(['description' => 'Lâminas e pomadas', 'amount_in_cents' => 21_000, 'date' => '2026-10-03']);
        $afterDelete->assertJsonPath('one_off', []);
        $this->getJson('/api/finance/expenses')->assertJsonPath('one_off.0.date', '2026-10-03');
    }

    public function test_lists_the_fixed_expenses_by_due_day_and_the_one_off_of_this_month(): void
    {
        // Arrange
        $this->actingAsBarber();
        $this->postJson('/api/finance/fixed-expenses', ['name' => 'DAS', 'amount_in_cents' => 7590, 'due_day' => 20]);
        $this->postJson('/api/finance/fixed-expenses', ['name' => 'Aluguel', 'amount_in_cents' => 150_000, 'due_day' => 5]);
        $this->postJson('/api/finance/one-off-expenses', ['description' => 'Setembro', 'amount_in_cents' => 1000, 'date' => '2026-09-30']);
        $this->postJson('/api/finance/one-off-expenses', ['description' => 'Toalhas', 'amount_in_cents' => 8500, 'date' => '2026-10-12']);
        $this->postJson('/api/finance/one-off-expenses', ['description' => 'Lâminas', 'amount_in_cents' => 2100, 'date' => '2026-10-02']);

        // Act
        $response = $this->getJson('/api/finance/expenses');

        // Assert
        $this->assertSame(['Aluguel', 'DAS'], array_column($response->json('fixed'), 'name'));
        $this->assertSame(['Toalhas', 'Lâminas'], array_column($response->json('one_off'), 'description'));
    }

    public function test_validates_amount_due_day_and_a_date_in_the_future_of_the_barbershop(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $fixed = $this->postJson('/api/finance/fixed-expenses', ['name' => '', 'amount_in_cents' => 0, 'due_day' => 32]);
        $tomorrowLocal = $this->postJson('/api/finance/one-off-expenses', ['description' => 'Máquina', 'amount_in_cents' => 50_000, 'date' => '2026-10-16']);
        $todayLocal = $this->postJson('/api/finance/one-off-expenses', ['description' => 'Máquina', 'amount_in_cents' => 50_000, 'date' => '2026-10-15']);

        // Assert
        $fixed->assertUnprocessable();
        $this->assertSame(['name' => ['required'], 'amount_in_cents' => ['between'], 'due_day' => ['between']], $fixed->json('errors'));
        $tomorrowLocal->assertUnprocessable()->assertJsonPath('errors.date.0', 'before_or_equal');
        $todayLocal->assertCreated();
    }

    public function test_a_barbershop_never_lists_or_changes_the_expenses_of_another(): void
    {
        // Arrange
        $this->actingAsBarber();
        $foreignId = $this->postJson('/api/finance/fixed-expenses', ['name' => 'Aluguel A', 'amount_in_cents' => 1000, 'due_day' => 1])->json('id');
        $this->actingAsBarber();

        // Act
        $list = $this->getJson('/api/finance/expenses');
        $update = $this->putJson("/api/finance/fixed-expenses/{$foreignId}", ['name' => 'Invasão', 'amount_in_cents' => 1, 'due_day' => 1]);
        $delete = $this->deleteJson("/api/finance/fixed-expenses/{$foreignId}");
        $restore = $this->postJson("/api/finance/fixed-expenses/{$foreignId}/restore");

        // Assert
        $list->assertJsonPath('fixed', []);
        $update->assertNotFound()->assertJsonPath('code', 'EXPENSE_NOT_FOUND');
        $delete->assertNotFound();
        $restore->assertNotFound();
    }
}
