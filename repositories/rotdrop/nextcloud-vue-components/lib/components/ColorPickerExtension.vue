<!--
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 - @copyright 2022, 2024, 2025 Claus-Justus Heine
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
  <div class="color-picker-container flex-container flex-center">
    <NcActions>
      <NcActionButton icon="icon-play"
                      @click="pickerVisible = true"
      >
        {{ componentLabels.openColorPicker }}
      </NcActionButton>
      <NcActionButton icon="icon-confirm"
                      @click="submitColorChoice"
      >
        {{ componentLabels.submitColorChoice }}
      </NcActionButton>
      <NcActionButton icon="icon-history"
                      :disabled="savedState.rgbColor === rgbColor"
                      @click="rgbColor = savedState.rgbColor"
      >
        {{ componentLabels.revertColor }}
      </NcActionButton>
      <NcActionButton icon="icon-toggle-background"
                      :disabled="!colorPaletteHasChanged"
                      @click="revertColorPalette"
      >
        {{ componentLabels.revertColorPalette }}
      </NcActionButton>
      <NcActionButton icon="icon-toggle-background"
                      :disabled="colorPaletteIsDefault"
                      @click="resetColorPalette"
      >
        {{ componentLabels.resetColorPalette }}
      </NcActionButton>
    </NcActions>
    <NcColorPicker ref="colorPicker"
                   v-model="rgbColor"
                   :palette="colorPickerPalette"
                   :shown.sync="pickerVisible"
                   @submit="submitCustomColor"
                   @update:open="handleOpen"
                   @close="() => false"
    >
      <NcButton :style="cssVariables"
                type="primary"
                class="trigger-button"
      >
        {{ label }}
      </NcButton>
    </NcColorPicker>
    <input type="submit"
           class="icon-confirm confirm-button"
           value=""
           @click="$emit('update', rgbColor)"
    >
  </div>
</template>
<script lang="ts">
import { appName } from '../config.ts'
import {
  NcActions,
  NcActionButton,
  NcButton,
  NcColorPicker,
} from '@nextcloud/vue'
import { nextTick, set as vueSet } from 'vue'
import type { PropType } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import type { Color as RGBColorType } from '@nextcloud/vue'

type NcColorPickerType = typeof NcColorPicker

const isRGBColor = (arg: any): arg is RGBColorType => !!arg && !Array.isArray(arg) && typeof arg === 'object' && (arg.r || arg.g || arg.b || arg.color) !== undefined

export type {
  RGBColorType,
}

