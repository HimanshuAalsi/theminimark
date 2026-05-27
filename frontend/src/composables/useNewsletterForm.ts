import { ref } from 'vue'
import { ApiError } from '@/lib/api'
import { submitNewsletter } from '@/lib/newsletter'

export function useNewsletterForm(source: string) {
  const email = ref('')
  const feedback = ref('')
  const busy = ref(false)

  async function submit(e: Event) {
    e.preventDefault()
    feedback.value = ''
    const addr = email.value.trim()
    if (!addr) {
      feedback.value = 'Enter your email address.'
      return
    }
    if (busy.value) return
    busy.value = true
    try {
      const res = await submitNewsletter(addr, source)
      feedback.value = res.message
      if (res.ok) {
        email.value = ''
      }
    } catch (err) {
      if (err instanceof ApiError) {
        feedback.value =
          typeof err.body === 'object' && err.body !== null && 'message' in err.body
            ? String((err.body as { message: unknown }).message)
            : 'Could not subscribe right now.'
      } else {
        feedback.value = 'Could not reach the server. Try again later.'
      }
    } finally {
      busy.value = false
    }
  }

  return { email, feedback, busy, submit }
}
