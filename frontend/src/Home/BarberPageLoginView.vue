<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AuthSplitLayout from '@/components/ui/AuthSplitLayout.vue'
import AuthSwitch from '@/components/ui/AuthSwitch.vue'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import { AUTH_PHOTOS } from '@/assets/auth/authPhotos'
import { usePageMeta } from '@/composables/usePageMeta'
import BarberLoginPanel from '@/Domain/Identity/BarberLoginPanel.vue'
import PublicPageStatus from '@/Domain/Settings/PublicPageStatus.vue'
import { usePublicPage } from '@/Domain/Settings/usePublicPage'

/**
 * /<slug>/panel/login: the barbershop's own sign-in, with its theme and photo. Only its barbers get in; the panel stays
 * under the barbershop's address instead of linkcut's.
 */
const { t } = useI18n()
const route = useRoute()
const publicPage = usePublicPage()
const slug = computed(() => String(route.params.slug))
const profile = computed(() => publicPage.page?.profile)

const photo = computed(() => {
  const url = profile.value?.backgroundUrl || publicPage.page?.portfolio.coverUrl
  return url
    ? { src: url, srcset: `${url} 1080w`, alt: t('barberLogin.photoAlt', { name: profile.value?.name ?? '' }) }
    : { ...AUTH_PHOTOS.login, alt: t('authPage.photos.login') }
})

usePageMeta(
  () => (profile.value ? t('barberLogin.metaTitle', { name: profile.value.name }) : ''),
  () => t('authPage.barber.description'),
)
</script>

<template>
  <PublicPageStatus v-if="!profile" :status="publicPage.status" @retry="publicPage.reload()" />
  <AuthSplitLayout v-else :photo="photo" photo-key="login">
    <div class="mb-6 flex items-center gap-3">
      <AvatarImage :src="profile.avatarUrl" :alt="t('bioLink.avatarAlt', { name: profile.name })" size="md" />
      <p class="font-display text-lg font-extrabold break-words">{{ profile.name }}</p>
    </div>
    <BarberLoginPanel :slug="slug">
      <template #switch>
        <AuthSwitch :question="t('barberLogin.notYours')" :action="t('barberLogin.findMine')" :to="{ name: 'login' }" />
      </template>
    </BarberLoginPanel>
  </AuthSplitLayout>
</template>
