<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Unit;

use Domain\Finance\Data\CommissionAdjustmentInputForCalcData;
use Domain\Finance\Enums\CommissionAppliesTo;
use Domain\Finance\Enums\CommissionKind;
use Domain\Finance\Enums\CommissionStage;
use Domain\Finance\Support\CommissionCalculator;
use PHPUnit\Framework\TestCase;

final class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new CommissionCalculator;
    }

    public function test_appbarber_bruto_fifty_percent_of_one_hundred(): void
    {
        // Arrange — R$ 100 at 50%, no adjustments → R$ 50
        $price = 10_000;
        $percent = 500;

        // Act
        $result = $this->calc->calculate($price, $percent, []);

        // Assert
        $this->assertSame(10_000, $result->base_in_cents);
        $this->assertSame(5_000, $result->amount_in_cents);
    }

    public function test_appbarber_liquido_after_four_percent_on_commission(): void
    {
        // Arrange — R$ 100, 50%, after 4% on commission → R$ 48
        $adj = new CommissionAdjustmentInputForCalcData(
            name: 'Taxa cartão',
            stage: CommissionStage::After,
            kind: CommissionKind::Percent,
            applies_to: CommissionAppliesTo::Commission,
            value: 40,
        );

        // Act
        $result = $this->calc->calculate(10_000, 500, [$adj]);

        // Assert
        $this->assertSame(4_800, $result->amount_in_cents);
    }

    public function test_appbarber_descontado_after_four_percent_on_price(): void
    {
        // Arrange — R$ 100, 50%, after 4% on price → R$ 46
        $adj = new CommissionAdjustmentInputForCalcData(
            name: 'Taxa cartão',
            stage: CommissionStage::After,
            kind: CommissionKind::Percent,
            applies_to: CommissionAppliesTo::Price,
            value: 40,
        );

        // Act
        $result = $this->calc->calculate(10_000, 500, [$adj]);

        // Assert
        $this->assertSame(4_600, $result->amount_in_cents);
    }

    public function test_trinks_admin_fee_before_then_sixty_percent(): void
    {
        // Arrange — R$ 100, before 10% on price, then 60% → base 90, commission R$ 54
        $adj = new CommissionAdjustmentInputForCalcData(
            name: 'Taxa administrativa',
            stage: CommissionStage::Before,
            kind: CommissionKind::Percent,
            applies_to: CommissionAppliesTo::Price,
            value: 100,
        );

        // Act
        $result = $this->calc->calculate(10_000, 600, [$adj]);

        // Assert
        $this->assertSame(9_000, $result->base_in_cents);
        $this->assertSame(5_400, $result->amount_in_cents);
    }

    public function test_service_cost_system_adjustment_reduces_base(): void
    {
        // Arrange — R$ 100, cost R$ 6 as system before, 50% → base 94, commission 4700
        $adj = new CommissionAdjustmentInputForCalcData(
            name: 'Custo do serviço',
            stage: CommissionStage::Before,
            kind: CommissionKind::Fixed,
            applies_to: CommissionAppliesTo::Price,
            value: 0,
            system_key: 'service_cost',
        );

        // Act
        $result = $this->calc->calculate(10_000, 500, [$adj], serviceCostInCents: 600);

        // Assert
        $this->assertSame(9_400, $result->base_in_cents);
        $this->assertSame(4_700, $result->amount_in_cents);
    }

    public function test_rounds_half_up_only_at_the_end(): void
    {
        // Arrange — 1001 cents × 33.3% = 333.333… → 333
        $resultLow = $this->calc->calculate(1_001, 333, []);
        // 1000 × 0.5% after on commission from 50% of 1000 = 5 − 0.025? use 15 cents odd
        // 99 cents at 50% = 49.5 → 50
        $resultHalf = $this->calc->calculate(99, 500, []);

        // Assert
        $this->assertSame(333, $resultLow->amount_in_cents);
        $this->assertSame(50, $resultHalf->amount_in_cents);
    }
}
