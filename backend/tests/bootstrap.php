<?php

declare(strict_types=1);

// Tests always run on an in-memory SQLite database, even when a .env points to MySQL.
foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}

require __DIR__.'/../vendor/autoload.php';
