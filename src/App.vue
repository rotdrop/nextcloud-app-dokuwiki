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
  <NcContent :app-name="appName">
    <NcAppContent :class="{ 'icon-loading': loading }">
      <RouterView v-show="!loading"
                  :loading.sync="loading"
                  @iframe-loaded="onIFrameLoaded($event)"
      />
    </NcAppContent>
  </NcContent>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import {
  NcAppContent,
  NcContent,
} from '@nextcloud/vue'
import {
  ref,
} from 'vue'
import {
  useRoute,
  useRouter,
} from 'vue-router/composables'
import type { Location as RouterLocation } from 'vue-router'

const loading = ref(true)

const router = useRouter()
const currentRoute = useRoute()

const onIFrameLoaded = async (event: { wikiPath: string[], query: Record<string, string> }) => {
  loading.value = false
  console.info('GOT EVENT', { event })
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
    console.info('NAVIGATION ABORTED', { error })
  }
}

</script>
<style scoped lang="scss">
</style>
