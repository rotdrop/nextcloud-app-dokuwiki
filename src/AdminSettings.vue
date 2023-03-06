<script>
/**
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
</script>
<template>
  <SettingsSection :class="[...cloudVersionClasses, appName]" :title="t(appName, 'DokuWiki Integration')">
    <AppSettingsSection title="">
      <SettingsInputText v-model="externalLocation"
                         :label="t(appName, 'DokuWiki Installation Path')"
                         title=""
                         :hint="t(appName, 'Please enter the location of the already installed DokuWiki instance. This should either be a path, absolute or relative to the root of the web server, or a complete URL pointing to the web location of the DokuWiki. In order to make things work, your have to enable the XML-RPC protocol in your DokuWiki.')"
                         :disabled="loading > 0"
                         @update="saveTextInput(...arguments, 'externalLocation')"
      />
    </AppSettingsSection>
    <AppSettingsSection title="">
      <SettingsInputText v-model="authenticationRefreshInterval"
                         title=""
                         :label="t(appName, 'DokuWiki Session Refresh Interval [s]')"
                         :hint="t(appName, 'Please enter the desired session-refresh interval here. The interval is measured in seconds and should be somewhat smaller than the configured session life-time for the DokuWiki instance in use.')"
                         :disabled="loading > 0"
                         @update="saveTextInput(...arguments, 'authenticationRefreshInterval')"
      />
    </AppSettingsSection>
    <AppSettingsSection title="">
      <input id="enable-ssl-verify"
             v-model="enableSSLVerify"
             class="checkbox"
             type="checkbox"
             name="enableSSLVerify"
             value="1"
             :disabled="loading > 0"
             @change="saveSetting('enableSSLVerify')"
      >
      <label for="enable-ssl-verify"
             :title="t(appName, 'Disable SSL verification, e.g. for self-signed certificates or known mis-matching host-names like \'localhost\'.')"
      >
        {{ t(appName, 'Enable SSL verification.') }}
      </label>
      <p class="hint">
        {{ t(appName, 'Disable SSL verification, e.g. for self-signed certificates or known mis-matching host-names like \'localhost\'.') }}
      </p>
    </AppSettingsSection>
  </SettingsSection>
</template>
<script>
import { appName } from './config.js'
import AppSettingsSection from '@nextcloud/vue/dist/Components/AppSettingsSection'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import SettingsInputText from '@rotdrop/nextcloud-vue-components/lib/components/SettingsInputText'
import settingsSync from './toolkit/mixins/settings-sync'
import cloudVersionClasses from './toolkit/util/cloud-version-classes.js'

export default {
  name: 'AdminSettings',
  components: {
    AppSettingsSection,
    SettingsSection,
    SettingsInputText,
  },
  data() {
    return {
      loading: 0,
      cloudVersionClasses,
      externalLocation: null,
      authenticationRefreshInterval: null,
      enableSSLVerify: null,
    }
  },
  mixins: [
    settingsSync,
  ],
  computed: {
  },
  watch: {},
  created() {
    this.getData()
  },
  mounted() {
  },
  methods: {
    info() {
      console.info(...arguments)
    },
    async getData() {
      // slurp in all personal settings
      ++this.loading
      this.fetchSettings('admin').finally(() => {
        console.info('THIS', this)
        --this.loading
      })
    },
    async saveTextInput(value, settingsKey, force) {
      if (this.loading > 0) {
        // avoid ping-pong by reactivity
        console.info('SKIPPING SETTINGS-SAVE DURING LOAD', settingsKey, value)
        return
      }
      this.saveConfirmedSetting(value, 'admin', settingsKey, force);
    },
    async saveSetting(setting) {
      if (this.loading > 0) {
        // avoid ping-pong by reactivity
        console.info('SKIPPING SETTINGS-SAVE DURING LOAD', setting)
        return
      }
      this.saveSimpleSetting(setting, 'admin')
    },
  },
}
</script>
<style lang="scss" scoped>
.cloud-version {
  --cloud-icon-info: var(--icon-info-000);
  --cloud-icon-checkmark: var(--icon-checkmark-000);
  --cloud-icon-alert: var(--icon-alert-outline-000);
  --cloud-theme-filter: none;
  &.cloud-version-major-25 {
    --cloud-icon-info: var(--icon-info-dark);
    --cloud-icon-checkmark: var(--icon-checkmark-dark);
    --cloud-icon-alert: var(--icon-alert-outline-dark);
    --cloud-theme-filter: var(--background-invert-if-dark);
  }
}
.flex-container {
  display:flex;
  &.flex-column {
    flex-direction:column;
  }
  &.flex-row {
    flex-direction:row;
  }
  &.flex-center {
    align-items:center;
  }
}
.settings-section {
  :deep(.app-settings-section) {
    margin-bottom: 40px;
  }
  :deep(.settings-section__title) {
    position: relative;
    padding-left:48px;
    height:32px;
    &::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      width: 32px;
      height: 32px;
      background-size:32px;
      background-image:url('../img/app.svg');
      background-repeat:no-repeat;
      background-origin:border-box;
      background-position:left center;
      filter: var(--cloud-theme-filter);
    }
  }
}
.hint {
  color: var(--color-text-lighter);
  font-style: italic;
  max-width: 400px;
}
</style>
