# 🐘 Backend Standards (PHP / Laravel & DDD)

## 1. Architecture, Domain-Driven Design (DDD) & Limites de Contexto
- **Domínios Bem Definidos:** A aplicação deve ser estruturada em domínios baseados em contextos de negócio (ex: `Users`, `Products`, `Sales`, `Auth`) e não por tipos genéricos de ficheiro (MVC tradicional).
- **Context Boundaries:** Serviços e Ações não devem conhecer ou mutar diretamente modelos de banco de dados pertencentes a domínios de negócio completamente distintos.
- **Regra de Isolamento (Actions & Models):** Uma `Action` tem a responsabilidade de executar uma única tarefa de negócio e **só pode aceder aos Models do seu próprio domínio**.
- **Proibido Cruzar Domínios Diretamente:** Uma `Action` **nunca** deve invocar uma `Action` pertencente a outro domínio.
- **Orquestradores (Orchestrator):** Quando um fluxo de negócio exigir interação entre múltiplos domínios (ex: criar uma venda, deduzir o stock do produto e notificar o utilizador), utilize um **Orchestrator**. Ele vive em `src/Domain/Orchestrator`, junto dos outros contextos. Não acessa model de domínio: só chama a Action de cada um, porque um domínio não entra no outro.
- **DTO não importa Model:** A Data class não faz `use` de Eloquent Model e não declara `fromClient`, `fromUser`, `fromStore` nem qualquer `from{Model}`. Quem lê o model é a Action do próprio domínio. Ela monta `new ClientData(...)` com escalares ou com Data do mesmo contexto. `Data::from()` e `Data::validate()` recebem array de entrada, não o model.
- **Contexto do problema, não do schema:** Tabela no banco não cria domínio. Não abra pasta nem DTO de User, Store, Transaction ou Operation só para aninhar a resposta de outro contexto. Se neste problema o dono e a loja são atributos do cliente, `ClientData` declara `name` e `store_name`. A Action lê a relação e copia a string. Transação e operação são outro contexto e ficam de fora até esse contexto ser o trabalho.
- **Domínio não é tabela:** Criar pasta só quando o contexto tem linguagem, regra e ciclo próprios. Uma entidade com uma Action não é domínio: vive dentro do contexto que já a nomeia. Sem uma frase de uma linha — o que este contexto faz, e por que não cabe no que já existe — a pasta não se cria.

### 📁 Estrutura de Pastas (DDD na prática)

```
src/
└── Domain/
│   ├── Sale/
│   │   ├── Actions/
│   │   │   └── CreateSale.php
│   │   ├── Models/
│   │   │   └── Sale.php
│   │   ├── Data/
│   │   │   ├── CreateSaleData.php
│   │   │   └── SaleData.php
│   │   ├── Enums/
│   │   │   └── SaleStatus.php
│   │   ├── Events/
│   │   │   └── SaleCreated.php
│   │   ├── Exceptions/
│   │   │   └── InvalidSaleException.php
│   │   ├── Controllers/
│   │   │   └── SaleController.php
│   │   ├── Routes/
│   │   │   └── api.php
│   │   └── Tests/
│   │       ├── Feature/
│   │       │   └── CreateSaleTest.php
│   │       └── Unit/
│   │           └── SaleModelTest.php
│   ├── Product/
│   │   ├── Actions/
│   │   │   └── DecrementStock.php
│   │   ├── Models/
│   │   │   └── Product.php
│   │   └── ...
│   ├── User/
│   │   ├── Actions/
│   │   │   └── NotifyUser.php
│   │   └── ...
│   └── Orchestrator/
│       └── Checkout/
│           ├── Actions/
│           │   └── ProcessCheckout.php
│           ├── Data/
│           │   └── CheckoutResultData.php
│           └── Controllers/
│               └── CheckoutController.php
```

### 🎯 Exemplo: Action dentro do seu domínio

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Actions;

