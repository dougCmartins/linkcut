import { z } from 'zod'

export const PASSWORD_MIN_LENGTH = 8
export const CODE_LENGTH = 6

/** Values are i18n keys under `auth.errors`. */
export const credentialsSchema = z.object({
  email: z.string().trim().min(1, 'emailRequired').pipe(z.email('emailInvalid')),
  password: z.string().min(1, 'passwordRequired').min(PASSWORD_MIN_LENGTH, 'passwordTooShort'),
})

export const codeSchema = z.string().regex(new RegExp(`^\\d{${CODE_LENGTH}}$`), 'codeIncomplete')

export type CredentialsInput = z.input<typeof credentialsSchema>

/** First error message key per field, or an empty object when valid. */
export function credentialsErrors(input: CredentialsInput): Partial<Record<keyof CredentialsInput, string>> {
  const result = credentialsSchema.safeParse(input)
  if (result.success) return {}
  const errors: Partial<Record<keyof CredentialsInput, string>> = {}
  for (const issue of result.error.issues) {
    const field = issue.path[0] as keyof CredentialsInput
    errors[field] ??= issue.message
  }
  return errors
}
