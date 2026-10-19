<?php

declare(strict_types=1);

namespace Domain\Identity\Tests\Unit;

use Domain\Identity\Support\ContactMask;
use PHPUnit\Framework\TestCase;

final class ContactMaskTest extends TestCase
{
    public function test_masks_emails_keeping_the_first_letters_and_the_domain(): void
    {
        // Act
        $masked = [ContactMask::email('rafael@linkcut.app'), ContactMask::email('ab@x.com'), ContactMask::email('a@x.com')];

        // Assert
        $this->assertSame(['ra****@linkcut.app', 'a*@x.com', 'a*@x.com'], $masked);
    }

    public function test_masks_phones_keeping_the_country_and_last_four_digits(): void
    {
        // Act
        $masked = ContactMask::phone('+55 (11) 98888-7777');

        // Assert
        $this->assertSame('+55 •• •••••-7777', $masked);
    }
}
