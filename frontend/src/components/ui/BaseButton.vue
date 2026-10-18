<script setup lang="ts">
import { computed } from 'vue'

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger'
type ButtonSize = 'md' | 'lg'

interface Props {
  variant?: ButtonVariant
  size?: ButtonSize
  /** Hard offset shadow under the pill (Figma "hard shadow" style). */
  elevated?: boolean
  /** Renders a link instead of a button. */
  href?: string
  to?: string
  type?: 'button' | 'submit'
  disabled?: boolean
  block?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  elevated: false,
  href: undefined,
  to: undefined,
  type: 'button',
  disabled: false,
  block: false,
})

const VARIANT_CLASSES: Record<ButtonVariant, string> = {
  primary: 'bg-accent text-on-accent [--edge:var(--accent-edge)]',
  secondary: 'bg-surface text-ink ring-1 ring-line [--edge:var(--neutral-edge)]',
  ghost: 'bg-transparent text-ink ring-1 ring-line [--edge:transparent]',
  danger: 'bg-transparent text-danger ring-1 ring-danger/40 [--edge:transparent]',
}

const SIZE_CLASSES: Record<ButtonSize, string> = {
  // --control-height feeds the `rounded-button` radius (shape chosen in Settings > Appearance).
  md: 'h-11 [--control-height:2.75rem] px-5 text-sm',
  lg: 'h-16 [--control-height:4rem] px-7 text-base',
}

const classes = computed(() => [
  'inline-flex items-center justify-center gap-2 rounded-button font-display font-extrabold tracking-tight',
  'transition duration-150 ease-out select-none',
  'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent',
  'disabled:cursor-not-allowed disabled:opacity-40',
  VARIANT_CLASSES[props.variant],
  SIZE_CLASSES[props.size],
  props.block ? 'w-full' : '',
  props.elevated
    ? 'shadow-[0_6px_0_0_var(--edge),var(--shadow-soft)] hover:-translate-y-0.5 hover:shadow-[0_8px_0_0_var(--edge),var(--shadow-float)] active:translate-y-1 active:shadow-[0_2px_0_0_var(--edge)] disabled:translate-y-0'
    : 'shadow-soft hover:brightness-110 active:scale-[0.98]',
])

const tag = computed(() => (props.to ? 'RouterLink' : props.href ? 'a' : 'button'))
const linkAttrs = computed(() => {
  if (props.to) return { to: props.to }
  if (props.href) return { href: props.href, target: '_blank', rel: 'noopener noreferrer' }
  return { type: props.type, disabled: props.disabled }
})
</script>

<template>
  <component :is="tag" v-bind="linkAttrs" :class="classes">
    <slot />
  </component>
</template>
