# 💻 Frontend Standards (Vue 3 / TypeScript)

## 1. Ecossistema Vue 3 e TypeScript
- **Composition API:** O padrão absoluto para a construção de componentes é a Composition API, utilizando a tag `<script setup lang="ts">`.
- **TypeScript Obrigatório:** Utilize TypeScript de forma rigorosa para garantir a segurança de tipos, interfaces claras e um melhor *intellisense*, reduzindo erros em tempo de execução.

### 🎯 Exemplo: Componente com `<script setup lang="ts">`

```vue
<!-- components/ProductCard.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import type { Product } from '@/types/product'

interface Props {
  product: Product
  highlighted?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  highlighted: false,
})

const emit = defineEmits<{
  (e: 'select', productId: number): void
  (e: 'remove', productId: number): void
}>()

const formattedPrice = computed(() =>
  new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(props.product.price)
)
</script>

<template>
  <article
    class="product-card"
    :class="{ 'product-card--highlighted': highlighted }"
    @click="emit('select', product.id)"
  >
    <h3>{{ product.name }}</h3>
    <p>{{ formattedPrice }}</p>
    <button type="button" @click.stop="emit('remove', product.id)">
      Remover
    </button>
  </article>
</template>
```

### 🎯 Exemplo: Tipagem de contratos com interfaces

```ts
// types/product.ts
export interface Product {
  id: number
  name: string
  price: number
  status: ProductStatus
  createdAt: string
}

export type ProductStatus = 'active' | 'inactive' | 'draft'

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
  }
}
```

---

## 2. Reatividade e Performance
- **Pensar em Performance:** Construa código reativo de forma consciente. Distinga claramente o uso de `ref()` para valores primitivos e `reactive()` para objetos.
- **Otimização de Dados:** Para grandes volumes de dados que não precisam de reatividade profunda (ex: listas massivas de leitura), considere utilizar `shallowRef` ou `markRaw` para poupar memória e processamento.
- **Propriedades Computadas:** Utilize `computed()` para derivar estados e lógicas matemáticas, evitando ao máximo recalcular expressões complexas diretamente no HTML.

### 🎯 Exemplo: `ref` vs `reactive`

```ts
import { ref, reactive } from 'vue'

// ✅ ref() para valores primitivos
const count = ref(0)
const isLoading = ref(false)
const userName = ref<string | null>(null)

// ✅ reactive() para objetos com múltiplas propriedades relacionadas
const form = reactive({
  email: '',
  password: '',
  rememberMe: false,
})

// ❌ Evite misturar: reactive com primitivo isolado não é idiomático
const state = reactive({ count: 0 }) // prefira ref(0) se for só um número
```

### 🎯 Exemplo: `shallowRef` para listas massivas

```ts
import { shallowRef, triggerRef } from 'vue'
import type { LogEntry } from '@/types/log'

// ✅ shallowRef — não rastreia profundamente cada item da lista
// Útil para listas grandes onde você substitui a lista inteira, não muta itens
const logs = shallowRef<LogEntry[]>([])

function replaceLogs(newLogs: LogEntry[]): void {
  logs.value = newLogs // dispara reatividade uma vez
}

function appendLog(entry: LogEntry): void {
  logs.value.push(entry)
  triggerRef(logs) // notifica manualmente porque shallowRef não rastreia mutações profundas
}
```

### 🎯 Exemplo: `markRaw` para objetos não reativos

```ts
import { markRaw, ref } from 'vue'
import { Chart } from 'chart.js'

// ✅ markRaw — objeto pesado que não precisa ser reativo
const chartInstance = ref<Chart | null>(null)

function initChart(canvas: HTMLCanvasElement): void {
  chartInstance.value = markRaw(new Chart(canvas, {
    type: 'line',
    data: { datasets: [] },
  }))
}
```

### 🎯 Exemplo: `computed()` em vez de lógica no template

```vue
<script setup lang="ts">
import { computed } from 'vue'
import type { Product } from '@/types/product'

const props = defineProps<{ products: Product[] }>()

// ✅ Lógica derivada encapsulada em computed
const activeProducts = computed(() =>
  props.products.filter(p => p.status === 'active')
)

const totalPrice = computed(() =>
  activeProducts.value.reduce((sum, p) => sum + p.price, 0)
)

const formattedTotal = computed(() =>
  new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(totalPrice.value)
)
</script>

<template>
  <!-- ✅ Template limpo, apenas renderiza o resultado -->
  <div>
    <p>Produtos ativos: {{ activeProducts.length }}</p>
    <p>Total: {{ formattedTotal }}</p>
  </div>
</template>
```