use Domain\Sale\Data\CreateSaleData;
use Domain\Sale\Data\SaleData;
use Domain\Sale\Enums\SaleStatus;
use Domain\Sale\Events\SaleCreated;
use Domain\Sale\Exceptions\InvalidSaleException;
use Domain\Sale\Models\Sale;
use Illuminate\Support\Facades\DB;

final class CreateSale
{
    public function handle(CreateSaleData $data): SaleData
    {
        if ($data->amount <= 0) {
            throw new InvalidSaleException('O valor da venda deve ser positivo.');
        }

        $saleData = DB::transaction(function () use ($data) {
            $sale = Sale::create([
                'client_id' => $data->client_id,
                'amount'    => $data->amount,
                'status'    => SaleStatus::Pending,
            ]);

            $saleData = new SaleData(
                id: $sale->id,
                client_id: $sale->client_id,
                amount: (float) $sale->amount,
                status: $sale->status,
            );

            SaleCreated::dispatch($saleData);

            return $saleData;
        });

        return $saleData;
    }
}
```

### 🎯 Exemplo: Orchestrator chamando múltiplos domínios

```php
<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Checkout\Actions;

use Domain\Product\Actions\DecrementStock;
use Domain\Sale\Actions\CreateSale;
use Domain\Sale\Data\CreateSaleData;
use Domain\User\Actions\NotifyUser;
use Domain\Orchestrator\Checkout\Data\CheckoutResultData;

final class ProcessCheckout
{
    public function __construct(
        private readonly CreateSale $createSale,
        private readonly DecrementStock $decrementStock,
        private readonly NotifyUser $notifyUser,
    ) {}

    public function handle(CheckoutData $data): CheckoutResultData
    {
        // Orquestra: cada Action pertence ao seu domínio, o Orchestrator só coordena.
        $sale = $this->createSale->handle(
            new CreateSaleData(
                client_id: $data->client_id,
                amount: $data->amount,
            )
        );

        $this->decrementStock->handle($data->product_id, $data->quantity);
        $this->notifyUser->handle($data->client_id, 'Compra realizada com sucesso!');

        return new CheckoutResultData(saleId: $sale->id);
    }
}
```

---

## 2. Estrutura de Código, Tipagem e Testes
- **Strict Typing:** Declare obrigatoriamente tipos estritos no topo de todos os arquivos PHP (`declare(strict_types=1);`).
- **DTOs & Controladores Magros (Skinny Controllers):** Utilize DTOs com `spatie/laravel-data`. Cada DTO é uma classe `final` que estende `Spatie\LaravelData\Data` e vive em `Domain/{X}/Data/`. Propriedades de entrada são `readonly`. Query e body entram como argumento tipado (`ListSalesData $data`, `CreateSaleData $data`): o Spatie cria e valida a Data a partir do request. O controller não injeta `Request` e não monta array com `validateAndCreate`. Devolve o retorno da Action: `return $action->handle($data)`. A Action devolve `Data` ou `DataCollection`. Não chame `response()->json()` no sucesso. Não crie Form Request e não crie `JsonResource`.
- **Validação por tags, sem `rules()`:** Não escreva `function rules()`. Cada campo leva as tags do Spatie na propriedade: `Required`, `Max`, `Email`, `Min`, `IntegerType`, `StringType`, `Numeric`, `Nullable`, `DataCollectionOf`. Defaults de query (`page`, `perPage`) ficam no construtor da Data. Id de rota, quando não vem no body, passa por `Data::validate(['id' => $id])`. No `spatie/laravel-data` 4, `validate()` devolve o array validado: o controller entrega o objeto com `ShowSaleData::from(ShowSaleData::validate(['id' => $id]))`. `from()` sozinho não valida. Regra customizada entra como tag importada `Rule` na mesma propriedade. Não crie `fromRequest`, `fromArray` nem `fromRouteId`. Não leia `$request->query()` para montar a Data.
- **Proibido array no lugar de DTO, e proibido DTO de outro contexto para fugir do array:** Estrutura aninhada que pertence a este contexto é uma Data class. Lista desse contexto é `DataCollection`, não `array`. Valor que é só um campo deste contexto fica escalar. `ClientData` não declara `?array $users` e também não declara `?UserData $users`: declara `string $name` e `?string $store_name`. Arrays de contexto em logs estruturados não são contrato de domínio e continuam permitidos.
- **Enums e Ficheiros de Configuração:** Utilize `Enums` nativos do PHP para definir estados, tipos de dados estritos ou categorias. Variáveis de ambiente, *flags* e parametrizações de integração devem estar externalizados em ficheiros de configuração (`config/`).
- **Testes no Escopo do Domínio:** Os testes automatizados (Pest/PHPUnit) não devem ficar isolados numa pasta global e genérica, mas sim organizados **dentro do escopo do seu próprio domínio**, garantindo que cada domínio é auto-testável e verdadeiramente modular.

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ShowSaleData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $id,
    ) {
    }
}
```

