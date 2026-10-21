/**
 * Links a barbershop can't take (pages of the app and names kept for later).
 * Same list as the API (backend/src/Domain/Tenant/Support/ReservedSlugs.php).
 */
export const RESERVED_SLUGS = [
  'admin',
  'ajuda',
  'api',
  'app',
  'assets',
  'cadastrar',
  'conta',
  'entrar',
  'icons',
  'login',
  'painel',
  'privacidade',
  'public',
  'pulse',
  'suporte',
  'termos',
  'www',
] as const

export const SLUG_MIN_LENGTH = 3
export const SLUG_MAX_LENGTH = 40
const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

/** "Barbearia do João!" → "barbearia-do-joao" (lowercase, no accents, words joined by "-"). */
export function slugify(text: string): string {
  return text
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, SLUG_MAX_LENGTH)
    .replace(/-+$/, '')
}

export function isValidSlug(slug: string): boolean {
  return slug.length >= SLUG_MIN_LENGTH && slug.length <= SLUG_MAX_LENGTH && SLUG_PATTERN.test(slug)
}

export function isReservedSlug(slug: string): boolean {
  return (RESERVED_SLUGS as readonly string[]).includes(slug)
}
