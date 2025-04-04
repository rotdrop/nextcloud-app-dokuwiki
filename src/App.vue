<!--
 - @copyright Copyright (c) 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <NcContent :app-name="appId">
    <NcAppContent :class="[appId + '-content-container', { 'icon-loading': loading }]">
      <RouterView v-show="!loading && !error"
                  :loading.sync="loading"
                  @iframe-loaded="onIFrameLoaded($event)"
                  @error="onError"
      />
      <NcEmptyContent v-if="error">
        <template #name>
          <h2>{{ t(appName, 'DokuWiki Wrapper for Nextcloud') }}</h2>
        </template>
        <template #icon>
          <DynamicSvgIcon :data="appIcon" size="64" />
        </template>
        <template #description>
          <div class="error-message">
            {{ error }}
          </div>
        </template>
      </NcEmptyContent>
    </NcAppContent>
  </NcContent>
</template>
<script setup lang="ts">
import { appName as appId } from './config.ts'
import { translate as t } from '@nextcloud/l10n'
import {
  NcAppContent,
  NcContent,
  NcEmptyContent,
} from '@nextcloud/vue'
import DynamicSvgIcon from '@rotdrop/nextcloud-vue-components/lib/components/DynamicSvgIcon.vue'
import appIcon from '../img/app.svg?raw'
import {
  ref,
} from 'vue'
import {
  useRoute,
  useRouter,
} from 'vue-router/composables'
import type { Location as RouterLocation } from 'vue-router'

const loading = ref(true)
const error = ref<string|undefined>(undefined)

const router = useRouter()
const currentRoute = useRoute()

const onError = (event: { error: Error, hint: string }) => {
  console.error('DokuWiki caught error event', { event })
  error.value = event.hint
  loading.value = false
}

const onIFrameLoaded = async (event: { wikiPath: string[], query: Record<string, string> }) => {
  loading.value = false
  console.debug('GOT EVENT', { event })
  if (event.query.id) {
    delete event.query.id
  }
  const routerLocation: RouterLocation = {
    name: currentRoute.name!,
    params: {
      wikiPage: event.wikiPath.join(':'),
    },
    query: { ...event.query },
  }
  try {
    await router.push(routerLocation)
  } catch (error) {
    console.debug('NAVIGATION ABORTED', { error })
  }
}

// The initial route is not named and consequently does not load the
// wrapper component, so just replace it by the one and only named
// route.
router.onReady(async () => {
  if (!currentRoute.name) {
    const routerLocation: RouterLocation = {
      name: 'home',
      params: {},
      query: { ...currentRoute.query },
    }
    try {
      await router.replace(routerLocation)
    } catch (error) {
      console.debug('NAVIGATION ABORTED', { error })
    }
  }
})
</script>
<style scoped lang="scss">
main {
  // strange: all divs have the same height, there is no horizontal
  // scrollbar, but still FF likes to emit a vertical scrollbar.
  //
  // DO NOT ALLOW THIS!
  overflow: hidden !important;
}
.empty-content::v-deep {
  h2 ~ p {
    text-align: center;
    width: 72ex;
  }
  .hint {
    color: var(--color-text-lighter);
  }
  .empty-content__icon {
    margin-top: 16px;
  }
}
</style>