### 🎯 Exemplo: Controller magro (Skinny Controller)

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Controllers;

use Domain\Sale\Actions\CreateSale;
use Domain\Sale\Actions\ListSales;
use Domain\Sale\Actions\ShowSale;
use Domain\Sale\Data\CreateSaleData;
use Domain\Sale\Data\ListSalesData;
use Domain\Sale\Data\SaleData;
use Domain\Sale\Data\ShowSaleData;
use Spatie\LaravelData\DataCollection;

final class SaleController
{
    public function index(ListSalesData $data, ListSales $action): DataCollection
    {
        return $action->handle($data);
    }

    public function store(CreateSaleData $data, CreateSale $action): SaleData
    {
        return $action->handle($data);
    }

    public function show(string $id, ShowSale $action): SaleData
    {
        return $action->handle(ShowSaleData::from(ShowSaleData::validate(['id' => $id])));
    }
}
```

### 🎯 Exemplo: Enum nativo do PHP

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Enums;

enum SaleStatus: string
{
    case Pending  = 'pending';
    case Paid     = 'paid';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Canceled, self::Refunded => true,
            default                        => false,
        };
    }
}
```

### 🎯 Exemplo: Configuração externalizada (`config/sale.php`)

```php
<?php

// config/sale.php

return [
    'max_installments'   => env('SALE_MAX_INSTALLMENTS', 12),
    'min_amount'         => env('SALE_MIN_AMOUNT', 1.00),
    'auto_approve_below' => env('SALE_AUTO_APPROVE_BELOW', 50.00),
];
```

Uso na Action:

```php
if ($data->amount < config('sale.min_amount')) {
    throw new InvalidSaleException('Valor abaixo do mínimo permitido.');
}
```

### 🎯 Exemplo: Teste dentro do domínio (`Domain/Sale/Tests/Feature/CreateSaleTest.php`)

```php
<?php

use Domain\Sale\Actions\CreateSale;
use Domain\Sale\Data\CreateSaleData;
use Domain\Sale\Models\Sale;

it('cria uma venda com sucesso', function () {
    $data = new CreateSaleData(client_id: 1, amount: 100.00);

    $result = app(CreateSale::class)->handle($data);

    expect($result->amount)->toBe(100.00)
        ->and(Sale::count())->toBe(1);
});

it('rejeita venda com valor inválido', function () {
    $data = new CreateSaleData(client_id: 1, amount: 0);

    app(CreateSale::class)->handle($data);
})->throws(\Domain\Sale\Exceptions\InvalidSaleException::class);
```

---

## 3. Database & Security
- **Arranque do projeto:** Todo projeto novo começa com Docker, Makefile na raiz e MySQL. O Compose sobe o MySQL e a aplicação. `make up`, `make migrate` e `make test` são o caminho. Sail, PostgreSQL, SQLite ou outro banco só entram se o pedido o disser.
- **Eloquent First:** Priorize sempre o Eloquent ORM para consultas ao banco de dados e definição de relacionamentos. Mantenha a complexidade de consultas isolada em *Scopes* ou *Query Builders* dedicados ao domínio. A Action chama o scope ou a relação; não reescreve a query. Relação do mesmo domínio não vira Orchestrator.
- **Raw SQL Policy:** `DB::raw` ou consultas puras são estritamente restritas a agregações críticas de performance. Quando utilizadas, devem ser isoladas em repositórios/camadas de serviço, rigorosamente documentadas e utilizar placeholders (`?`) para prevenir SQL Injection.
- **Mass Assignment Protection:** Defina explicitamente `$fillable` ou `$guarded` em todos os modelos Eloquent. Nunca deixe `$guarded = []` sem proteção explícita.
- **Input Validation:** Valide o payload na Data class com as tags do Spatie na propriedade. Não escreva `function rules()` e não use Form Request. O controller não valida campo a campo: chama `Data::validate()` e entrega a Data à Action.

