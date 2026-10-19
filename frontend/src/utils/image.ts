export const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'] as const
export const MAX_IMAGE_BYTES = 5 * 1024 * 1024
export const MAX_IMAGE_DIMENSION = 1600

export const ImageFileError = {
  InvalidType: 'invalidType',
  TooLarge: 'tooLarge',
} as const

export type ImageFileError = (typeof ImageFileError)[keyof typeof ImageFileError]

export function validateImageFile(file: Pick<File, 'type' | 'size'>): ImageFileError | null {
  if (!(ACCEPTED_IMAGE_TYPES as readonly string[]).includes(file.type)) return ImageFileError.InvalidType
  if (file.size > MAX_IMAGE_BYTES) return ImageFileError.TooLarge
  return null
}

/** Scales (width, height) down so the longer side fits `maxDimension`. Never scales up. */
export function fitWithin(width: number, height: number, maxDimension: number): { width: number; height: number } {
  const scale = Math.min(1, maxDimension / Math.max(width, height))
  return { width: Math.round(width * scale), height: Math.round(height * scale) }
}

/**
 * Resizes an image in the browser and re-encodes it as WebP.
 * Falls back to the original file when the browser cannot decode or encode it.
 */
export async function resizeImage(file: Blob, maxDimension = MAX_IMAGE_DIMENSION): Promise<Blob> {
  if (typeof createImageBitmap !== 'function' || typeof document === 'undefined') return file
  try {
    const bitmap = await createImageBitmap(file)
    const size = fitWithin(bitmap.width, bitmap.height, maxDimension)
    const canvas = document.createElement('canvas')
    canvas.width = size.width
    canvas.height = size.height
    const context = canvas.getContext('2d')
    if (!context) return file
    context.drawImage(bitmap, 0, 0, size.width, size.height)
    bitmap.close()
    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/webp', 0.85))
    return blob ?? file
  } catch {
    return file
  }
}
