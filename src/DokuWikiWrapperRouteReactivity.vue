<!--
 - @copyright Copyright (c) 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <DokuWikiWrapper v-bind="$attrs"
                   :wiki-page="routeWikiPage"
                   :query="routeQuery"
                   v-on="$listeners"
  />
</template>
<script setup lang="ts">
import DokuWikiWrapper from './DokuWikiWrapper.vue'
import {
  onBeforeMount,
  ref,
} from 'vue'
import {
  onBeforeRouteUpdate,
  useRoute,
} from 'vue-router'
import type {
  RouteLocationNormalizedGeneric,
  RouteLocationNormalizedLoadedGeneric,
} from 'vue-router'
import logger from './logger.ts'

const currentRoute = useRoute()

const routeWikiPage = ref<string>('')
const routeQuery = ref<Route['query']>({})

const onRouteChange = (to: RouteLocationNormalizedGeneric) => {
  routeWikiPage.value = to.params.wikiPage ?? ''
  routeQuery.value = Object.fromEntries(Object.entries(to.query || {}).filter(([key]) => key !== 'id'))
}

onBeforeMount(() => {
  logger.debug('ON BEFORE MOUNT', { ...currentRoute }, { ...window?.history?.state })
  onRouteChange(currentRoute)
})

onBeforeRouteUpdate((to, from, next) => {
  logger.debug('ON BEFORE ROUTE UPDATE', {
    to: { ...to },
    from: { ...from },
    windowState: { ...(window?.history?.state || {}) },
  })
  onRouteChange(to)
  next()
})

</script>
