<template>
  <div class="font-sans min-h-[80vh] bg-slate-50">
    <div
      v-if="authStore.isAdmin"
      class="flex items-center justify-center p-8 animate-in fade-in duration-500"
    >
      <Card class="w-full max-w-md shadow-lg border-t-4 border-t-red-600 text-center p-8">
        <div class="mx-auto bg-red-100 w-20 h-20 rounded-full flex items-center justify-center mb-6">
          <span class="text-4xl">🚫</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 mb-2 uppercase tracking-tight">
          Acesso Restrito
        </h1>
        <p class="text-sm text-slate-500 mb-6 font-medium">
          Contas de nível administrativo não possuem carteira de moedas ativa e não podem realizar transações comerciais.
        </p>
        <Button @click="$router.push('/')" variant="outline" class="w-full font-bold">
          VOLTAR AO INÍCIO
        </Button>
      </Card>
    </div>

    <div
      v-else
      class="container mx-auto flex items-center justify-center p-4 py-8 animate-in fade-in duration-500"
    >
      <Card class="w-full max-w-2xl shadow-xl border-t-4 border-t-yellow-500 bg-white overflow-hidden">
        <CardHeader class="text-center bg-slate-50/50 border-b border-slate-100 pb-6">
          <div class="mx-auto bg-yellow-400 text-white w-16 h-16 rounded-2xl flex items-center justify-center mb-4 shadow-lg rotate-3">
            <span class="text-4xl">🪙</span>
          </div>
          <CardTitle class="text-3xl font-black text-slate-900 uppercase tracking-tighter">Mercado de Moedas</CardTitle>
          <CardDescription class="font-bold text-yellow-600 uppercase text-[10px] tracking-widest mt-1">
            Taxa de Câmbio Oficial: 1.00€ = 10 Moedas de Jogo
          </CardDescription>
        </CardHeader>

        <CardContent class="space-y-8 pt-8">
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">1. Seleciona o Montante</Label>
              <span v-if="methodLimit" class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-100 animate-pulse">
                Limite {{ selectedMethod }}: {{ methodLimit }}€
              </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <button
                v-for="pkg in packages"
                :key="pkg.euros"
                @click="amountEuro = pkg.euros"
                :disabled="methodLimit && pkg.euros > methodLimit"
                class="relative flex flex-col items-center justify-center p-4 rounded-2xl border-2 transition-all hover:shadow-md disabled:opacity-30 disabled:cursor-not-allowed"
                :class="amountEuro === pkg.euros ? 'border-yellow-500 bg-yellow-50 ring-4 ring-yellow-100' : 'border-slate-100 bg-white hover:border-yellow-200'"
              >
                <span class="text-2xl font-black text-slate-800">{{ pkg.coins }}</span>
                <span class="text-[10px] font-black text-yellow-600 uppercase tracking-tighter">Moedas</span>
                <div class="mt-3 bg-slate-900 text-white text-[10px] px-3 py-1 rounded-full font-black">
                  {{ pkg.euros.toFixed(2) }}€
                </div>
              </button>
            </div>

            <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
              <div class="flex-1">
                <Label for="custom" class="text-[10px] font-bold text-slate-400 uppercase">Valor Customizado (€)</Label>
                <Input
                  id="custom"
                  type="number"
                  min="1"
                  max="99"
                  step="1"
                  v-model.number="amountEuro"
                  :class="{'border-red-500 focus-visible:ring-red-500': isOverLimit}"
                  class="bg-white font-black text-lg h-12"
                />
              </div>
              <div class="text-right px-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Receberás</p>
                <p class="text-2xl font-black text-yellow-600 font-mono">{{ coinsReceived }} 💰</p>
              </div>
            </div>
            <p v-if="isOverLimit" class="text-[10px] text-red-500 font-bold text-center italic">
              O montante excede o limite de segurança para pagamentos via {{ selectedMethod }}.
            </p>
          </div>

          <div class="space-y-4">
            <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">2. Método de Pagamento</Label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="method in paymentMethods"
                :key="method.key"
                @click="changeMethod(method.key)"
                class="flex items-center gap-2 px-4 py-3 rounded-xl border-2 font-bold text-xs transition-all uppercase tracking-tighter"
                :class="selectedMethod === method.key ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-100 bg-white text-slate-500 hover:bg-slate-50'"
              >
                <span>{{ method.icon }}</span> {{ method.label }}
              </button>
            </div>
          </div>

          <div class="space-y-3 p-5 bg-indigo-50/50 border border-indigo-100 rounded-2xl animate-in slide-in-from-top-2">
            <Label for="ref" class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">{{ referenceLabel }}</Label>
            <Input
              id="ref"
              v-model="paymentReference"
              :placeholder="referencePlaceholder"
              class="bg-white border-indigo-200 h-12 font-medium"
            />
            <p class="text-[10px] text-slate-400 font-bold italic">{{ referenceHint }}</p>
          </div>

          <div
            v-if="errorMessage"
            class="p-4 bg-red-50 text-red-600 text-xs font-black rounded-xl border border-red-100 flex items-center gap-3 animate-shake"
          >
            <span class="text-xl">⚠️</span>
            <div class="flex flex-col">
              <span class="uppercase tracking-tight">Falha na Transação</span>
              <span class="font-medium text-[11px] opacity-80 normal-case">{{ errorMessage }}</span>
            </div>
          </div>
        </CardContent>

        <CardFooter class="flex flex-col gap-4 border-t p-8 bg-slate-50/50">
          <div class="flex justify-between w-full items-baseline">
            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Total a debitar:</span>
            <span class="text-3xl font-black text-slate-900 tracking-tighter" :class="{'text-red-500': isOverLimit}">
              {{ Number(amountEuro || 0).toFixed(2) }}€
            </span>
          </div>

          <Button
            class="w-full bg-slate-900 hover:bg-black text-white font-black h-14 text-lg shadow-xl transition-all active:scale-95 disabled:opacity-50"
            :disabled="isLoading || amountEuro < 1 || !paymentReference || isOverLimit"
            @click="handlePurchase"
          >
            <span v-if="isLoading" class="animate-spin mr-3">🌀</span>
            {{ isLoading ? 'A COMUNICAR COM O BANCO...' : `EFETUAR PAGAMENTO SEGURO` }}
          </Button>
          <p class="text-center text-[9px] text-slate-400 uppercase font-bold tracking-widest">
            🔐 Transação encriptada e processada via SSL 256-bit
          </p>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/services/api'
