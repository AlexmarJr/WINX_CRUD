<script setup lang="ts">
import { apiErrorMessage, apiWrite } from '~/utils/api'
import { confirmDanger, showSuccessToast } from '~/utils/confirmAction'
import { renderSafeMarkdown } from '~/utils/safeMarkdown'

type ChatMessage = { role: 'user' | 'assistant', text: string }
type ChatResponse = { data: { reply: string, history: ChatMessage[] }, message: string }

const auth = useAuthStore()
const isOpen = ref(false)
const expanded = ref(false)
const draft = ref('')
const messages = ref<ChatMessage[]>([])
const sending = ref(false)
const errorMessage = ref('')
const messageList = ref<HTMLElement | null>(null)
const nearBottom = ref(true)
const typingIndex = ref<number | null>(null)
const visibleChars = ref(0)
let typingTimer: ReturnType<typeof setInterval> | null = null

const shortcuts = [
  'Me ajude a otimizar meu estoque de produtos.',
  'Me dê um resumo do meu estoque de produtos.'
]

function storageKey(): string | null {
  return auth.user?.id ? `ai-chat-history:${auth.user.id}` : null
}

function stopTyping(): void {
  if (typingTimer) clearInterval(typingTimer)
  typingTimer = null
  typingIndex.value = null
}

function saveHistory(): void {
  const key = storageKey()
  if (!key || !import.meta.client) return

  try {
    sessionStorage.setItem(key, JSON.stringify(messages.value.slice(-40)))
  } catch {
    // O chat continua funcionando caso o armazenamento da aba esteja indisponível.
  }
}

watch(() => auth.user?.id, (id) => {
  stopTyping()
  messages.value = []
  draft.value = ''
  errorMessage.value = ''

  if (!id || !import.meta.client) return

  try {
    const saved = JSON.parse(sessionStorage.getItem(`ai-chat-history:${id}`) || '[]')
    if (Array.isArray(saved)) {
      messages.value = saved
        .filter((entry): entry is ChatMessage =>
          entry && (entry.role === 'user' || entry.role === 'assistant')
          && typeof entry.text === 'string'
        )
        .slice(-40)
    }
  } catch {
    messages.value = []
  }
}, { immediate: true })

watch(isOpen, async (opened) => {
  if (opened) {
    await nextTick()
    scrollToBottom(true)
  }
})

watch(expanded, async () => {
  await nextTick()
  scrollToBottom(true)
})

onUnmounted(stopTyping)

function updateScrollPosition(): void {
  const element = messageList.value
  if (!element) return
  nearBottom.value = element.scrollHeight - element.scrollTop - element.clientHeight < 72
}

function scrollToBottom(force = false): void {
  const element = messageList.value
  if (!element || (!force && !nearBottom.value)) return
  element.scrollTop = element.scrollHeight
}

function messageHtml(message: ChatMessage, index: number): string {
  const text = typingIndex.value === index ? message.text.slice(0, visibleChars.value) : message.text
  return renderSafeMarkdown(text)
}

function startTyping(index: number): void {
  stopTyping()
  typingIndex.value = index
  visibleChars.value = 0
  typingTimer = setInterval(() => {
    const message = messages.value[index]
    if (!message) {
      stopTyping()
      return
    }

    visibleChars.value = Math.min(message.text.length, visibleChars.value + Math.max(4, Math.ceil(message.text.length / 360)))
    void nextTick(() => scrollToBottom())

    if (visibleChars.value >= message.text.length) stopTyping()
  }, 22)
}

async function send(): Promise<void> {
  const question = draft.value.trim()
  if (!question || sending.value || typingIndex.value !== null || !auth.user) return

  const previous = [...messages.value]
  errorMessage.value = ''
  draft.value = ''
  sending.value = true
  messages.value = [...previous, { role: 'user', text: question }]
  await nextTick()
  scrollToBottom(true)

  try {
    const response = await apiWrite<ChatResponse>('/api/v1/ai-chat', 'POST', {
      message: question,
      history: previous.slice(-12)
    })
    messages.value = [...previous, { role: 'user', text: question }, { role: 'assistant', text: response.data.reply }].slice(-40) as ChatMessage[]
    saveHistory()
    await nextTick()
    startTyping(messages.value.length - 1)
  } catch (error) {
    messages.value = previous
    draft.value = question
    errorMessage.value = apiErrorMessage(error, 'Não consegui responder agora. Tente novamente.')
    await nextTick()
    scrollToBottom()
  } finally {
    sending.value = false
  }
}

