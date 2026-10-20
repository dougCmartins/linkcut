<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

/** The server's public key, needed by the browser to subscribe (applicationServerKey). */
final class VapidPublicKeyData extends Data
{
    public function __construct(public readonly string $public_key) {}
}