### 🎯 Exemplo: Model Eloquent com `$fillable`, `casts` e Scopes

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Models;

use Domain\Sale\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class Sale extends Model
{
    protected $table = 'sales';

    // Mass Assignment Protection
    protected $fillable = [
        'client_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'status'  => SaleStatus::class,
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Scope de domínio — encapsula regra de negócio
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Paid->value);
    }

    public function scopeRecent(Builder $query, int $days = 25): Builder
    {
        return $query->where('paid_at', '>=', now()->subDays($days));
    }
}
```

### 🎯 Exemplo: Uso de `DB::raw` com placeholders (Raw SQL Policy)

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Queries;

use Illuminate\Support\Facades\DB;

final class SaleAggregateQuery
{
    /**
     * Agregação crítica de performance — isolada e documentada.
     * Usa placeholders (?) para evitar SQL Injection.
     */
    public function totalBySeller(int $sellerId, string $startDate): float
    {
        $result = DB::selectOne(
            'SELECT SUM(amount) AS total
             FROM sales
             WHERE seller_id = ?
               AND paid_at >= ?',
            [$sellerId, $startDate]
        );

        return (float) ($result->total ?? 0);
    }
}
```

---

## 4. API, Respostas e Observabilidade
- **Sem `JsonResource`:** A resposta de sucesso não passa por `JsonResource`, nem por pasta `Resources/`, nem por `response()->json()`. O controller devolve a `Data` ou a `DataCollection` que a Action devolveu. O Spatie implementa `Responsable` e serializa o JSON. O formato público é o DTO, não as colunas do model.
- **Envelope só no erro:** Sucesso é o JSON da Data. O envelope (`data`, `message`, `code`, `status_code`, `errors`) fica no handler global, quando a exceção é de domínio. O controller não monta esse objeto.

```json
{
  "data": { ... },
  "message": "Mensagem amigável descritiva",
  "code": "TAG_DO_EVENTO_OU_ERRO",
  "status_code": 200,
  "errors": []
}
```

- **O poder da chave `code`:** No erro, o campo `code` atua como uma *tag* única (ex: `USER_NOT_FOUND`, `PRODUCT_OUT_OF_STOCK`), permitindo ao frontend mapear lógicas de UI e traduções sem depender de texto puro ou do `status_code` HTTP de forma isolada. Sucesso não carrega `code`: o corpo é a Data.
- **Exception Handling e Exceções Semânticas (Domain Exceptions):** Lance exceções com nomes claros de negócio (ex: `InvalidCartException`). Trate exceções de forma centralizada no `Handler` global, que deve capturá-las e formatá-las automaticamente dentro do Envelope Padrão da API com os códigos HTTP apropriados.
- **Logs Estruturados:** Nunca utilize o log de forma silenciosa ou apenas com strings genéricas. Ao processar dados críticos, adicione logs estruturados passando arrays de contexto (`Log::info('Venda processada com sucesso', ['order_id' => $id, 'user_id' => $userId])`).

### 🎯 Exemplo: Domain Exception semântica

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Exceptions;

use DomainException;