async function clearHistory(): Promise<void> {
  const confirmed = await confirmDanger({
    title: 'Apagar conversa?',
    text: 'As mensagens desta conversa serão removidas desta aba.',
    confirmText: 'Apagar conversa'
  })
  if (!confirmed) return

  stopTyping()
  messages.value = []
  draft.value = ''
  errorMessage.value = ''
  const key = storageKey()
  if (key) sessionStorage.removeItem(key)
  void showSuccessToast('Conversa apagada.')
  void nextTick(() => scrollToBottom(true))
}
</script>

<template>
  <div v-if="auth.user" class="ai-chat-root">
    <button v-if="!isOpen" class="ai-chat-launcher" type="button" aria-label="Abrir Assistente IA" @click="isOpen = true">
      <AppIcon name="message" aria-hidden="true" />
    </button>

    <div v-if="isOpen && expanded" class="ai-chat-backdrop" @click.self="expanded = false" />

    <section v-if="isOpen" class="ai-chat-panel" :class="{ 'ai-chat-panel-expanded': expanded }" role="dialog" :aria-modal="expanded" aria-label="Assistente IA">
      <header class="ai-chat-header">
        <div class="ai-chat-title">
          <span class="ai-chat-header-icon"><AppIcon name="message" aria-hidden="true" /></span>
          <div><strong>Assistente IA</strong><small>Ajuda rápida</small></div>
        </div>
        <div class="ai-chat-header-actions">
          <button type="button" :aria-label="expanded ? 'Recolher chat' : 'Expandir chat'" @click="expanded = !expanded"><AppIcon :name="expanded ? 'compress' : 'expand'" aria-hidden="true" /></button>
          <button type="button" aria-label="Fechar chat" @click="isOpen = false; expanded = false"><AppIcon name="close" aria-hidden="true" /></button>
        </div>
      </header>

      <div ref="messageList" class="ai-chat-messages" aria-live="polite" @scroll="updateScrollPosition">
        <div v-if="!messages.length" class="ai-chat-message ai-chat-message-assistant">
          <div class="ai-chat-bubble"><p>Oi! Envie sua pergunta.</p></div>
        </div>

        <div v-for="(entry, index) in messages" :key="index" class="ai-chat-message" :class="`ai-chat-message-${entry.role}`">
          <div v-if="entry.role === 'user'" class="ai-chat-bubble">{{ entry.text }}</div>
          <div v-else class="ai-chat-bubble ai-chat-markdown" v-html="messageHtml(entry, index)" />
        </div>

        <div v-if="sending" class="ai-chat-message ai-chat-message-assistant">
          <div class="ai-chat-bubble ai-chat-thinking">IA pensando...</div>
        </div>
        <div v-if="errorMessage" class="ai-chat-message ai-chat-message-assistant">
          <div class="ai-chat-bubble ai-chat-error" role="alert">
            {{ errorMessage }}
            <button type="button" @click="send">Tentar novamente</button>
          </div>
        </div>
      </div>

      <div class="ai-chat-composer">
        <div class="ai-chat-shortcuts">
          <button v-for="shortcut in shortcuts" :key="shortcut" type="button" :disabled="sending || typingIndex !== null" @click="draft = shortcut">{{ shortcut }}</button>
        </div>
        <form @submit.prevent="send">
          <label class="ai-chat-input-label" for="ai-chat-input">Sua mensagem</label>
          <textarea id="ai-chat-input" v-model="draft" maxlength="2000" rows="2" placeholder="Digite sua mensagem..." :disabled="sending || typingIndex !== null" @keydown.enter.exact.prevent="send" />
          <div class="ai-chat-composer-actions">
            <button class="ai-chat-clear" type="button" :disabled="sending" @click="clearHistory"><AppIcon name="trash" aria-hidden="true" /> Apagar</button>
            <button class="ai-chat-send" type="submit" :disabled="!draft.trim() || sending || typingIndex !== null" aria-label="Enviar mensagem"><AppIcon name="send" aria-hidden="true" /></button>
          </div>
        </form>
      </div>
    </section>
  </div>
</template>
