# Requisitos: endereço da barbearia e distância do cliente

> **Por quê:** o link público (`/:slug`) pode ser aberto de qualquer lugar. Sem endereço, um cliente de outra cidade agenda sem saber onde fica a barbearia, e o barbeiro recebe o pedido sem saber de onde o cliente é.

## 1. Endereço da barbearia (obrigatório)

| ID | Regra |
|---|---|
| RF-L1 | O endereço fica em **Configurações → Link**: CEP, rua, número, complemento (opcional), bairro, cidade e UF. |
| RF-L2 | Ao digitar o CEP com 8 números, rua, bairro, cidade e UF são preenchidos pelo **ViaCEP** (gratuito, chamado direto do navegador). CEP inexistente mostra "CEP não encontrado". Se o ViaCEP estiver fora do ar, o barbeiro preenche à mão. |
| RF-L3 | O endereço é **obrigatório** para salvar a aba Link. Uma barbearia sem endereço (criada antes desta regra) não recebe agendamentos: a API responde `BARBERSHOP_ADDRESS_MISSING` (409), o modal mostra "Agenda indisponível" e o painel mostra um aviso com o link para cadastrar. |
| RF-L4 | **Ponto no mapa** (latitude e longitude), usado para medir a distância dos clientes: o botão "Estou na barbearia: usar minha localização" grava a posição do aparelho. Sem o botão, a API procura o endereço no **OpenStreetMap Nominatim** ao salvar. |
| RF-L5 | Mudar qualquer campo do endereço apaga o ponto, que é procurado de novo ao salvar. Endereço sem mudança mantém o ponto, sem nova consulta. |
| RF-L6 | Se o Nominatim não achar o endereço ou estiver fora do ar, o endereço é salvo mesmo assim, sem ponto. A tela avisa e sugere o botão "Estou na barbearia". Sem ponto, os pedidos chegam como "localização não informada". |

## 2. O cliente vê onde fica

| ID | Regra |
|---|---|
| RF-L7 | O modal de agendamento mostra o card **"Onde fica"**, com o endereço completo e o link "Como chegar" (busca no Google Maps, sem chave de API). |

## 3. Distância do cliente (opcional)

| ID | Regra |
|---|---|
| RF-L8 | O modal pergunta "Quer avisar o barbeiro a que distância você está?" e oferece o botão **"Compartilhar minha localização"**, que usa a API nativa do navegador (`navigator.geolocation`). O navegador mostra o próprio pedido de permissão. |
| RF-L9 | Se o cliente permitir, ele vê "Você está a X km da barbearia" antes de enviar. Se negar, ou se o aparelho não tiver localização, aparece "Tudo bem, você pode agendar mesmo assim". **A localização nunca é obrigatória.** |
| RF-L10 | O pedido leva latitude e longitude só se o cliente permitiu. A API calcula a distância em linha reta (fórmula de haversine) até o ponto da barbearia. |
| RF-L11 | O barbeiro vê nos cards do painel (pendentes e agenda de hoje) **"a 3,2 km da barbearia"** ou **"localização não informada"**. |

## 4. Privacidade (LGPD)

- As coordenadas do cliente **não são gravadas**. A API guarda só a distância (`booking_requests.client_distance_meters`). O modal avisa: "Usamos sua localização só para calcular a distância. Ela não fica salva."
- O endereço e o ponto da barbearia são públicos (é um comércio) e aparecem na página pública.

## 5. Serviços externos

| Serviço | Uso | Onde | Observação |
|---|---|---|---|
| ViaCEP | CEP → rua, bairro, cidade e UF | navegador (`Domain/Settings/cep.api.ts`) | Gratuito e sem chave. |
| OpenStreetMap Nominatim | endereço → ponto no mapa | API (`Settings/Support/AddressGeocoder`) | Gratuito. A política pede `User-Agent` próprio e no máximo 1 consulta por segundo, por isso só consulta ao salvar um endereço alterado. Configurável por `NOMINATIM_URL` e `NOMINATIM_USER_AGENT`. Com muito volume, use uma instância própria ou um provedor pago. |

Os testes nunca chamam serviços reais: `Http::preventStrayRequests()` na API, e ViaCEP e geolocalização simulados no SPA.
