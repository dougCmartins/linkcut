# Escala: o que aguenta hoje e o que fazer quando crescer

## Hoje
Uma VPS de 2 GB, com tudo no `docker-compose.prod.yml`:

| Serviço | Memória | O que faz |
|---|---|---|
| `web` (Caddy) | 128 MB | https, arquivos do app, prévias para robôs |
| `app` (FrankenPHP) | 512 MB | a API |
| `queue` | 256 MB | e-mails e push (fila no MySQL) |
| `scheduler` | 192 MB | lembretes, convites, limpezas, backup |
| `redis` | 64 MB | cache e limite de tentativas |
| `mysql` | 768 MB | o banco |

**Medido** (detalhes em [performance.md](performance.md)):
- 240 requisições por segundo em 4 núcleos, com mediana de 19 ms;
- em 2 núcleos, espere perto de 120 por segundo, ou 60 aberturas de página por segundo;
- a página pública sai do cache e faz uma consulta só.

Na prática, uma barbearia recebe poucas centenas de visitas por dia. Mesmo com 1.000 barbearias, o pico fica longe desse limite.

## Os sinais de que é hora de crescer
Olhe no Pulse (`/pulse`, ligue só quando precisar) e no `docker stats`:
- **CPU do `app` acima de 70%** por muito tempo: o PHP é o limite;
- **requisições lentas** (acima de 1 s) no Pulse, ou o p95 subindo;
- **memória do MySQL** no limite, ou consultas lentas no Pulse;
- **fila atrasando:** e-mails e push chegando minutos depois;
- **disco das imagens** passando de 70%.

## Os passos, do mais barato para o mais caro
Cada passo resolve um gargalo. Faça só o que o sinal pedir.

1. **VPS maior (4 GB, 2–4 núcleos).**
   - Só trocar o plano e subir os limites de memória no compose.
   - Dobra a capacidade sem mudar nada no código.
2. **FrankenPHP em modo worker (ou Laravel Octane).**
   - O Laravel sobe uma vez e atende muitas requisições, sem o boot de cerca de 6 ms a cada uma.
   - Costuma dar de 2 a 4 vezes mais requisições por segundo na mesma máquina.
   - **Cuidado antes de ligar:** nada pode guardar estado entre requisições. O `CurrentTenant` e qualquer singleton precisam ser limpos a cada requisição. Rode a suíte toda e um teste de carga com duas barbearias se alternando para conferir que uma nunca vê os dados da outra.
3. **Imagens fora da VPS** (Cloudflare R2, S3 ou Bunny, com CDN).
   - Troque o disco `public` por um disco S3 no `config/filesystems.php`.
   - As fotos saem do servidor e chegam mais rápido no celular.
   - O backup fica só com o banco.
4. **Banco gerenciado** (MySQL na DigitalOcean, AWS RDS ou similar).
   - Backups automáticos, réplica e mais memória sem cuidar do servidor.
   - Basta trocar o `DB_HOST` e tirar o serviço `mysql` do compose.
5. **Mais de um app atrás do Caddy.**
   - Duas ou mais VPS com o `app`, e o Caddy dividindo a carga (`reverse_proxy app1:8080 app2:8080`).
   - Pré-requisitos, que já estão prontos ou ficam prontos nos passos acima:
     - cache e limite de tentativas no Redis, compartilhado (já está);
     - imagens num disco compartilhado (passo 3);
     - banco fora da VPS do app (passo 4).
   - O `scheduler` roda numa máquina só.
6. **Fila separada.**
   - Leve a fila para o Redis (`QUEUE_CONNECTION=redis`) quando o volume de e-mails crescer, com o Redis **com disco** (`appendonly yes`) e sem descarte de chaves, para não perder trabalho.
   - Ou use um serviço gerenciado (SQS).
   - Suba mais `queue` se os e-mails atrasarem.

## O que não precisa mudar
- **Multi-tenant:** todas as tabelas têm `tenant_id` com índice, e as consultas sempre filtram por ele. O banco cresce em linhas, não em complexidade.
- **Sessões:** a API usa tokens (Passport), não depende de sessão no servidor. Dá para ter vários apps sem "sessão grudada".
- **Frontend:** arquivos estáticos com hash no nome e cache de um ano. Podem ir para uma CDN a qualquer momento.