final class InvalidSaleException extends DomainException
{
    public function __construct(string $message = 'Venda inválida.')
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'INVALID_SALE';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
```

### 🎯 Exemplo: Exception Handler global formatando o Envelope

```php
<?php

// app/Exceptions/Handler.php (ou bootstrap/app.php no Laravel 11)

use Domain\Sale\Exceptions\InvalidSaleException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;

public function render($request, Throwable $e): JsonResponse
{
    if ($e instanceof InvalidSaleException) {
        return response()->json([
            'data'        => null,
            'message'     => $e->getMessage(),
            'code'        => $e->getErrorCode(),
            'status_code' => $e->getHttpStatus(),
            'errors'      => [],
        ], $e->getHttpStatus());
    }

    // Fallback genérico — nunca expor stack trace em produção
    return response()->json([
        'data'        => null,
        'message'     => 'Erro interno.',
        'code'        => 'INTERNAL_ERROR',
        'status_code' => 500,
        'errors'      => config('app.debug') ? [$e->getMessage()] : [],
    ], 500);
}
```

### 🎯 Exemplo: Resposta de sucesso

O controller devolve a Data. O Spatie serializa. Não há envelope nem `SaleResource`.

```json
{
  "id": 42,
  "client_id": 1,
  "amount": "100.00",
  "status": "pending",
  "created_at": "2026-09-29T12:00:00+00:00"
}
```

### 🎯 Exemplo: Logs estruturados

```php
<?php

declare(strict_types=1);

namespace Domain\Sale\Actions;

use Illuminate\Support\Facades\Log;

final class CreateSale
{
    public function handle(CreateSaleData $data): SaleData
    {
        Log::info('Iniciando criação de venda', [
            'client_id' => $data->client_id,
            'amount'    => $data->amount,
        ]);

        try {
            // ... lógica
            $sale = Sale::create([...]);

            Log::info('Venda criada com sucesso', [
                'sale_id'   => $sale->id,
                'client_id' => $sale->client_id,
                'amount'    => $sale->amount,
            ]);

            return new SaleData(
                id: $sale->id,
                client_id: $sale->client_id,
                amount: (float) $sale->amount,
                status: $sale->status,
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao criar venda', [
                'client_id' => $data->client_id,
                'amount'    => $data->amount,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

---

## 5. Legacy Refactoring & Migrations
- Ao refatorar código legado ou migrar estruturas de banco de dados, preserve a retrocompatibilidade quando aplicável e garanta que as regras de negócio permaneçam intactas.
- Evite introduzir complexidade arquitetural excessiva (over-engineering) quando uma refatoração simples atinge o requisito com excelência.

### 🎯 Exemplo: Migration retrocompatível

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Adiciona coluna nova como nullable para não quebrar registros existentes
            $table->string('payment_method')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_method');
        });

        // Popula valores padrão para registros antigos
        DB::table('sales')->whereNull('payment_method')->update([
            'payment_method' => 'unknown',
        ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'paid_at']);
        });
    }
};
```

### 🎯 Exemplo: Refatoração incremental — de Controller gordo para Action

**Antes (legado):**

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'client_id' => 'required|exists:clients,id',
        'amount'    => 'required|numeric|min:1',
    ]);

    $sale = Sale::create($validated);
    event(new SaleCreated($sale));

    return response()->json($sale, 201);
}
```

**Depois (refatorado, com DTO + Action):**

```php
public function store(CreateSaleData $data, CreateSale $action): SaleData
{
    return $action->handle($data);
}
```

A refatoração preserva a regra de negócio (validar cliente, criar venda, disparar evento) e adiciona tipagem e separação de camadas. O sucesso é a Data. O envelope fica no handler de erro.

---

## 📋 Tabela Resumo – Onde cada peça vive

| Camada / Artefato | Localização | Responsabilidade |
|-------------------|-------------|-------------------|
| **Model** | `Domain/{X}/Models/` | Entidade, relacionamentos, scopes |
| **Action** | `Domain/{X}/Actions/` | Uma operação de negócio |
| **Data (DTO)** | `Domain/{X}/Data/` | `spatie/laravel-data`: contrato de entrada/saída e validação (substitui Form Request) |
| **Enum** | `Domain/{X}/Enums/` | Estados, tipos, categorias |
| **Event** | `Domain/{X}/Events/` | Side effects entre módulos |
| **Exception** | `Domain/{X}/Exceptions/` | Erros de domínio semânticos |
| **Controller** | `Domain/{X}/Controllers/` | Recebe a Data validada, chama a Action, devolve a Data |
| **Routes** | `Domain/{X}/Routes/api.php` | Rotas do domínio |
| **Tests** | `Domain/{X}/Tests/` | Testes do domínio |
| **Orchestrator** | `src/Domain/Orchestrator/{Y}/` | Ponte entre domínios: só chama Actions, sem Model próprio |

