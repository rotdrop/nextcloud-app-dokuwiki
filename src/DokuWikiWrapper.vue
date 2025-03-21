<!--
 - @copyright Copyright (c) 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  <div :class="appName + '-container'">
    <div ref="loaderContainer" class="loader-container" />
    <div :class="appName + '-frame-wrapper'">
      <iframe :id="frameId"
              ref="externalFrame"
              :src="iframeLocation"
              :name="appName"
              v-bind="props.iFrameAttributes"
              @load="loadHandler"
      />
    </div>
  </div>
</template>
<script setup lang="ts">
import { appName } from './config.ts'
import {
  computed,
  onMounted,
  onUnmounted,
  onBeforeMount,
  ref,
  watch,
} from 'vue'
import {
  tuneContents,
  maximize as maximizeIFrame,
  removeEnvelope,
} from './doku-wiki.ts'
import { getInitialState } from './toolkit/services/InitialStateService.js'

interface InitialState {
  appName: typeof appName,
  authenticationRefreshInterval: number,
  wikiURL: string,
}

const props = withDefaults(defineProps<{
  wikiPage?: string,
  query?: Record<string, string>,
  iFrameAttributes?: Record<string, string>,
  compact?: boolean,
}>(), {
  wikiPage: '',
  query: () => ({}),
  iFrameAttributes: () => ({}),
  compact: false,
})

const emit = defineEmits(['iframe-loaded'])

const initialState = getInitialState() as InitialState

const requestedLocation = computed(() => {
  const queryString = (new URLSearchParams({ id: props.wikiPage, ...props.query })).toString().replace('%3A', ':')
  return initialState.wikiURL + '/doku.php?' + queryString
})
const iframeLocation = ref(requestedLocation.value)
const currentLocation = ref(requestedLocation.value)

const frameId = computed(() => appName + '-frame')

watch(() => props.wikiPage, () => {
  if (requestedLocation.value !== currentLocation.value) {
    console.info('TRIGGER IFRAME REFRESH', { request: requestedLocation.value, current: currentLocation.value })
    iframeLocation.value = requestedLocation.value
  } else {
    console.info('NOT CHANGING IFRAME SOURCE', { request: requestedLocation.value, current: currentLocation.value })
  }
})

let gotLoadEvent = false

const loadTimeout = 1000 // 1 second

let timerCount = 0

let loadTimer: undefined|ReturnType<typeof setTimeout>

const loaderContainer = ref<null | HTMLElement>(null)
const externalFrame = ref<null | HTMLIFrameElement>(null)

const loadHandler = () => {
  console.debug('DOKUWIKI: GOT LOAD EVENT')
  const iframe = externalFrame.value
  const iFrameWindow = iframe?.contentWindow
  if (!iframe || !iFrameWindow) {
    return
  }
  tuneContents(iframe)
  if (props.compact) {
    removeEnvelope(iframe)
  } else {
    maximizeIFrame(iframe)
  }
  if (!gotLoadEvent) {
    loaderContainer.value!.classList.add('fading')
  }
  gotLoadEvent = true
  console.info('IFRAME IS NOW', {
    iframe,
    location: iFrameWindow.location,
  })
  currentLocation.value = iFrameWindow.location.href
  const search = iFrameWindow.location.search
  const urlPath = iFrameWindow.location.pathname.replace(/^.*doku\.php\/?/, '')
  const query = Object.fromEntries((new URLSearchParams(search)).entries())
  const wikiPath: string[] = []
  if (query.id) {
    wikiPath.splice(0, 0, ...(query.id.split(/:/)))
  } else {
    wikiPath.splice(0, 0, ...(urlPath.split(/[:/]/)))
  }
  // no rewrite: doku.php?id=A:B:C
  // rewrite: doku.php/A:B:C
  // rewrite + useslash: doku.php/A/B/C
  //
  // In all cases the id=A:B:C is understood.
  emit('iframe-loaded', {
    wikiPath,
    urlPath,
    query,
    iFrame: iframe,
    window: iFrameWindow,
    document: iframe.contentDocument,
  })
}

const resizeHandlerWrapper = () => {
  maximizeIFrame(externalFrame.value!)
}

const loadTimerHandler = () => {
  loadTimer = undefined
  if (gotLoadEvent) {
    return
  }
  timerCount++
  const rcfContents = externalFrame.value!.contentWindow!.document
  if (rcfContents.querySelector('#layout')) {
    console.info('DOKUWIKI: LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms')
    externalFrame.value!.dispatchEvent(new Event('load'))
  } else {
    loadTimer = setTimeout(loadTimerHandler, loadTimeout)
  }
}

onBeforeMount(() => {
  iframeLocation.value = requestedLocation.value
})

let listenerInstalled = false

watch(() => props.compact, (value) => {
  if (value) {
    removeEnvelope(externalFrame.value || undefined)
    if (listenerInstalled) {
      window.removeEventListener('resize', resizeHandlerWrapper)
      listenerInstalled = false
    }
  } else {
    if (!listenerInstalled) {
      window.addEventListener('resize', resizeHandlerWrapper)
      listenerInstalled = true
    }
    // if this mutation really happens we trigger an iframe reload by
    // touching its src attribute
    const iFrame = externalFrame.value
    if (iFrame) {
      if (iframeLocation.value !== requestedLocation.value) {
        iframeLocation.value = requestedLocation.value
      } else if (iFrame.contentWindow) {
        iFrame.contentWindow.location.href = requestedLocation.value
      }
    }
  }
})

onMounted(() => {
  if (!props.compact && !listenerInstalled) {
    window.addEventListener('resize', resizeHandlerWrapper)
    listenerInstalled = true
  }
  if (!loadTimer) {
    loadTimer = setTimeout(loadTimerHandler, loadTimeout)
  }
})

onUnmounted(() => {
  if (listenerInstalled) {
    window.removeEventListener('resize', resizeHandlerWrapper)
    listenerInstalled = false
  }
})

</script>
<style scoped lang="scss">
.app-container {
  display: flex;
  flex-direction: column;
  flex-wrap: wrap;
  justify-content: center;
  align-items: stretch;
  align-content: stretch;
  &.error {
    .loader-container {
      display:none; // do not further annoy the user
    }
  }
  .loader-container {
    background-image: url('../img/loader.gif');
    background-repeat: no-repeat;
    background-position: center;
    z-index:10;
    width:100%;
    height:100%;
    position:fixed;
    transition: visibility 1s, opacity 1s;
    &.fading {
      opacity: 0;
      visibility: hidden;
    }
  }
  #errorMsg {
    align-self: center;
    padding:2em 2em;
    font-weight: bold;
    font-size:120%;
    max-width: 80%;
    border: 2px solid var(--color-border-maxcontrast);
    border-radius: var(--border-radius-pill);
    background-color: var(--color-background-dark);
  }
  iframe {
    flex-grow: 10;
  }
}
</style>
