import { describe, expect, it } from 'vitest'
import { toCsv } from './csv'

describe('toCsv', () => {
  it('uses semicolons, CRLF lines and a UTF-8 BOM for Excel', () => {
    // Arrange
    const headers = ['Cliente', 'Valor (R$)']
    const rows = [['João', '50,00']]

    // Act
    const csv = toCsv(headers, rows)

    // Assert
    expect(csv).toBe('﻿Cliente;Valor (R$)\r\nJoão;50,00\r\n')
  })

  it('quotes cells with separators, quotes or line breaks', () => {
    // Arrange
    const rows = [['Corte; barba', 'Diz "oi"', 'linha\nquebrada']]

    // Act
    const csv = toCsv(['a', 'b', 'c'], rows)

    // Assert
    expect(csv).toContain('"Corte; barba";"Diz ""oi""";"linha\nquebrada"')
  })

  it('neutralizes cells that a spreadsheet would run as formulas', () => {
    // Arrange
    const rows = [['=HYPERLINK("http://x")', '+55', '@cmd']]

    // Act
    const csv = toCsv(['a', 'b', 'c'], rows)

    // Assert
    expect(csv).toContain(`"'=HYPERLINK(""http://x"")";'+55;'@cmd`)
  })
})
