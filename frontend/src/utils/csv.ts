/** Semicolon is what Excel in Portuguese (pt-BR) expects, since the comma is the decimal mark. */
export const CSV_SEPARATOR = ';'
/** Byte order mark so Excel opens the accents (UTF-8) correctly. */
const UTF8_BOM = '﻿'
/** Cells starting with these can run as formulas in spreadsheets (CSV injection). */
const FORMULA_START = /^[=+\-@\t\r]/

export type CsvCell = string | number

function escapeCell(value: CsvCell): string {
  let text = String(value)
  if (FORMULA_START.test(text)) text = `'${text}`
  return /[";\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
}

export function toCsv(headers: string[], rows: CsvCell[][]): string {
  const lines = [headers, ...rows].map((row) => row.map(escapeCell).join(CSV_SEPARATOR))
  return `${UTF8_BOM}${lines.join('\r\n')}\r\n`
}

/** Saves a text file in the browser (download). */
export function downloadTextFile(filename: string, content: string, mimeType = 'text/csv;charset=utf-8'): void {
  const url = URL.createObjectURL(new Blob([content], { type: mimeType }))
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.style.display = 'none'
  document.body.append(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
