<?php

declare(strict_types=1);

namespace Domain\Notification\Tests\Unit;

use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Support\WebPushSender;
use Minishlink\WebPush\VAPID;
use Tests\TestCase;

final class WebPushSenderTest extends TestCase
{
    public function test_the_real_sender_starts_with_the_server_keys(): void
    {
        // Arrange: without GMP/BCMath the library refuses to start (the Docker image installs GMP).
        $keys = VAPID::createVapidKeys();
        config(['services.webpush.public_key' => $keys['publicKey'], 'services.webpush.private_key' => $keys['privateKey']]);

        // Act
        $gone = (new WebPushSender)->send([], new PushMessageData(title: 'Novo pedido', body: 'Ana', url: '/painel', tag: 't'));

        // Assert
        $this->assertSame([], $gone);
        $this->assertTrue(extension_loaded('gmp') || extension_loaded('bcmath'));
    }
}