Não existe camada `Resources/`. `JsonResource` não faz parte deste padrão.

---

## 6. O que não passa

Antes de criar arquivo novo, recuse o caminho abaixo. Estas são as falhas já vistas neste projeto.

- Data com `function rules()`, `fromRequest`, `fromArray` ou `fromRouteId`.
- Data que importa Eloquent Model ou expõe `fromClient` / `fromUser` / `fromStore`.
- `JsonResource` ou pasta `Resources/` para montar o JSON. Sucesso é a Data. O envelope é só o erro, no handler.
- Controller que injeta `Illuminate\Http\Request` e chama `validateAndCreate` com `page` ou `perPage` lidos na mão.
- `response()->json()` no sucesso, com `message`, `code`, `status_code` e `errors`.
- Model em `app/Models`. O model vive em `Domain/{X}/Models/`.
- Pasta, classe ou teste deixados depois de uma migração. Sem `use` vivo, apaga-se.
- `array` dentro de uma Data para esconder campos. E também um DTO de outro contexto (`UserData`, `StoreData`, `TransactionData`) criado só para não usar esse array.
- Domínio novo porque a tabela existe, ou porque há uma Action só. O contexto é o problema em curso. Dono e loja, neste problema, são `name` e `store_name` do cliente. Transação e operação só entram quando esse contexto for o trabalho.
- Controller que consulta Model, valida na mão ou devolve o model cru.
- Projeto novo sem Docker e Makefile, ou com banco diferente de MySQL, quando ninguém pediu a troca.

---

## ✅ Checklist final do Backend

- [ ] Todo arquivo PHP tem `declare(strict_types=1);`
- [ ] Actions são `final` e têm um único método `handle()`
- [ ] A Action lê o model e faz `new XData(...)`. A Data não importa Model
- [ ] DTOs são `final`, estendem `Spatie\LaravelData\Data`, propriedades `readonly`, validação só por tags, sem `rules()` e sem Form Request
- [ ] Entrada de query ou body é a Data injetada. Id de rota passa por `Data::validate()`, sem `fromRouteId` e sem `Request`
- [ ] Nenhuma Data usa `array` para estrutura aninhada. Lista do mesmo contexto é `DataCollection`. Campo deste contexto é escalar, não DTO de outro domínio
- [ ] Não nasce domínio fora do problema em curso, nem por tabela, nem por uma Action só
- [ ] Models vivem em `Domain/{X}/Models/`, com `$fillable` ou `$guarded` explícitos. Nada em `app/Models`
- [ ] Controllers são magros: delegam e devolvem a Data. Sem `Request`, sem `response()->json()` no sucesso, sem `JsonResource`
- [ ] Exceções de domínio herdam de `DomainException`
- [ ] Handler global formata o envelope só no erro
- [ ] Erros usam `code` (SCREAMING_SNAKE_CASE)
- [ ] Logs são estruturados (com array de contexto)
- [ ] Testes ficam dentro do domínio (`Domain/{X}/Tests/`)
- [ ] O Orchestrator vive em `src/Domain/Orchestrator`, só chama Actions e não tem Model próprio
- [ ] Raw SQL só é usado com placeholders e documentado
- [ ] Projeto novo tem Docker, Makefile e MySQL, salvo pedido explícito em contrário

Esse documento consolida as práticas de arquitetura backend adotadas em projetos Laravel com foco em DDD pragmático, tipagem estrita, separação de camadas e observabilidade — servindo como referência para qualquer desenvolvedor que queira aplicar esses padrões de forma consistente.