import { useToastStore } from '@/stores/toast'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()
const apiStore = useAPIStore()
const toastStore = useToastStore()

// --- ESTADO ---
const amountEuro = ref(10)
const selectedMethod = ref('MBWAY')
const paymentReference = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

/**
 * G2/C3: Configuração de pacotes e métodos de pagamento.
 * Simula um gateway financeiro com limites de segurança por método.
 */
const packages = [
  { euros: 3, coins: 30 },
  { euros: 5, coins: 50 },
  { euros: 10, coins: 100 },
  { euros: 20, coins: 200 },
]

const paymentMethods = [
  { key: 'MBWAY', label: 'MBWAY', icon: '📱', limit: 5 },
  { key: 'PAYPAL', label: 'PayPal', icon: '🅿️', limit: 10 },
  { key: 'MB', label: 'Multibanco', icon: '🏧', limit: 20 },
  { key: 'VISA', label: 'Visa', icon: '💳', limit: 30 },
  { key: 'IBAN', label: 'Transferência', icon: '🏦', limit: 50 },
]

// --- LÓGICA COMPUTADA ---

// G2: Cálculo reativo da quantidade de moedas baseada no valor em euros.
const coinsReceived = computed(() => (Math.floor(amountEuro.value) || 0) * 10)

const methodLimit = computed(() => {
  return paymentMethods.find(m => m.key === selectedMethod.value)?.limit || 99
})

const isOverLimit = computed(() => amountEuro.value > methodLimit.value)

/**
 * NF5: Adaptação dinâmica da interface baseada no método de pagamento (UX).
 */
const referenceLabel = computed(() => {
  const labels = { MBWAY: 'Telemóvel', VISA: 'Nº Cartão', PAYPAL: 'Email de Conta', MB: 'Referência Pagamento', IBAN: 'IBAN da Conta' }
  return labels[selectedMethod.value] || 'Referência'
})

const referencePlaceholder = computed(() => {
  const placeholders = { MBWAY: '9XXXXXXXX', VISA: '4XXXXXXXXXXXXXXX', PAYPAL: 'exemplo@email.com', IBAN: 'PT50...', MB: '12345-123456789' }
  return placeholders[selectedMethod.value] || ''
})

const referenceHint = computed(() => {
  if (selectedMethod.value === 'MBWAY') return 'Insira o número de telemóvel associado ao serviço (9 dígitos).'
  if (selectedMethod.value === 'VISA') return 'Insira os 16 dígitos do cartão (deve começar por 4).'
  if (selectedMethod.value === 'PAYPAL') return 'Certifique-se que o email introduzido é uma conta válida.'
  return 'Utilize os dados fornecidos pelo seu prestador de serviços financeiros.'
})

// --- AÇÕES (G2/NF2) ---

const changeMethod = (methodKey) => {
  selectedMethod.value = methodKey
  errorMessage.value = ''
  // NF5: Ajuste automático para cumprir políticas de segurança.
  if (amountEuro.value > methodLimit.value) {
    amountEuro.value = methodLimit.value
  }
}

/**
 * G2: Processa a transação financeira via API RESTful.
 * NF2: Comunica com o servidor para criar o registo na tabela coin_transactions.
 */
const handlePurchase = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const payload = {
      type: selectedMethod.value,
      reference: paymentReference.value,
      value: Number(amountEuro.value),
    }

    // NF2: Pedido POST para processamento de pagamento.
    await apiStore.post('purchases', payload)

    /**
     * G2: Após sucesso, atualiza o perfil do utilizador para refletir o novo saldo.
     * NF8: Sincroniza o estado da Single-Page Application (SPA) sem recarregar a página.
     */
    await authStore.refreshUser()

    toastStore.showSuccess(`Operação concluída. Foram creditadas ${coinsReceived.value} moedas na sua conta.`)
    router.push('/')
  } catch (error) {
    const status = error.response?.status
    const backendMsg = error.response?.data?.message

    /**
     * G2/NF5: Tradução amigável de erros de rede ou de negócio.
     */
    if (status === 422) {
      if (backendMsg?.includes('limit')) {
        errorMessage.value = 'A instituição financeira recusou o débito por exceder o limite diário definido para este método.'
      } else {
        errorMessage.value = 'Os dados introduzidos não foram reconhecidos pelo processador de pagamentos.'
      }
    } else if (status === 403) {
      errorMessage.value = 'A sua conta não tem permissões para realizar esta transação financeira.'
    } else {
      errorMessage.value = 'Não foi possível estabelecer ligação com a entidade bancária. Por favor, tente novamente.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* NF5: Feedback tátil em caso de erro no formulário. */
.animate-shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>
