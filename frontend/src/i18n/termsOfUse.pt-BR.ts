/**
 * The Terms of Use text (/termos), apart from the main messages: only that page downloads it.
 * Precompiled like pt-BR.ts (vite.config.ts) and merged into the "terms" messages when the page opens.
 * A template for legal review (docs/privacidade.md).
 */
export default {
  terms: {
    page: {
      title: 'Termos de Uso',
      updated: 'Versão 2026-10 · atualizados em outubro de 2026',
      intro:
        'Estes termos valem para quem usa o linkcut: barbeiros e barbearias que criam a sua página e agenda, e os clientes que agendam por elas. Ao criar uma conta de barbearia, você aceita estes termos e a Política de Privacidade.',
      sections: {
        service: {
          title: 'O serviço',
          items: [
            'O linkcut oferece à barbearia uma página pública (link na bio), portfólio, agenda online, avisos e um controle financeiro simples.',
            'O serviço é prestado por {controller} (nº {document}). Contato: {email}.',
            'O serviço pode mudar e ganhar ou perder funções. Mudanças importantes nestes termos serão avisadas no app.',
          ],
        },
        account: {
          title: 'Conta da barbearia',
          items: [
            'Você precisa ter 18 anos ou mais e informar dados verdadeiros. A conta é pessoal: guarde a senha e não compartilhe o acesso.',
            'Você é responsável pelo que publica na sua página (textos, fotos, preços) e por ter o direito de usar essas imagens.',
            'É proibido usar o linkcut para atividades ilegais, enganosas, spam, ou para coletar dados de pessoas sem uma base legal.',
          ],
        },
        clients: {
          title: 'Dados dos seus clientes (LGPD)',
          items: [
            'Os dados dos clientes que agendam pela sua página pertencem à barbearia: a barbearia é a controladora desses dados, e o linkcut é o operador (arts. 37 a 40 da LGPD).',
            'Como operador, o linkcut trata esses dados só para prestar o serviço (agenda, avisos e lembretes), segundo estes termos e as configurações que você escolhe, e não os usa para outros fins.',
            'Suboperadores: hospedagem do servidor, envio de e-mails (Brevo) e monitoramento de erros (Sentry), listados na Política de Privacidade. Você autoriza esse uso.',
            'O linkcut ajuda a barbearia a atender os direitos dos titulares: o cliente corrige e exclui os próprios dados em "Minha conta", e a barbearia baixa ou exclui todos os dados em Configurações → Segurança.',
            'Se houver um incidente de segurança que envolva esses dados, o linkcut avisará a barbearia sem demora e ajudará nas comunicações à ANPD e aos titulares.',
            'Ao encerrar a conta, você pode baixar os seus dados antes; depois da exclusão, tudo da barbearia é apagado, inclusive dos backups em até 14 dias.',
          ],
        },
        software: {
          title: 'Propriedade do software',
          items: [
            'O código e a marca linkcut são de propriedade do autor. O código fonte pode estar visível publicamente para consulta, sob a licença PolyForm Strict 1.0.0, que não permite uso comercial, alteração nem redistribuição sem autorização.',
            'Você recebe apenas o direito de usar o serviço enquanto a sua conta estiver ativa.',
          ],
        },
        availability: {
          title: 'Disponibilidade e responsabilidade',
          items: [
            'Trabalhamos para manter o serviço no ar, com backups diários, mas ele é oferecido como está, sem garantia de funcionamento ininterrupto.',
            'O linkcut não é responsável pelo atendimento da barbearia, pelos preços cobrados nem por faltas e atrasos de clientes ou barbeiros.',
          ],
        },
        termination: {
          title: 'Encerramento',
          items: [
            'Você pode excluir a sua barbearia a qualquer momento em Configurações → Segurança.',
            'Podemos suspender ou encerrar contas que violem estes termos ou a lei, avisando sempre que possível.',
          ],
        },
        law: {
          title: 'Lei aplicável',
          items: ['Estes termos seguem as leis brasileiras, incluindo o Código de Defesa do Consumidor quando aplicável e a LGPD.'],
        },
      },
    },
  },
}
