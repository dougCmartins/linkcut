# Subir o linkcut numa VPS

Com isto, o linkcut fica no ar com um **link fixo** (ex.: `https://seudominio.com.br/rafael`) para a bio do Instagram, com `https` automático e e-mail de verdade.

## O que você vai precisar
| Item | Sugestão | Custo aproximado |
|---|---|---|
| VPS (servidor) | Ubuntu 24.04, 2 GB de RAM. Hetzner CX22, Vultr, DigitalOcean ou Hostinger VPS | US$ 5–6/mês (Hostinger em reais) |
| Domínio | Registro.br (`.com.br`) | ~R$ 40/ano |
| E-mail (códigos e avisos) | Brevo, grátis até 300 e-mails/dia (ou Resend) | grátis |

## 1. Domínio apontando para a VPS
1. Crie a VPS e anote o **IP** dela.
2. No painel do domínio (Registro.br → DNS), crie um registro **A**: nome vazio (ou `@`), valor = IP da VPS. Se quiser `www`, crie outro registro A com nome `www`.
3. Espere alguns minutos. `ping seudominio.com.br` deve responder com o IP da VPS.

## 2. Preparar a VPS (uma vez)
Entre por SSH (`ssh root@IP-DA-VPS`) e rode:

```bash
# Docker
curl -fsSL https://get.docker.com | sh

# Firewall: só SSH e o site
apt-get install -y ufw make git
ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable

# 2 GB de swap: o build e o MySQL cabem com folga numa VPS de 2 GB
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

Se o provedor oferecer **criptografia de disco** (volume criptografado), ligue. É uma camada a mais para a LGPD.

## 3. Baixar o projeto
O repositório é privado. O jeito mais simples é um **token do GitHub**:
1. Gere o token em GitHub → Settings → Developer settings → Personal access tokens → Fine-grained. Dê acesso só ao repositório `linkcut`, com permissão de leitura de conteúdo.
2. Clone com ele:

```bash
cd /opt
git clone https://SEU_USUARIO:SEU_TOKEN@github.com/dougCmartins/linkcut.git
cd linkcut
```

## 4. Configurar
```bash
cp .env.production.example .env.production
nano .env.production
```

Preencha:
- `DOMAIN`, `APP_URL`, `FRONTEND_URL` e `FRONTEND_URLS` com o seu domínio. `DOMAIN` vai sem `https://`; os outros três, com.
- `DB_PASSWORD` e `MYSQL_ROOT_PASSWORD`: senhas longas. Gere com `openssl rand -base64 24`.
- O bloco `MAIL_*`: veja o passo 6.
- Deixe `APP_KEY` e as chaves `VAPID_*` vazias: o sistema preenche sozinho na primeira subida. **Depois disso, não apague**, porque trocar o `APP_KEY` desconecta todo mundo.

## 5. Subir
```bash
make prod-up
```

