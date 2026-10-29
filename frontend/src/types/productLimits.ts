/** Technical cap shared with the API. Not a billing plan. */
export const ProductLimits = {
  MaxCount: 50,
  NameMaxLength: 80,
  DescriptionMaxLength: 160,
  MinPriceInCents: 1,
  MaxPriceInCents: 1_000_000,
} as const
