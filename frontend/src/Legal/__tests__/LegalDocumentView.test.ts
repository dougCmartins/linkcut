import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import LegalDocumentView from '../LegalDocumentView.vue'
import { renderWithPlugins } from '@/utils/testRender'

describe('LegalDocumentView', () => {
  it('/termos shows the Terms of Use with the data processor clause', async () => {
    // Arrange
    const rendered = renderWithPlugins(LegalDocumentView, { props: { document: 'terms' } })

    // Act
    await rendered.router.push('/termos')

    // Assert
    expect(await screen.findByRole('heading', { level: 1, name: 'Termos de Uso' })).toBeTruthy()
    expect(screen.getByText(/arts\. 37/)).toBeTruthy()
  })

  it('/privacidade shows the Privacy Policy with the incidents section', async () => {
    // Arrange
    const rendered = renderWithPlugins(LegalDocumentView, { props: { document: 'privacy' } })

    // Act
    await rendered.router.push('/privacidade')

    // Assert
    expect(await screen.findByRole('heading', { level: 1, name: 'Política de Privacidade' })).toBeTruthy()
    expect(screen.getByRole('heading', { name: 'Segurança e incidentes' })).toBeTruthy()
  })
})
