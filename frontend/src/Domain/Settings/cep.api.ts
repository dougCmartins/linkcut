/** Address from a CEP with ViaCEP (free, no key, allows calls from the browser). */

const VIA_CEP_URL = 'https://viacep.com.br/ws'

export interface ZipCodeAddress {
  street: string
  district: string
  city: string
  state: string
}

/** Null when the CEP does not exist. Rejects when ViaCEP can't be reached (the barber types it by hand). */
export async function fetchAddressByZipCode(zipCode: string): Promise<ZipCodeAddress | null> {
  const digits = zipCode.replace(/\D/g, '')
  const response = await fetch(`${VIA_CEP_URL}/${digits}/json/`)
  if (!response.ok) throw new Error(`ViaCEP answered ${response.status}`)
  const body = (await response.json()) as { erro?: boolean | string; logradouro?: string; bairro?: string; localidade?: string; uf?: string }
  if (body.erro) return null
  return { street: body.logradouro ?? '', district: body.bairro ?? '', city: body.localidade ?? '', state: body.uf ?? '' }
}
