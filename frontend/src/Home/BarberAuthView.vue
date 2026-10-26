<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AuthSplitLayout from '@/components/ui/AuthSplitLayout.vue'
import AuthSwitch from '@/components/ui/AuthSwitch.vue'
import { AUTH_PHOTOS } from '@/assets/auth/authPhotos'
import { usePageMeta } from '@/composables/usePageMeta'

/**
 * linkcut's own entrance (/, /cadastrar, /entrar): one page where "Criar conta" comes first and "Entrar" is one
 * click away. Barbers sign in on their own page (/<slug>/panel/login); here "Entrar" sends them its link. The three routes share this component, so switching only swaps the form and the photo.
 */
// Only the form of the current mode is downloaded; the other one comes when the barber switches.
const FindPagePanel = defineAsyncComponent(() => import('@/Domain/Identity/FindPagePanel.vue'))
const SignUpPanel = defineAsyncComponent(() => import('@/Domain/Onboarding/SignUpPanel.vue'))

const { t } = useI18n()
const route = useRoute()

const isLogin = computed(() => route.meta.authMode === 'login')
/** Keeps "?redirect=" (a page asked before signing in) when switching. */
const keptQuery = computed(() => (typeof route.query.redirect === 'string' ? { redirect: route.query.redirect } : {}))
const photo = computed(() =>
  isLogin.value ? { ...AUTH_PHOTOS.login, alt: t('authPage.photos.login') } : { ...AUTH_PHOTOS.signUp, alt: t('authPage.photos.signUp') },
)

usePageMeta(
  () => (isLogin.value ? t('authPage.barber.loginMeta') : t('authPage.barber.signUpMeta')),
  () => t('authPage.barber.description'),
)
</script>

<template>
  <AuthSplitLayout :photo="photo" :photo-key="isLogin ? 'login' : 'sign-up'">
    <FindPagePanel v-if="isLogin">
      <template #switch>
        <AuthSwitch
          :question="t('authPage.barber.noAccount')"
          :action="t('authPage.barber.signUp')"
          :to="{ name: 'sign-up', query: keptQuery }"
        />
      </template>
    </FindPagePanel>
    <SignUpPanel v-else>
      <template #switch>
        <AuthSwitch
          :question="t('authPage.barber.haveAccount')"
          :action="t('authPage.barber.login')"
          :to="{ name: 'login', query: keptQuery }"
        />
      </template>
    </SignUpPanel>
  </AuthSplitLayout>
</template>
