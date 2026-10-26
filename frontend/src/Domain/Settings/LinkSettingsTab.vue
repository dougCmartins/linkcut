<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ImageUploader from '@/components/ui/ImageUploader.vue'
import SelectField from '@/components/ui/SelectField.vue'
import SocialIcon from '@/components/ui/SocialIcon.vue'
import TextArea from '@/components/ui/TextArea.vue'
import TextField from '@/components/ui/TextField.vue'
import AddressEditor from './AddressEditor.vue'
import PublicLinkCard from './PublicLinkCard.vue'
import LinkPreview from './LinkPreview.vue'
import PhoneFrame from './PhoneFrame.vue'
import SettingsCard from './SettingsCard.vue'
import { useSettingsStore } from './settings.store'
import { SettingsSection, imageUrl, type ProfileLink } from './settings.model'
import { TEXT_LIMITS } from './settings.validation'
import { useImageUpload } from './useImageUpload'
import { useSectionErrors } from './useSectionErrors'
import { PROFILE_LINK_ICONS } from '@/types/profileLinkIcon'

const { t } = useI18n()
const store = useSettingsStore()
const { errorFor } = useSectionErrors(SettingsSection.Profile)
const { upload, isBusy, errorFor: uploadErrorFor } = useImageUpload()

const profile = computed(() => store.drafts.profile)
const avatarUrl = computed(() => imageUrl(profile.value.avatar))
const backgroundUrl = computed(() => imageUrl(profile.value.background))
const iconOptions = computed(() => [
  { value: '', label: t('settings.link.linkIconNone') },
  ...PROFILE_LINK_ICONS.map((icon) => ({ value: icon, label: t(`settings.link.icons.${icon}`) })),
])

function addLink(): void {
  profile.value.links.push({ title: '', url: '', icon: null })
}

function removeLink(index: number): void {
  profile.value.links.splice(index, 1)
}

function moveLink(index: number, direction: -1 | 1): void {
  const next = index + direction
  const links = profile.value.links
  const current = links[index]
  const target = links[next]
  if (!current || !target) return
  links[index] = target
  links[next] = current
}

function setIcon(link: ProfileLink, value: string): void {
  link.icon = (PROFILE_LINK_ICONS as readonly string[]).includes(value) ? (value as ProfileLink['icon']) : null
}

function iconValue(link: ProfileLink): string {
  return link.icon ?? ''
}

</script>

<template>
  <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start">
    <div class="grid gap-5">
      <PublicLinkCard />
      <SettingsCard :title="t('settings.link.profileTitle')" :description="t('settings.link.profileDescription')">
        <ImageUploader
          :label="t('settings.link.avatar')"
          :src="avatarUrl"
          shape="circle"
          :is-busy="isBusy('avatar')"
          :error="uploadErrorFor('avatar')"
          @select="upload('avatar', $event, (image) => (profile.avatar = image), () => (profile.avatar = store.profile.avatar))"
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
          :error="uploadErrorFor('background')"
          @select="upload('background', $event, (image) => (profile.background = image), () => (profile.background = store.profile.background))"
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

      <SettingsCard :title="t('settings.link.linksTitle')" :description="t('settings.link.linksDescription')">
        <div v-for="(link, index) in profile.links" :key="index" class="grid gap-3 rounded-2xl bg-raised p-4 ring-1 ring-line">
          <TextField
            v-model="link.title"
            :label="t('settings.link.linkTitle')"
            :maxlength="TEXT_LIMITS.linkTitle.max"
            :error="errorFor(`links.${index}.title`)"
          />
          <TextField
            v-model="link.url"
            type="url"
            inputmode="url"
            :label="t('settings.link.linkUrl')"
            :placeholder="t('settings.link.socialPlaceholder')"
            :maxlength="TEXT_LIMITS.linkUrl.max"
            :error="errorFor(`links.${index}.url`)"
          />
          <SelectField
            :model-value="iconValue(link)"
            :label="t('settings.link.linkIcon')"
            :options="iconOptions"
            @update:model-value="setIcon(link, $event)"
          />
          <div class="flex flex-wrap gap-3">
            <button type="button" class="text-sm font-semibold text-muted hover:text-ink disabled:opacity-40" :disabled="index === 0" :aria-label="t('settings.link.linkUp')" @click="moveLink(index, -1)">
              {{ t('settings.link.linkUp') }}
            </button>
            <button type="button" class="text-sm font-semibold text-muted hover:text-ink disabled:opacity-40" :disabled="index === profile.links.length - 1" :aria-label="t('settings.link.linkDown')" @click="moveLink(index, 1)">
              {{ t('settings.link.linkDown') }}
            </button>
            <button type="button" class="text-sm font-semibold text-danger" :aria-label="t('settings.link.linkRemove')" @click="removeLink(index)">
              {{ t('settings.link.linkRemove') }}
            </button>
          </div>
        </div>
        <button type="button" class="text-sm font-semibold text-accent" @click="addLink">{{ t('settings.link.linkAdd') }}</button>
      </SettingsCard>

    </div>

    <div class="lg:sticky lg:top-6">
      <PhoneFrame :theme="store.drafts.theme">
        <LinkPreview :profile="profile" :avatar-url="avatarUrl" :background-url="backgroundUrl" />
      </PhoneFrame>
    </div>
  </div>
</template>
