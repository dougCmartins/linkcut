<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ImageUploader from '@/components/ui/ImageUploader.vue'
import TextArea from '@/components/ui/TextArea.vue'
import TextField from '@/components/ui/TextField.vue'
import PhoneFrame from './PhoneFrame.vue'
import PortfolioPreview from './PortfolioPreview.vue'
import SettingsCard from './SettingsCard.vue'
import { useSettingsStore } from './settings.store'
import { MAX_PORTFOLIO_PHOTOS, SettingsSection } from './settings.model'
import { TEXT_LIMITS } from './settings.validation'
import { useImageUpload } from './useImageUpload'
import { useSectionErrors } from './useSectionErrors'

const { t } = useI18n()
const store = useSettingsStore()
const { errorFor } = useSectionErrors(SettingsSection.Portfolio)
const { upload, isBusy, failedField } = useImageUpload()

const portfolio = computed(() => store.drafts.portfolio)
const coverUrl = computed(() => store.resolveImage(portfolio.value.cover))
const photoUrls = computed(() => portfolio.value.photos.map((photo) => store.resolveImage(photo.src)))
const canAddPhoto = computed(() => portfolio.value.photos.length < MAX_PORTFOLIO_PHOTOS)

function addPhoto(file: File): void {
  void upload('newPhoto', file, (source) => {
    if (!canAddPhoto.value) return
    portfolio.value.photos.push({ id: `photo-${Date.now()}`, src: source, alt: '' })
  })
}

function replacePhoto(index: number, file: File): void {
  void upload(`photo-${index}`, file, (source) => {
    const photo = portfolio.value.photos[index]
    if (photo) photo.src = source
  })
}

function removePhoto(index: number): void {
  portfolio.value.photos.splice(index, 1)
}
</script>

<template>
  <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start">
    <div class="grid gap-5">
      <SettingsCard :title="t('settings.portfolio.coverTitle')">
        <ImageUploader
          :label="t('settings.portfolio.cover')"
          :src="coverUrl"
          :is-busy="isBusy('cover')"
          @select="upload('cover', $event, (source) => (portfolio.cover = source))"
          @remove="portfolio.cover = null"
        />
      </SettingsCard>

      <SettingsCard :title="t('settings.portfolio.textsTitle')">
        <TextArea v-model="portfolio.quote" :label="t('settings.portfolio.quote')" :maxlength="TEXT_LIMITS.quote.max" :rows="3" :error="errorFor('quote')" />
        <TextArea
          v-model="portfolio.story"
          :label="t('settings.portfolio.story')"
          :hint="t('settings.portfolio.storyHint')"
          :maxlength="TEXT_LIMITS.story.max"
          :rows="8"
          :error="errorFor('story')"
        />
      </SettingsCard>

      <SettingsCard :title="t('settings.portfolio.highlightsTitle')" :description="t('settings.portfolio.highlightsDescription')">
        <div v-for="(highlight, index) in portfolio.highlights" :key="index" class="grid grid-cols-2 gap-3">
          <TextField
            v-model="highlight.label"
            :label="t('settings.portfolio.highlightLabel', { number: index + 1 })"
            :maxlength="TEXT_LIMITS.highlightLabel.max"
            :error="errorFor(`highlights.${index}.label`)"
          />
          <TextField
            v-model="highlight.value"
            :label="t('settings.portfolio.highlightValue', { number: index + 1 })"
            :maxlength="TEXT_LIMITS.highlightValue.max"
            :error="errorFor(`highlights.${index}.value`)"
          />
        </div>
      </SettingsCard>

      <SettingsCard
        :title="t('settings.portfolio.photosTitle')"
        :description="t('settings.portfolio.photosCount', { count: portfolio.photos.length, max: MAX_PORTFOLIO_PHOTOS })"
      >
        <ul class="grid gap-5 sm:grid-cols-2">
          <li v-for="(photo, index) in portfolio.photos" :key="photo.id" class="grid gap-3">
            <ImageUploader
              :label="t('settings.portfolio.photo', { number: index + 1 })"
              :src="photoUrls[index] ?? ''"
              :alt="photo.alt"
              shape="square"
              :is-busy="isBusy(`photo-${index}`)"
              @select="replacePhoto(index, $event)"
              @remove="removePhoto(index)"
            />
            <TextField
              v-model="photo.alt"
              :label="t('settings.portfolio.photoAlt', { number: index + 1 })"
              :maxlength="TEXT_LIMITS.photoAlt.max"
              :error="errorFor(`photos.${index}.alt`)"
            />
          </li>
          <li v-if="canAddPhoto">
            <ImageUploader
              :label="t('settings.portfolio.addPhoto')"
              src=""
              shape="square"
              :removable="false"
              :is-busy="isBusy('newPhoto')"
              @select="addPhoto"
            />
          </li>
        </ul>
        <p v-if="!canAddPhoto" class="text-sm text-muted" role="status">
          {{ t('settings.portfolio.photosLimit', { max: MAX_PORTFOLIO_PHOTOS }) }}
        </p>
      </SettingsCard>

      <p v-if="failedField" class="text-sm text-danger" role="alert">{{ t('settings.image.uploadFailed') }}</p>
    </div>

    <div class="lg:sticky lg:top-6">
      <PhoneFrame :theme="store.drafts.theme">
        <PortfolioPreview :portfolio="portfolio" :name="store.drafts.profile.name" :cover-url="coverUrl" :photo-urls="photoUrls" />
      </PhoneFrame>
    </div>
  </div>
</template>
