<?php

declare(strict_types=1);

namespace Domain\Notification\Tests\Unit;

use Domain\Notification\Support\PlainText;
use Tests\TestCase;

/** Names typed by visitors go into e-mails from our domain as plain text only. */
final class PlainTextTest extends TestCase
{
    public function test_keeps_a_normal_name(): void
    {
        $this->assertSame('João da Silva', PlainText::name('  João   da Silva '));
    }

    public function test_removes_links_markdown_and_html(): void
    {
        // Arrange
        $phishing = 'Ana [Pague aqui](https://evil.example) <b>x</b> www.golpe.com pix.com.br/pagar';

        // Act
        $name = PlainText::name($phishing);

        // Assert
        $this->assertStringNotContainsString('http', $name);
        $this->assertStringNotContainsString('](', $name);
        $this->assertStringNotContainsString('<', $name);
        $this->assertStringNotContainsString('golpe.com', $name);
        $this->assertStringNotContainsString('pix.com.br', $name);
        $this->assertStringStartsWith('Ana', $name);
    }

    public function test_falls_back_when_nothing_is_left(): void
    {
        $this->assertSame('cliente', PlainText::name('https://evil.example'));
    }

    public function test_comment_drops_tags_and_links_and_blank_becomes_null(): void
    {
        // Arrange
        $comment = '  Ótimo corte <b>mesmo</b> https://evil.example  www.golpe.com ';

        // Act
        $clean = PlainText::text($comment);
        $blank = PlainText::text('https://evil.example');

        // Assert
        $this->assertSame('Ótimo corte mesmo', $clean);
        $this->assertNull($blank);
        $this->assertNull(PlainText::text(null));
    }
}
