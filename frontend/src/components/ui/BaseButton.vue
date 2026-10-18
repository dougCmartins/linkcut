<script setup lang="ts">
import { computed } from 'vue'
import { buttonSkinClasses, type ButtonTone } from './buttonSkin'
import { useButtonStyle } from '@/composables/useButtonStyle'

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger'
type ButtonSize = 'md' | 'lg'

interface Props {
  variant?: ButtonVariant
  size?: ButtonSize
  /** Main button: uses the button style chosen in Settings > Appearance (primary/secondary only). */
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
  primary: 'bg-accent text-on-accent',
  secondary: 'bg-surface text-ink ring-1 ring-line',
  ghost: 'bg-transparent text-ink ring-1 ring-line',
  danger: 'bg-transparent text-danger ring-1 ring-danger/40',
}

const SIZE_CLASSES: Record<ButtonSize, string> = {
  // --control-height feeds the `rounded-button` radius (shape chosen in Settings > Appearance).
  md: 'h-11 [--control-height:2.75rem] px-5 text-sm',
  lg: 'h-16 [--control-height:4rem] px-7 text-base',
}

const buttonStyle = useButtonStyle()

const lookClasses = computed(() => {
  const isThemed = props.elevated && (props.variant === 'primary' || props.variant === 'secondary')
  return isThemed
    ? buttonSkinClasses(buttonStyle.value, props.variant as ButtonTone)
    : `${VARIANT_CLASSES[props.variant]} shadow-soft hover:brightness-110 active:scale-[0.98]`
})

const classes = computed(() => [
  'inline-flex items-center justify-center gap-2 rounded-button font-display font-extrabold tracking-tight',
  'transition duration-150 ease-out select-none',
  'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent',
  'disabled:cursor-not-allowed disabled:opacity-40',
  lookClasses.value,
  SIZE_CLASSES[props.size],
  props.block ? 'w-full' : '',
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
