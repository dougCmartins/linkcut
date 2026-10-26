/**
 * "Continuar com Google" only shows when the build turns it on (VITE_GOOGLE_LOGIN=true), once the API has
 * GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET. Off by default: no broken button before that.
 */
export function isGoogleLoginEnabled(): boolean {
  return import.meta.env.VITE_GOOGLE_LOGIN === 'true'
}
