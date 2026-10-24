import login1080 from './login-1080.webp'
import login720 from './login-720.webp'
import signUp1080 from './sign-up-1080.webp'
import signUp720 from './sign-up-720.webp'

/** Photos of the sign-up and sign-in pages: the storefront invites in, the inside welcomes back. */
export const AUTH_PHOTOS = {
  signUp: { src: signUp720, srcset: `${signUp720} 720w, ${signUp1080} 1080w` },
  login: { src: login720, srcset: `${login720} 720w, ${login1080} 1080w` },
} as const
