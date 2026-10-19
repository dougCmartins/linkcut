import { onBeforeUnmount, shallowRef } from 'vue'

/**
 * Keeps the last undoable action for a few seconds (the "Desfazer" notice).
 * `offer` starts the window, `take` returns the action and closes it.
 */
export function useUndoWindow<T>(durationMs = 5000) {
  const pending = shallowRef<T | null>(null)
  let timer: ReturnType<typeof setTimeout> | undefined

  function dismiss(): void {
    clearTimeout(timer)
    pending.value = null
  }

  function offer(action: T): void {
    dismiss()
    pending.value = action
    timer = setTimeout(dismiss, durationMs)
  }

  function take(): T | null {
    const action = pending.value
    dismiss()
    return action
  }

  onBeforeUnmount(() => clearTimeout(timer))
  return { pending, offer, take, dismiss }
}