```vue
<!-- ❌ Anti-padrão: lógica pesada dentro do template -->
<template>
  <div>
    <p>
      Total: {{
        products
          .filter(p => p.status === 'active')
          .reduce((sum, p) => sum + p.price, 0)
          .toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
      }}
    </p>
  </div>
</template>
```

---

## 3. Componentização e Templates Limpos
- **Componentização Inteligente:** Divida interfaces pesadas em componentes menores, granulares e reutilizáveis (Padrão *Smart/Dumb Components*).
- **Sem HTML Inflado (Anti-v-if Exagerado):** Não polua os templates com árvores gigantes e aninhadas de `v-if` / `v-else-if`. Se a lógica de renderização condicional se tornar complexa:
    1. Abstraia esse bloco para um subcomponente dedicado.
    2. Resolva a lógica através de variáveis ou propriedades computadas (`computed`).
    3. Se a alternância de exibição for muito frequente e custosa para o DOM, prefira utilizar o `v-show`.

### 🎯 Exemplo: Smart Component vs Dumb Component

**Smart Component (`ProductListContainer.vue`):**

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ProductList from './ProductList.vue'
import { fetchProducts } from '@/api/product.api'
import type { Product } from '@/types/product'

const products = ref<Product[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const search = ref('')

const filteredProducts = computed(() =>
  products.value.filter(p =>
    p.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

onMounted(async () => {
  isLoading.value = true
  try {
    products.value = await fetchProducts()
  } catch (e) {
    error.value = 'Erro ao carregar produtos'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div>
    <input v-model="search" placeholder="Buscar..." />
    <p v-if="isLoading">Carregando...</p>
    <p v-else-if="error">{{ error }}</p>
    <ProductList v-else :products="filteredProducts" />
  </div>
</template>
```

**Dumb Component (`ProductList.vue`):**

```vue
<script setup lang="ts">
import type { Product } from '@/types/product'

defineProps<{ products: Product[] }>()
const emit = defineEmits<{ (e: 'select', id: number): void }>()
</script>

<template>
  <ul>
    <li v-for="product in products" :key="product.id" @click="emit('select', product.id)">
      {{ product.name }} — {{ product.price }}
    </li>
  </ul>
</template>
```

### 🎯 Exemplo: Refatorando `v-if` aninhados em componente dedicado

```vue
<!-- ❌ Anti-padrão: árvores de v-if aninhadas -->
<template>
  <div>
    <div v-if="user">
      <div v-if="user.isActive">
        <div v-if="user.hasSubscription">
          <span>Bem-vindo, assinante!</span>
        </div>
        <div v-else>
          <span>Assine para continuar</span>
        </div>
      </div>
      <div v-else>
        <span>Conta inativa</span>
      </div>
    </div>
    <div v-else>
      <span>Faça login</span>
    </div>
  </div>
</template>
```

```vue
<!-- ✅ Refatorado: estado computado + componente dedicado -->
<script setup lang="ts">
import { computed } from 'vue'
import UserStatusPanel from './UserStatusPanel.vue'
import type { User } from '@/types/user'

const props = defineProps<{ user: User | null }>()

type UserState = 'guest' | 'inactive' | 'no-subscription' | 'subscriber'

const userState = computed<UserState>(() => {
  if (!props.user) return 'guest'
  if (!props.user.isActive) return 'inactive'
  if (!props.user.hasSubscription) return 'no-subscription'
  return 'subscriber'
})
</script>

<template>
  <UserStatusPanel :state="userState" />
</template>
```

### 🎯 Exemplo: `v-show` vs `v-if`

```vue
<script setup lang="ts">
import { ref } from 'vue'

const isTooltipVisible = ref(false)
const isModalOpen = ref(false)
</script>

<template>
  <!-- ✅ v-show: usado quando o elemento alterna com frequência -->
  <span v-show="isTooltipVisible" class="tooltip">Info</span>

  <!-- ✅ v-if: usado quando o elemento é caro e raramente visível -->
  <Modal v-if="isModalOpen" @close="isModalOpen = false" />
</template>
```

---

## 4. Gestão de Estado
- Para estados globais, partilha de dados entre ecrãs e fluxos assíncronos complexos, utilize o **Pinia**.
- Mantenha o fluxo de dados unidirecional: componentes emitem eventos (`emits`) para informar mudanças e recebem dados através de propriedades (`props`).

### 🎯 Exemplo: Store Pinia

```ts
// stores/cart.store.ts
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { CartItem } from '@/types/cart'

export const useCartStore = defineStore('cart', () => {
  // State
  const items = ref<CartItem[]>([])

  // Getters
  const totalItems = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  const totalPrice = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  // Actions
  function addItem(item: CartItem): void {
    const existing = items.value.find(i => i.productId === item.productId)
    if (existing) {
      existing.quantity += item.quantity
    } else {
      items.value.push(item)
    }
  }

  function removeItem(productId: number): void {
    items.value = items.value.filter(i => i.productId !== productId)
  }

  function clear(): void {
    items.value = []
  }

  return {
    items,
    totalItems,
    totalPrice,
    addItem,
    removeItem,
    clear,
  }
})
```

### 🎯 Exemplo: Fluxo unidirecional com props + emits

```vue
<!-- ParentComponent.vue -->
<script setup lang="ts">
import { ref } from 'vue'
import ChildInput from './ChildInput.vue'

const message = ref('')

function handleUpdate(value: string): void {
  message.value = value
}
</script>

<template>
  <ChildInput :model-value="message" @update="handleUpdate" />
  <p>Mensagem atual: {{ message }}</p>
</template>
```

```vue
<!-- ChildInput.vue -->
<script setup lang="ts">
defineProps<{ modelValue: string }>()
const emit = defineEmits<{ (e: 'update', value: string): void }>()
</script>

<template>
  <input
    :value="modelValue"
    @input="emit('update', ($event.target as HTMLInputElement).value)"
  />
</template>
```

### 🎯 Exemplo: Composable consumindo store internamente

```ts
// composables/useCart.ts
import { computed } from 'vue'
import { useCartStore } from '@/stores/cart.store'

export function useCart() {
  const store = useCartStore()

  const items = computed(() => store.items)
  const totalItems = computed(() => store.totalItems)
  const totalPrice = computed(() => store.totalPrice)

  return {
    items,
    totalItems,
    totalPrice,
    addItem: store.addItem,
    removeItem: store.removeItem,
    clear: store.clear,
  }
}
```

---

## 5. Testes Automatizados (Vitest)
- **Ferramenta Nativa:** A biblioteca padrão para testes unitários e de componentes no nosso ecossistema frontend é o **Vitest** (integrado nativamente com o Vite).
- **Foco dos Testes:** Priorize testar a lógica de negócio encapsulada em *composables* e o comportamento dos componentes (ex: se as *props* são renderizadas corretamente e se os cliques disparam os *emits* esperados), evitando testar detalhes rígidos de implementação do framework.
- **Teste no contexto:** O domínio nasce no backend. Ecrã, store, model e o teste Vitest ocupam a pasta com o mesmo nome. Não numa pasta `tests/` global, nem num contexto que o backend não tenha.

### 🎯 Exemplo: Teste de composable

```ts
// composables/__tests__/useCounter.test.ts
import { describe, it, expect } from 'vitest'
import { useCounter } from '../useCounter'

describe('useCounter', () => {
  it('inicia com valor padrão 0', () => {
    const { count } = useCounter()
    expect(count.value).toBe(0)
  })

  it('incrementa o valor', () => {
    const { count, increment } = useCounter()
    increment()
    expect(count.value).toBe(1)
  })

  it('dobra o valor via computed', () => {
    const { double, increment } = useCounter()
    increment()
    increment()
    expect(double.value).toBe(4)
  })
})
```

### 🎯 Exemplo: Teste de componente com Testing Library

```ts
// components/__tests__/ProductCard.test.ts
import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/vue'
import ProductCard from '../ProductCard.vue'

describe('ProductCard', () => {
  const mockProduct = {
    id: 1,
    name: 'Camiseta',
    price: 49.9,
    status: 'active' as const,
    createdAt: '2026-01-01T00:00:00Z',
  }

  it('renderiza nome e preço formatado', () => {
    render(ProductCard, { props: { product: mockProduct } })

    expect(screen.getByText('Camiseta')).toBeTruthy()
    expect(screen.getByText(/49,90/)).toBeTruthy()
  })

  it('emite select ao clicar no card', async () => {
    const onSelect = vi.fn()
    render(ProductCard, {
      props: { product: mockProduct, onSelect },
    })

    await fireEvent.click(screen.getByRole('article'))

    expect(onSelect).toHaveBeenCalledWith(1)
  })

  it('emite remove ao clicar no botão', async () => {
    const onRemove = vi.fn()
    render(ProductCard, {
      props: { product: mockProduct, onRemove },
    })

    await fireEvent.click(screen.getByRole('button', { name: /remover/i }))

    expect(onRemove).toHaveBeenCalledWith(1)
  })
})
```

### 🎯 Exemplo: Mock de API com `vi.mock`

```ts
// composables/__tests__/useCreateProduct.test.ts
import { describe, it, expect, vi } from 'vitest'
import { useCreateProduct } from '../useCreateProduct'

vi.mock('@/api/product.api', () => ({
  createProduct: vi.fn().mockResolvedValue({
    id: 1,
    name: 'Novo Produto',
    price: 100,
    status: 'active',
    createdAt: '2026-01-01T00:00:00Z',
  }),
}))

describe('useCreateProduct', () => {
  it('retorna produto criado com sucesso', async () => {
    const { mutate, isPending, error } = useCreateProduct()

    const result = await mutate({
      name: 'Novo Produto',
      price: 100,
      categoryId: null,
      tags: [],
    })

    expect(result.id).toBe(1)
    expect(result.name).toBe('Novo Produto')
    expect(isPending.value).toBe(false)
    expect(error.value).toBeNull()
  })
})
```

### 🎯 Exemplo: Configuração mínima do Vitest

```ts
// vitest.config.ts
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'node:path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/setup.ts'],
    include: ['**/__tests__/**/*.test.ts'],
  },
})
```

---

## 📋 Tabela Resumo – Boas Práticas Frontend

| Prática | Correto | Errado |
|---------|---------|--------|
| **Composition API** | `<script setup lang="ts">` | Options API em novos componentes |
| **Tipagem** | Interfaces para props/emits | Props sem tipo |
| **Reatividade** | `ref` para primitivos, `reactive` para objetos | Reatividade desnecessária em objetos grandes |
| **Listas massivas** | `shallowRef` + `markRaw` | `ref` com reatividade profunda |
| **Cálculos** | `computed` | Lógica no template |
| **Templates** | Limpos, sem `v-if` aninhado | Árvores de `v-if` profundas |
| **Alternância frequente** | `v-show` | `v-if` para elementos que alternam muito |
| **Estado global** | Pinia | Estado solto em `provide/inject` |
| **Fluxo de dados** | Props + emits | Mutação direta de props |
| **Testes** | Composable + comportamento | Detalhes internos do framework |
| **Mocks** | `vi.mock` na camada de API | Mock global no `$fetch` |

---

## ✅ Checklist final do Frontend

- [ ] Todos os componentes novos usam `<script setup lang="ts">`
- [ ] Props e emits tipados com TypeScript
- [ ] `ref` para primitivos, `reactive` para objetos, `shallowRef`/`markRaw` para grandes volumes
- [ ] Lógica derivada encapsulada em `computed`
- [ ] Templates limpos, sem árvores profundas de `v-if`
- [ ] `v-show` usado para alternâncias frequentes
- [ ] Estado global gerenciado com Pinia
- [ ] Fluxo unidirecional (props + emits)
- [ ] Composables testados isoladamente
- [ ] Testes de componente verificam comportamento visível, não implementação
- [ ] Mocks na camada de API (`*.api.ts`), não no `$fetch` global

Esse documento consolida as práticas de desenvolvimento frontend adotadas em projetos Vue 3 com TypeScript, priorizando reatividade consciente, componentização inteligente, tipagem forte e testes focados em comportamento — servindo como referência para qualquer desenvolvedor que queira aplicar esses padrões de forma consistente.