Na primeira vez, leva alguns minutos para montar tudo. Depois, abra `https://seudominio.com.br`. O cadeado aparece sozinho (o Caddy pega o certificado do Let's Encrypt).

- **Criar a sua barbearia:** em `https://seudominio.com.br/cadastrar`.
- **Link para a bio do Instagram:** em Configurações → Link → **Copiar link**.
- **Ver se está tudo bem:** `make prod-logs`.

## 6. E-mail de verdade (Brevo)
1. Crie a conta em brevo.com.
2. Em **Senders, Domains & Dedicated IPs → Domains**, adicione o seu domínio. A Brevo mostra registros DNS (TXT/CNAME, para SPF e DKIM); crie-os no Registro.br. Sem isso, os e-mails caem no spam.
3. Em **SMTP & API → SMTP**, copie o login e gere uma chave SMTP.
4. No `.env.production`:
   ```
   MAIL_HOST=smtp-relay.brevo.com
   MAIL_PORT=587
   MAIL_USERNAME=<login SMTP>
   MAIL_PASSWORD=<chave SMTP>
   MAIL_FROM_ADDRESS="nao-responda@seudominio.com.br"
   ```
5. Rode `make prod-up` de novo.

## Atualizar (depois de cada mudança na `main`)
```bash
cd /opt/linkcut
git pull
make prod-up
```

As migrations do banco rodam sozinhas. O `make prod-up` sempre recria os containers do app, então uma mudança no `.env.production` vale na hora.

> **Cache no Redis:** o `docker-compose.prod.yml` sobe um Redis só para o cache e para o limite de tentativas (64 MB, sem disco), e já aponta o app para ele. Não precisa mudar nada no `.env.production`. A fila continua no MySQL, que não perde trabalho se o servidor reiniciar.

## Backup (banco + imagens + chaves + configurações)
O `make prod-backup` gera **um arquivo cifrado** (AES-256) em `backups/linkcut-AAAA-MM-DD-HHMM.tar.gz.enc` com:
- o banco de dados;
- as imagens enviadas e as chaves de login (sem elas, todo mundo é desconectado);
- o `.env.production` (`APP_KEY`, chaves do push, senhas).

Backups com mais de 14 dias são apagados.

1. **Senha do backup (uma vez):** gere com `openssl rand -base64 32` e coloque em `BACKUP_PASSPHRASE` no `.env.production`. **Guarde uma cópia no seu gerenciador de senhas.** Sem ela, nenhum backup abre.
2. **Automático, todo dia às 3h:** `crontab -e` e adicione:
   ```
   0 3 * * * cd /opt/linkcut && make prod-backup >> backups/cron.log 2>&1
   ```
3. **Cópia fora da VPS (recomendado):** se a VPS sumir, os backups dela vão junto.
   1. Crie um bucket grátis, por exemplo no Cloudflare R2 (10 GB grátis) ou no Backblaze B2.
   2. Instale o rclone: `apt-get install -y rclone`.
   3. Configure o acesso: `rclone config` (tipo S3, com as chaves do bucket).
   4. Ponha o destino em `BACKUP_RCLONE_REMOTE` (ex.: `r2:linkcut-backups`).
   5. Cada backup é copiado sozinho.

### Restaurar (testado)
Numa VPS nova, ou depois de um problema:
1. Siga os passos 2 a 5 deste guia (Docker, projeto, `.env.production`, `make prod-up`).
2. Copie o arquivo do backup para `backups/`.
3. Rode `make prod-restore FILE=backups/linkcut-AAAA-MM-DD-HHMM.tar.gz.enc` e digite `restaurar` para confirmar.
4. Compare o `.env.production.restored` com o seu `.env.production`. O `APP_KEY` e as `VAPID_*` precisam ser **os do backup**: copie esses valores se forem diferentes.
5. Rode `make prod-up`.

Senha errada ou arquivo corrompido: a restauração para antes de mexer em qualquer dado.

## Alertas: saber quando algo cai
1. **Site fora do ar:** crie um monitor grátis no [UptimeRobot](https://uptimerobot.com) do tipo HTTP(s) para `https://seudominio.com.br/up`. Ele avisa por e-mail ou no app.
2. **Lembretes e e-mails parados (agendador ou fila):**
   1. Crie um check grátis no [healthchecks.io](https://healthchecks.io), com período de 5 min e carência de 10 min.
   2. Copie a URL de ping para `HEARTBEAT_URL` no `.env.production` e rode `make prod-up`.
   3. O app avisa que está vivo a cada 5 minutos. Se parar, você recebe o alerta.
3. **Erros do app:**
   1. Crie um projeto grátis no [Sentry](https://sentry.io).
   2. Coloque a DSN em `SENTRY_LARAVEL_DSN` (backend) e em `VITE_SENTRY_DSN` (frontend).
   3. Rode `make prod-up`.
   4. Os erros chegam sem dados pessoais. Desempenho e gravação no momento do erro só aparecem para quem aceitou "Desempenho" no aviso de privacidade.

## Barbearias (admin mínimo)
Enquanto o painel admin não existe, pelo terminal da VPS:

| Comando | Faz |
|---|---|
| `docker compose -f docker-compose.prod.yml --env-file .env.production exec app php artisan tenant:list` | Lista as barbearias (ativa, onboarding) |
| `... exec app php artisan tenant:deactivate SLUG` | Desativa: página pública em 404 e login bloqueado |
| `... exec app php artisan tenant:activate SLUG` | Ativa de novo |

## Limpeza automática
O agendador apaga sozinho, todo dia de madrugada:
- códigos de verificação, desafios de login e sessões vencidos há mais de um dia;
- tokens expirados;
- falhas da fila com mais de 30 dias;
- imagens que nenhuma página usa mais.

## Comandos
| Comando | Faz |
|---|---|
| `make prod-up` | Sobe ou atualiza |
| `make prod-logs` | Mostra os logs ao vivo |
| `make prod-down` | Para tudo (os dados ficam guardados) |
| `make prod-backup` | Backup cifrado: banco, imagens, chaves e `.env.production` |
| `make prod-restore FILE=...` | Restaura um backup |

## Como funciona (para referência)
- **`web` (Caddy):** serve o app já compilado, cuida do `https` e repassa `/api` e `/storage` para a API.
- **`app` (Laravel no FrankenPHP):** a API. Roda as migrations e prepara as chaves a cada início.
- **`queue`:** envia os e-mails e os avisos.
- **`scheduler`:** dispara os lembretes da véspera e os convites, de hora em hora.
- **`mysql`:** o banco, ajustado para 2 GB (`deploy/mysql.cnf`).
- **Memória e logs:** cada serviço tem um limite de memória, e os logs de cada um ficam em no máximo 50 MB (`docker-compose.prod.yml`).
- **Desempenho do servidor:** o app roda em FrankenPHP com opcache, e configurações e rotas ficam em cache.
- **Volumes:** os dados do banco, as imagens enviadas, as chaves e os certificados ficam em volumes do Docker. Sobrevivem a `make prod-up`, `make prod-down` e reinícios.
- **O que fica exposto:** só as portas 80 e 443. O banco e a API não ficam expostos na internet.