export default {
  name: 'ColorPickerExtension',
  components: {
    NcActionButton,
    NcActions,
    NcButton,
    NcColorPicker,
  },
  inheritAttrs: false,
  props: {
    value: {
      type: Object as PropType<RGBColorType>,
      default: undefined,
    },
    label: {
      type: String,
      default: t(appName, 'pick a color'),
    },
    componentLabels: {
      type: Object,
      default: () => {
        return {
          openColorPicker: t(appName, 'open'),
          submitColorChoice: t(appName, 'submit'),
          revertColor: t(appName, 'revert color'),
          revertColorPalette: t(appName, 'restore palette'),
          resetColorPalette: t(appName, 'factory reset palette'),
        }
      },
    },
    colorPalette: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      pickerVisible: false,
      factoryColorPalette: undefined as undefined|RGBColorType[],
      colorPickerPalette: undefined as undefined|RGBColorType[],
      savedState: {
        rgbColor: undefined as undefined|RGBColorType,
        colorPickerPalette: undefined as undefined|RGBColorType[],
      },
      loading: true,
      id: undefined as undefined|number,
      colorValue: undefined as undefined|RGBColorType,
    }
  },
  computed: {
    cssVariables() {
      return {
        '--button-background-color': this.rgbColor.color,
        '--button-foreground-color': this.rgbToGrayScale(this.rgbColor) > 0.5 ? 'black' : 'white',
      }
    },
    colorPaletteIsDefault() {
      return this.loading || ('' + this.colorPickerPalette) === ('' + this.factoryColorPalette)
    },
    colorPaletteHasChanged() {
      return !this.loading && ('' + this.colorPickerPalette) !== ('' + this.savedState.colorPickerPalette)
    },
    /**
     * Writable computable property which updates this.value through
     * sending an event to the parent.
     */
    rgbColor: {
      set(newValue: RGBColorType|string|number[]|undefined) {
        if (this.loading) {
          return
        }
        if (newValue === undefined || isRGBColor(newValue)) {
          this.colorValue = newValue
        } else {
          let r: number, g: number, b: number
          const name = t(appName, 'Custom Color')
          if (Array.isArray(newValue)) {
            r = newValue[0]
            g = newValue[1]
            b = newValue[2]
          } else {
            const colorString = (newValue.startsWith('#') ? newValue.substring(1) : newValue) + '000000'
            r = parseInt(colorString.substring(0, 2), 16)
            g = parseInt(colorString.substring(2, 4), 16)
            b = parseInt(colorString.substring(4, 6), 16)
          }
          const ctor = this.factoryColorPalette![0].constructor
          this.colorValue = new ctor(r, g, b, name)
        }
        this.$emit('update:value', this.colorValue)
        this.$emit('input', this.colorValue)
      },
      get() {
        return this.value
      },
    },
    wrappedComponent() {
      return this.$refs!.colorPicker as NcColorPickerType
    },
  },
  watch: {
    colorPickerPalette: {
      handler(newValue, oldValue) {
        this.info('PALETTE', newValue, oldValue)
        if (this.loading) {
          return
        }
        this.colorPaletteHasChanged = true
        this.$emit('update:color-palette', newValue)
      },
      deep: true,
    },
    colorPalette(newValue, oldValue) {
      if (this.loading) {
        return
      }
      if (!!newValue && !!oldValue && newValue.toString() === oldValue.toString()) {
        return
      }
      if (newValue && Array.isArray(newValue) && this.colorPickerPalette) {
        this.colorPickerPalette.splice(0, Infinity, ...newValue)
      }
    },
  },
  created() {
    // console.info('VALUE', this.value, this.rgbColor, this.oldRgbColor)
    // console.info('LOADING IN CREATED', this.loading)
    this.id = this._uid
  },
  mounted() {
    // This seemingly stupid construct of having
    // this.colorPickerPalette === undefined at start enables us to peek
    // the default palette from the NC color picker widget.
    this.factoryColorPalette = [...this.wrappedComponent.palette]
    this.info('FACTORY PALETTE', this.factoryColorPalette)
    vueSet(
      this,
      'colorPickerPalette',
      (this.colorPalette && Array.isArray(this.colorPalette) && this.colorPalette.length > 0)
        ? [...this.colorPalette]
        : [...this.factoryColorPalette],
    )
    this.info('PALETTE IS NOW', this.colorPickerPalette, this.colorPalette, this.factoryColorPalette)
    if (this.rgbColor) {
      this.prependColorToPalette(this.rgbColor)
    }
    this.saveState()
    nextTick(() => {
      this.loading = false
    })
  },
  methods: {
    info(...args: any[]) {
      console.info(this.$options.name, ...args)
    },
    submitCustomColor(color: RGBColorType) {
      this.prependColorToPalette(color)
    },
    submitColorChoice() {
      this.pickerVisible = false
      this.savedState.rgbColor = this.rgbColor
    },
    handleOpen() {
    },
    revertColorPalette() {
      this.colorPickerPalette!.splice(0, Infinity, ...this.savedState.colorPickerPalette!)
    },
    resetColorPalette() {
      this.colorPickerPalette!.splice(0, Infinity, ...this.factoryColorPalette!)
    },
    prependColorToPalette(rgbColor: RGBColorType, destinationStorage?: any) {
      if (destinationStorage === undefined) {
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        destinationStorage = this
      }
      const rgb = rgbColor.color
      if ((destinationStorage.colorPickerPalette as RGBColorType[]).findIndex(rgbColor => rgbColor.color === rgb) >= 0) {
        const palette = [...destinationStorage.colorPickerPalette]
        palette.pop()
        palette.splice(0, 0, rgbColor)
        vueSet(destinationStorage, 'colorPickerPalette', palette)
      }
    },
    /**
     * Convert an RGH color to a grey-scale value. This is used to
     * switch the trigger-button color between black and white,
     * depending on the grey-value of the color.
     *
     * @param color RGB color
     *
     * @return Grey-value corresponding to rgb.
     */
    rgbToGrayScale(color: RGBColorType) {
      // const r = Number('0x' + rgb.substring(1, 3))
      // const g = Number('0x' + rgb.substring(3, 5))
      // const b = Number('0x' + rgb.substring(5, 7))
      return (0.3 * color.r + 0.59 * color.g + 0.11 * color.b) / 255.0
    },
    saveState() {
      this.savedState.rgbColor = this.rgbColor
      this.savedState.colorPickerPalette = [...this.colorPickerPalette!]
      this.prependColorToPalette(this.rgbColor, this.savedState)
    },
  },
}
</script>
<style scoped lang="scss">
.color-picker-container {
  .trigger-button {
    background-color: var(--button-background-color);
    color: var(--button-foreground-color);
    margin-right:0;
    border-top-right-radius:0;
    border-bottom-right-radius:0;
    &:not(:focus,:hover) {
      border-right:0;
    }
  }
  .confirm-button {
    min-height: 44px; // in order to match NcButton
    border-top-left-radius:0;
    border-bottom-left-radius:0;
    border: 2px solid var(--color-border-dark);
    &:hover:not(:disabled) {
      border: 2px solid var(--color-primary-element);
    }
    &:not(:focus,:hover) {
      border-left:2px solid var(--color-background-dark);
    }
  }
}
</style>
