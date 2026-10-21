<?php

declare(strict_types=1);

namespace Domain\Tenant\Support;

/**
 * Links a barbershop can't take: pages of the app (linkcut.app/painel…), folders of static files and names kept for later.
 * The SPA keeps the same list (frontend/src/Domain/Onboarding/reservedSlugs.ts).
 */
final class ReservedSlugs
{
    public const LIST = [
        'admin', 'ajuda', 'api', 'app', 'assets', 'cadastrar', 'conta', 'entrar', 'icons', 'login',
        'painel', 'privacidade', 'public', 'pulse', 'suporte', 'termos', 'www',
    ];

    public static function contains(string $slug): bool
    {
        return in_array($slug, self::LIST, true);
    }
}
