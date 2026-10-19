<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Unit;

use Domain\Settings\Support\WhatsappNumberRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WhatsappNumberRuleTest extends TestCase
{
    /** @return array<string, array{string, bool}> */
    public static function numbers(): array
    {
        return [
            'mobile with mask' => ['(11) 98888-7777', true],
            'landline' => ['1133334444', true],
            'with country code' => ['+55 11 98888-7777', true],
            'without area code' => ['98888-7777', false],
            'too long' => ['119888877771', false],
            'empty' => ['', false],
        ];
    }

    #[DataProvider('numbers')]
    public function test_accepts_brazilian_numbers_with_area_code(string $number, bool $expected): void
    {
        // Arrange
        $failed = false;

        // Act
        (new WhatsappNumberRule)->validate('whatsapp_number', $number, function () use (&$failed): void {
            $failed = true;
        });

        // Assert
        $this->assertSame($expected, ! $failed);
    }
}
