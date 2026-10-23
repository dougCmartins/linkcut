/**
 * The privacy policy text (/privacidade), apart from the main messages: only that page downloads it.
 * Precompiled like pt-BR.ts (vite.config.ts) and merged into the "privacy" messages when the page opens.
 */
export default {
  privacy: {
    page: {
      title: 'Política de Privacidade',
      updated: 'Atualizada em outubro de 2026',
      intro:
        'Esta política explica como o linkcut trata dados pessoais de barbeiros, dos clientes que agendam pelas páginas das barbearias e de quem visita essas páginas, conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018).',
      sections: {
        controller: {
          title: 'Quem é o responsável',
          items: [
            'Controlador: {controller}, inscrito sob o nº {document}.',
            'Encarregado (DPO) e canal para assuntos de privacidade: {email}.',
            'Cada barbearia decide como atende os próprios clientes. O linkcut guarda e processa esses dados para a barbearia prestar o serviço.',
          ],
        },
        data: {
          title: 'Quais dados tratamos',
          items: [
            'Barbeiro: nome, e-mail, senha (guardada com criptografia), WhatsApp, endereço e fotos da barbearia, serviços, horários e lançamentos financeiros.',
            'Cliente: nome, e-mail, WhatsApp, horários agendados e, se você permitir no agendamento, a distância até a barbearia (a localização exata não é guardada).',
            'Visitante: nenhum dado pessoal. Guardamos só no seu navegador o tema escolhido e a sua escolha de privacidade.',
            'Registros técnicos: endereço IP e data dos acessos, guardados por segurança (prevenção de fraude e abuso).',
          ],
        },
        purposes: {
          title: 'Para que usamos e com qual base legal',
          items: [
            'Prestar o serviço (página da barbearia, agendamentos, e-mails de confirmação e lembrete): execução de contrato (art. 7º, V).',
            'Segurança, prevenção de fraude e registro de erros sem dados pessoais: legítimo interesse (art. 7º, IX).',
            'Medir desempenho e gravar o momento de um erro com textos e imagens ocultos: consentimento (art. 7º, I), que você pode retirar quando quiser.',
            'Guardar registros de acesso: cumprimento de obrigação legal (Marco Civil da Internet, art. 15).',
          ],
        },
        sharing: {
          title: 'Com quem compartilhamos',
          items: [
            'Com a barbearia em que você agendou: só os dados do seu agendamento.',
            'Com fornecedores que nos ajudam a operar: hospedagem do servidor, envio de e-mails e monitoramento de erros (Sentry). Eles só usam os dados para isso.',
            'Serviços de endereço: ao digitar um CEP, o navegador consulta o ViaCEP; o endereço da barbearia é localizado no mapa pelo OpenStreetMap (Nominatim). Só o CEP e o endereço vão para eles.',
            'Não vendemos dados e não usamos publicidade nem rastreadores de terceiros.',
          ],
        },
        retention: {
          title: 'Por quanto tempo guardamos',
          items: [
            'Sessões: até você sair ou elas expirarem (até 30 dias na área do cliente).',
            'Códigos de verificação: expiram em minutos e são apagados.',
            'Agendamentos e histórico: enquanto a barbearia usar o linkcut. Se você excluir sua conta de cliente, seus dados pessoais saem desses registros.',
            'Registros de acesso: 6 meses, como pede a lei.',
          ],
        },
        storage: {
          title: 'Cookies e armazenamento do navegador',
          items: [
            'Não usamos cookies de publicidade. O app guarda no seu navegador apenas o necessário: a sessão (para você continuar conectado), o tema claro/escuro e a sua escolha de privacidade.',
            'Com o seu consentimento, o monitoramento de desempenho (Sentry) mede a velocidade das telas e grava o momento de um erro, sempre com textos e imagens ocultos.',
            'Você pode mudar a escolha a qualquer momento em "Preferências de cookies", no rodapé das páginas e em "Minha conta".',
          ],
        },
        rights: {
          title: 'Seus direitos',
          items: [
            'Confirmar se tratamos seus dados, acessá-los e corrigi-los (clientes podem corrigir nome e WhatsApp em "Minha conta").',
            'Pedir a exclusão: clientes podem excluir a conta em "Minha conta"; barbeiros, pelo e-mail do encarregado.',
            'Retirar o consentimento, pedir a portabilidade e informações sobre com quem compartilhamos.',
            'Para qualquer pedido, escreva para {email}. Você também pode reclamar à ANPD (Autoridade Nacional de Proteção de Dados).',
          ],
        },
        changes: {
          title: 'Mudanças nesta política',
          items: ['Quando esta política mudar de forma importante, avisaremos no app e pediremos sua escolha de novo, se for o caso.'],
        },
      },
    },
  },
}
