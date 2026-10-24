# Incidentes de segurança com dados pessoais

Passo a passo para quando algo der errado com dados pessoais (LGPD, art. 48, e Resolução CD/ANPD nº 15/2024).
Prazo principal: **avisar a ANPD e as pessoas afetadas em até 3 dias úteis** depois de saber do incidente, quando ele puder causar risco ou dano relevante.

## 1. Detectar
Sinais de um incidente:
- alerta do Sentry, do UptimeRobot ou do healthchecks.io fora do normal (muitos erros 401/403/500, picos de acesso);
- muitas falhas de login ou de código no log (`storage/logs`) ou no registro de acesso do Caddy (`/data/access.log`);
- aviso de um barbeiro, cliente ou pesquisador (pelo e-mail do encarregado);
- perda ou vazamento de backup, do `.env.production`, das chaves do Passport ou do acesso à VPS;
- e-mail enviado para a pessoa errada, ou dados de uma barbearia aparecendo em outra.

Anote **quando** soube: o prazo de 3 dias úteis conta daí.

## 2. Conter (primeiras horas)
Conforme o caso:
- **Acesso indevido ao servidor:** troque a senha e as chaves SSH, bloqueie o IP no firewall e tire um snapshot da VPS para análise antes de mexer.
- **Senha ou token vazado:**
  - troque o `APP_KEY` e as chaves do Passport (`php artisan passport:keys --force`): isso derruba todas as sessões;
  - troque as senhas do banco, do SMTP e o `BACKUP_PASSPHRASE`.
- **Conta de uma barbearia comprometida:** `php artisan tenant:deactivate {slug}` tira a página do ar; depois, redefina a senha.
- **Falha no código:** corrija, rode os testes e publique (`git pull` e `make prod-up`).
- Guarde as provas: logs, registros de acesso e o horário de cada passo.

## 3. Avaliar o risco
Responda:
- **Quais dados?** Nome, e-mail, WhatsApp, horários, financeiro, senhas (só com hash), localização (só a distância).
- **De quem e quantos?** Barbeiros, clientes, de quais barbearias.
- **Os dados estavam protegidos?** Backups cifrados e senhas com hash reduzem o risco.
- **O que pode acontecer?** Golpe por WhatsApp, phishing, constrangimento, prejuízo financeiro.

Há **risco ou dano relevante** quando o incidente pode afetar direitos das pessoas e envolve, por exemplo, dados de muitas pessoas, dados financeiros, de autenticação ou de menores. Na dúvida, comunique.

## 4. Comunicar (até 3 dias úteis)
- **ANPD:** pelo formulário de comunicação de incidente no site da ANPD (gov.br/anpd). Informe:
  - o que aconteceu e quando;
  - os dados e as pessoas afetadas;
  - as medidas de segurança que já existiam e as tomadas depois;
  - os riscos e o contato do encarregado.
- **Pessoas afetadas:** por e-mail, em linguagem simples:
  - o que aconteceu;
  - quais dados delas;
  - o que já fizemos;
  - o que elas podem fazer (ex.: desconfiar de mensagens pedindo pagamento);
  - o contato do encarregado.
- **Barbearias:** quando o incidente atingir os clientes delas, avise cada barbearia afetada **sem demora**. Ela é a controladora desses dados (Termos de Uso), e o linkcut a ajuda a comunicar.

Se ainda faltar informação no prazo, comunique o que já sabe e complete depois.

## 5. Registrar
Todo incidente, **mesmo os que não precisam ser comunicados**, entra no registro e é guardado por pelo menos **5 anos**. O registro fica fora do repositório (é privado) e leva:
- data em que soube e data do incidente;
- descrição, dados e pessoas afetadas;
- avaliação de risco e por que comunicou ou não;
- medidas tomadas;
- comunicações feitas (ANPD, titulares, barbearias), com as datas.

## 6. Aprender
Depois de resolver:
- descubra a causa;
- corrija e escreva um teste que pegaria o problema;
- atualize este documento, se algo faltou.
