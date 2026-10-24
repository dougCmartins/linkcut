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
            'Os dados dos clientes de cada barbearia pertencem a ela: a barbearia é a controladora, e o linkcut é o operador que guarda e processa esses dados para ela prestar o serviço (veja os Termos de Uso).',
          ],
        },
        data: {
          title: 'Quais dados tratamos',
          items: [
            'Barbeiro: nome, e-mail, senha (guardada com criptografia), WhatsApp, endereço e fotos da barbearia, serviços, horários, lançamentos financeiros e a data em que aceitou estes termos.',
            'Cliente: nome, e-mail, WhatsApp e horários agendados. Ao agendar, criamos uma conta de cliente com o seu e-mail, para você acompanhar os horários; você define a senha só se quiser.',
            'Localização: só se você tocar em "compartilhar localização" no agendamento. As coordenadas passam pelo servidor apenas para calcular a distância até a barbearia; guardamos só a distância.',
            'Visitante: nenhum dado pessoal. Guardamos só no seu navegador o tema escolhido e a sua escolha de privacidade.',
            'Registros de acesso: endereço IP, data e hora, endereço da página e resultado, sem senhas, tokens nem dados do navegador.',
          ],
        },
        purposes: {
          title: 'Para que usamos e com qual base legal',
          items: [
            'Prestar o serviço (página da barbearia, agendamentos, e-mails de confirmação e lembrete): execução de contrato (art. 7º, V).',
            'Convite único para o cliente terminar o cadastro e lembretes de horário: legítimo interesse (art. 7º, IX), com link para parar de receber em todo e-mail.',
            'Segurança, prevenção de fraude e registro de erros sem dados pessoais: legítimo interesse (art. 7º, IX).',
            'Medir desempenho e gravar o momento de um erro com textos e imagens ocultos: consentimento (art. 7º, I), que você pode retirar quando quiser.',
            'Guardar registros de acesso: cumprimento de obrigação legal (Marco Civil da Internet, art. 15).',
          ],
        },
        sharing: {
          title: 'Com quem compartilhamos',
          items: [
            'Com a barbearia em que você agendou: só os dados do seu agendamento.',
            'Com fornecedores que nos ajudam a operar (suboperadores): hospedagem do servidor, envio de e-mails (Brevo) e monitoramento de erros (Sentry). Eles só usam os dados para isso.',
            'Serviços de endereço: ao digitar um CEP, o seu navegador consulta o ViaCEP, que recebe o CEP e o seu endereço IP; o endereço da barbearia é localizado no mapa pelo OpenStreetMap (Nominatim), que recebe só esse endereço.',
            'Transferência internacional: o Brevo (França) e o Sentry (Estados Unidos/União Europeia) ficam fora do Brasil. A transferência se apoia nas cláusulas contratuais padrão desses fornecedores (art. 33 da LGPD).',
            'Não vendemos dados e não usamos publicidade nem rastreadores de terceiros.',
          ],
        },
        retention: {
          title: 'Por quanto tempo guardamos',
          items: [
            'Sessões: até você sair ou elas expirarem (até 30 dias na área do cliente). Sessões vencidas são apagadas todo dia.',
            'Códigos de verificação: expiram em minutos e são apagados no dia seguinte.',
            'Agendamentos e histórico: enquanto a barbearia usar o linkcut. Se a barbearia excluir a conta, tudo dela é apagado. Se você excluir sua conta de cliente, seus dados pessoais saem desses registros.',
            'Registros de acesso: 6 meses, como pede a lei. Registros de erros e de segurança: até 90 dias.',
            'Cópias de segurança (backup), cifradas: 14 dias.',
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
            'Confirmar se tratamos seus dados, acessá-los e corrigi-los (clientes corrigem nome e WhatsApp em "Minha conta").',
            'Portabilidade: baixe todos os seus dados em "Minha conta" (cliente) ou em Configurações → Segurança (barbeiro).',
            'Exclusão: clientes excluem a conta em "Minha conta" (os agendamentos ficam na barbearia sem o seu nome e contato); barbeiros excluem a barbearia e tudo dela em Configurações → Segurança.',
            'Retirar o consentimento e pedir informações sobre com quem compartilhamos.',
            'Para qualquer pedido, escreva para {email}; respondemos em até 15 dias. Você também pode reclamar à ANPD (Autoridade Nacional de Proteção de Dados).',
          ],
        },
        security: {
          title: 'Segurança e incidentes',
          items: [
            'Conexão sempre criptografada (https), senhas guardadas com hash, backups cifrados e acesso ao servidor restrito.',
            'Se houver um incidente que possa causar risco ou dano relevante, avisaremos a ANPD e as pessoas afetadas em até 3 dias úteis.',
          ],
        },
        children: {
          title: 'Menores de idade',
          items: [
            'O linkcut não é destinado a menores de 18 anos sem um responsável. Menores devem agendar com o acompanhamento dos pais ou responsáveis.',
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
