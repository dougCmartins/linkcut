import { describe, expect, it } from 'vitest'
import { ImageFileError, MAX_IMAGE_BYTES, fitWithin, validateImageFile } from './image'

describe('validateImageFile', () => {
  it('accepts a WebP image under the size limit', () => {
    // Arrange
    const file = { type: 'image/webp', size: 1024 }

    // Act
    const error = validateImageFile(file)

    // Assert
    expect(error).toBeNull()
  })

  it('rejects a file that is not an image', () => {
    // Arrange
    const file = { type: 'application/pdf', size: 1024 }

    // Act
    const error = validateImageFile(file)

    // Assert
    expect(error).toBe(ImageFileError.InvalidType)
  })

  it('rejects an image above 5 MB', () => {
    // Arrange
    const file = { type: 'image/jpeg', size: MAX_IMAGE_BYTES + 1 }

    // Act
    const error = validateImageFile(file)

    // Assert
    expect(error).toBe(ImageFileError.TooLarge)
  })
})

describe('fitWithin', () => {
  it('scales the longer side down to the limit and keeps the ratio', () => {
    // Arrange
    const [width, height] = [4000, 3000]

    // Act
    const size = fitWithin(width, height, 1600)

    // Assert
    expect(size).toEqual({ width: 1600, height: 1200 })
  })

  it('never scales a small image up', () => {
    // Arrange
    const [width, height] = [800, 600]

    // Act
    const size = fitWithin(width, height, 1600)

    // Assert
    expect(size).toEqual({ width: 800, height: 600 })
  })
})
