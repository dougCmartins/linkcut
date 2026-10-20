<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ImageUploader from '@/components/ui/ImageUploader.vue'
import SocialIcon from '@/components/ui/SocialIcon.vue'
import TextArea from '@/components/ui/TextArea.vue'
import TextField from '@/components/ui/TextField.vue'
import AddressEditor from './AddressEditor.vue'
import LinkPreview from './LinkPreview.vue'
import PhoneFrame from './PhoneFrame.vue'
import SettingsCard from './SettingsCard.vue'
import { useSettingsStore } from './settings.store'
import { SettingsSection, imageUrl } from './settings.model'
import { TEXT_LIMITS } from './settings.validation'
import { useImageUpload } from './useImageUpload'
import { useSectionErrors } from './useSectionErrors'

const { t } = useI18n()
const store = useSettingsStore()
const { errorFor } = useSectionErrors(SettingsSection.Profile)
const { upload, isBusy, failedField } = useImageUpload()

const profile = computed(() => store.drafts.profile)
const avatarUrl = computed(() => imageUrl(profile.value.avatar))
const backgroundUrl = computed(() => imageUrl(profile.value.background))
</script>

<template>
  <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start">
    <div class="grid gap-5">
      <SettingsCard :title="t('settings.link.profileTitle')" :description="t('settings.link.profileDescription')">
        <ImageUploader
          :label="t('settings.link.avatar')"
          :src="avatarUrl"
          shape="circle"
          :is-busy="isBusy('avatar')"
          @select="upload('avatar', $event, (image) => (profile.avatar = image))"
          @remove="profile.avatar = null"
        />
        <TextField v-model="profile.name" :label="t('settings.link.name')" :maxlength="TEXT_LIMITS.name.max" :error="errorFor('name')" autocomplete="name" />
        <TextField
          v-model="profile.handle"
          :label="t('settings.link.handle')"
          :placeholder="t('settings.link.handlePlaceholder')"
          :maxlength="TEXT_LIMITS.handle.max"
          :error="errorFor('handle')"
        />
        <TextArea v-model="profile.bio" :label="t('settings.link.bio')" :maxlength="TEXT_LIMITS.bio.max" :rows="3" :error="errorFor('bio')" />
      </SettingsCard>

      <SettingsCard :title="t('settings.link.backgroundTitle')" :description="t('settings.link.backgroundDescription')">
        <ImageUploader
          :label="t('settings.link.background')"
          :src="backgroundUrl"
          :is-busy="isBusy('background')"
          @select="upload('background', $event, (image) => (profile.background = image))"
          @remove="profile.background = null"
        />
      </SettingsCard>

      <SettingsCard :title="t('settings.link.whatsappTitle')">
        <TextField
          v-model="profile.whatsappNumber"
          type="tel"
          inputmode="tel"
          autocomplete="tel"
          :label="t('settings.link.whatsappNumber')"
          :hint="t('settings.link.whatsappHint')"
          :error="errorFor('whatsappNumber')"
        />
        <TextArea
          v-model="profile.whatsappMessage"
          :label="t('settings.link.whatsappMessage')"
          :hint="t('settings.link.whatsappMessageHint')"
          :maxlength="TEXT_LIMITS.whatsappMessage.max"
          :rows="2"
          :error="errorFor('whatsappMessage')"
        />
      </SettingsCard>

      <SettingsCard id="address" :title="t('settings.address.title')" :description="t('settings.address.description')">
        <AddressEditor v-model="profile.address" :saved="store.profile.address" :error-for="errorFor" />
      </SettingsCard>

      <SettingsCard :title="t('settings.link.socialsTitle')" :description="t('settings.link.socialsDescription')">
        <div v-for="(social, index) in profile.socials" :key="social.network" class="flex items-start gap-3">
          <span class="mt-7 flex size-10 shrink-0 items-center justify-center rounded-full bg-raised ring-1 ring-line" aria-hidden="true">
            <SocialIcon :network="social.network" class="size-4" />
          </span>
          <TextField
            v-model="social.url"
            class="min-w-0 flex-1"
            type="url"
            inputmode="url"
            :label="t(`socials.${social.network}`)"
            :placeholder="t('settings.link.socialPlaceholder')"
            :error="errorFor(`socials.${index}`)"
          />
        </div>
      </SettingsCard>

      <p v-if="failedField" class="text-sm text-danger" role="alert">{{ t('settings.image.uploadFailed') }}</p>
    </div>

    <div class="lg:sticky lg:top-6">
      <PhoneFrame :theme="store.drafts.theme">
        <LinkPreview :profile="profile" :avatar-url="avatarUrl" :background-url="backgroundUrl" />
      </PhoneFrame>
    </div>
  </div>
</template>
