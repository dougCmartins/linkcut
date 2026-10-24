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
```

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

> **Quem já tinha subido antes:** no seu `.env.production`, troque `CACHE_STORE=database` por `CACHE_STORE=file`. O limite de tentativas deixa de consultar o banco a cada requisição.

## Backup do banco
- **Manual:** `make prod-backup` cria `backups/linkcut-AAAA-MM-DD-HHMM.sql.gz`. Backups com mais de 14 dias são apagados.
- **Automático (todo dia às 3h):** `crontab -e` e adicione:
  ```
  0 3 * * * cd /opt/linkcut && make prod-backup >> backups/cron.log 2>&1
  ```
- De vez em quando, copie a pasta `backups/` para fora da VPS (ex.: `scp` para o seu computador). Se a VPS sumir, o backup vai junto.

## Comandos
| Comando | Faz |
|---|---|
| `make prod-up` | Sobe ou atualiza |
| `make prod-logs` | Mostra os logs ao vivo |
| `make prod-down` | Para tudo (os dados ficam guardados) |
| `make prod-backup` | Cópia do banco |

## Como funciona (para referência)
- **`web` (Caddy):** serve o app já compilado, cuida do `https` e repassa `/api` e `/storage` para a API.
- **`app` (Laravel no FrankenPHP):** a API. Roda as migrations e prepara as chaves a cada início.
- **`queue`:** envia os e-mails e os avisos.
- **`scheduler`:** dispara os lembretes da véspera e os convites, de hora em hora.
- **`mysql`:** o banco.
- **Volumes:** os dados do banco, as imagens enviadas, as chaves e os certificados ficam em volumes do Docker. Sobrevivem a `make prod-up`, `make prod-down` e reinícios.
- **O que fica exposto:** só as portas 80 e 443. O banco e a API não ficam expostos na internet.
