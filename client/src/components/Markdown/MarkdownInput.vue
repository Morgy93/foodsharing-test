<template>
  <div
    class="md-input"
    :class="{'conceal-toolbar': actuallyConcealToolbar }"
  >
    <b-button-toolbar>
      <b-button-group
        size="sm"
        class="md-button-toolbar"
      >
        <b-button
          v-for="(button, i) in buttons"
          :key="i"
          v-b-tooltip.hover="$i18n(`markdown_input.tooltip.${button.tooltip}`)"
          :variant="variant"
          :disabled="isPreview"
          @click="button.action"
        >
          <i :class="`fas fa-${button.icon}`" />
        </b-button>
        <b-button
          v-b-tooltip.hover="$i18n(`markdown_input.tooltip.preview`)"
          :variant="variant"
          :pressed.sync="isPreview"
        >
          <i class="fas fa-eye" />
        </b-button>
      </b-button-group>
    </b-button-toolbar>
    <div class="input-content">
      <b-form-textarea
        v-if="!isPreview"
        ref="input"
        v-model="modelValue"
        class="md-text-area"
        :rows="rows"
        :max-rows="maxRows"
        :state="state"
        :placeholder="placeholder"
        :disabled="disabled"
        @keydown.ctrl.enter="$emit('submit')"
        @keydown.enter="onEnter"
      />
      <Markdown
        v-if="isPreview"
        :source="modelValue || $i18n('markdown_input.empty_preview_placeholder')"
      />
    </div>
  </div>
</template>

<script>
import Markdown from './Markdown.vue'
import RouteAndDeviceCheckMixin from '@/mixins/RouteAndDeviceCheckMixin'

function getMaxRowsForScreenSize () {
  const minimumMaxRows = 8
  const relativeScreenUsage = 0.7
  const lineHeight = 24
  return Math.max(minimumMaxRows, Math.floor((window.innerHeight * relativeScreenUsage) / lineHeight))
}

export default {
  components: { Markdown },
  mixins: [RouteAndDeviceCheckMixin],
  props: {
    rows: {
      type: Number,
      default: 4,
    },
    maxRows: {
      type: Number,
      default: getMaxRowsForScreenSize(),
    },
    value: {
      type: String,
      default: '',
    },
    state: {
      type: Boolean,
      default: null,
    },
    variant: {
      type: String,
      default: 'secondary',
    },
    placeholder: {
      type: String,
      default: '',
    },
    concealToolbar: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  data () {
    return {
      isPreview: false,
      buttons: [
        { tooltip: 'bold', icon: 'bold', action: this.bold },
        { tooltip: 'italic', icon: 'italic', action: this.italic },
        { tooltip: 'strikethrough', icon: 'strikethrough', action: this.strikethrough },
        { tooltip: 'heading', icon: 'heading', action: this.heading },
        { tooltip: 'link', icon: 'link', action: this.link },
        { tooltip: 'code', icon: 'code', action: this.code },
        { tooltip: 'quote', icon: 'quote-right', action: this.quote },
        { tooltip: 'unorderedList', icon: 'list', action: this.unorderedList },
        { tooltip: 'orderedList', icon: 'list-ol', action: this.orderedList },
        { tooltip: 'hrule', icon: 'minus', action: this.hrule },
        // Additional buttons that could be added in the future:
        // { icon: 'at'}, // supposed to be a way to insert links to user profiles easily
        // { icon: 'table'}, // for adding md tables
      ],
    }
  },
  computed: {
    modelValue: {
      get () {
        return this.value
      },
      set (modelValue) {
        this.$emit('update:value', modelValue)
      },
    },
    actuallyConcealToolbar () {
      // Disable concealed toolbars for Safari, because focusing buttons on click doesn't work like everywhere else.
      // See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#clicking_and_focus
      if (this.isSafari) return false
      if (this.disabled) return true
      if (this.isPreview) return false
      if (this.modelValue.length > 0) return false
      return this.concealToolbar
    },
  },
  methods: {
    getBaseTextArea () {
      return this.$refs.input?.$refs?.input
    },
    bold () {
      this.wrapSelection('**')
    },
    italic () {
      this.wrapSelection('*')
    },
    strikethrough () {
      this.wrapSelection('~~')
    },
    heading () {
      const [start, end] = this.getSelection()
      const lineStart = this.getLineStart(start)
      let lineStarter = '#'
      const fromCurrentLine = this.modelValue.substring(lineStart)
      if (!/^#* /.test(fromCurrentLine)) {
        lineStarter += ' '
      } else if (/^#{6} /.test(fromCurrentLine)) {
        lineStarter = ''
      }
      this.modelValue = this.modelValue.substring(0, lineStart) + lineStarter + this.modelValue.substring(lineStart)
      this.setFocus(start + lineStarter.length, end + lineStarter.length)
    },
    link () {
      this.wrapSelection('[', '](https://)')
    },
    code () {
      const [start, end] = this.getSelection()
      const selected = this.modelValue.substring(start, end)
      if (selected.includes('\n')) {
        const breakIf = (condition) => condition ? '\n' : ''
        const wrapStart = breakIf(selected.startsWith('\n')) + '```' + breakIf(this.modelValue.substring(0, start).endsWith('\n'))
        const wrapEnd = breakIf(this.modelValue.substring(end).startsWith('\n')) + '```' + breakIf(selected.endsWith('\n'))
        this.wrapSelection(wrapStart, wrapEnd)
      } else {
        this.wrapSelection('`')
      }
    },
    quote () {
      this.prefixLines('\n', '\n> ', true)
    },
    unorderedList () {
      this.prefixLines(/\n(?!- )/g, '\n- ')
    },
    orderedList () {
      this.prefixLines(/\n(?!\d\. )/g, '\n1. ')
    },
    hrule () {
      this.wrapSelection('\n\n---\n\n', '')
    },
    prefixLines (pattern, replacement, includeEmptyLine = false) {
      let [start, end] = this.getSelection()
      let selected = this.modelValue.substring(start, end)
      start += Number(selected.startsWith('\n'))
      end -= Number(selected.endsWith('\n'))
      start = this.getLineStart(start)
      const restOfLineLength = this.modelValue.substring(end).indexOf('\n')
      if (restOfLineLength !== -1) {
        end += restOfLineLength
      } else {
        end = this.modelValue.length
      }
      selected = this.modelValue.substring(start, end)
      selected = ('\n' + selected).replaceAll(pattern, replacement).substring(1)
      if (includeEmptyLine) {
        selected += (this.modelValue.substring(end).startsWith('\n\n') ? '' : '\n')
      }
      this.modelValue = this.modelValue.substring(0, start) + selected + this.modelValue.substring(end)
      end = start + selected.length
      this.setFocus(end, end)
    },
    getLineStart (position) {
      return this.modelValue.substring(0, position).lastIndexOf('\n') + 1
    },
    wrapSelection (wrapper, secondWrapper = wrapper) {
      const [start, end] = this.getSelection()
      this.modelValue = this.modelValue.substring(0, start) + wrapper + this.modelValue.substring(start, end) + secondWrapper + this.modelValue.substring(end)
      this.setFocus(start + wrapper.length, end + wrapper.length)
    },
    getSelection () {
      return [this.getBaseTextArea().selectionStart, this.getBaseTextArea().selectionEnd]
    },
    async setFocus (start = 0, end = 0) {
      this.$refs.input.focus()
      await new Promise(resolve => window.requestAnimationFrame(resolve))
      this.getBaseTextArea().selectionEnd = end
      this.getBaseTextArea().selectionStart = start
    },
    onEnter (evt) {
      const [start, end] = this.getSelection()
      if (start !== end) return
      const fromLine = this.modelValue.substring(this.getLineStart())
      const prefix = fromLine.trim().match(/^[\t ]*(?:(?:>|-|(?:1\.))[\t ]+)*/)[0]
      if (prefix) {
        this.wrapSelection('\n' + prefix, '')
        evt.preventDefault()
      }
    },
  },
}
</script>

<style lang="scss" scoped>

.md-input {
  &:focus-within{
    box-shadow: 0 0 0 0.2rem rgba(83, 58, 32, 0.25);
    border-radius: .2rem .2rem var(--border-radius) var(--border-radius);
  }
  &:not(.conceal-toolbar:not(:focus-within)) .input-content {
      border-top-left-radius: 0;
      border-top-right-radius: 0;
      border-top: 0;
  }
  &.conceal-toolbar:not(:focus-within) .md-button-toolbar {
    display: none;
  }
}
.md-button-toolbar{
    flex-grow: 1;
    margin-right: 0;
    .btn {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
      padding-right: 0;
      padding-left: 0;
    }
  }

.md-text-area{
  border: 0;
  &:focus{
    box-shadow: none;
  }
}
.input-content {
  border: 1px solid var(--fs-border-default);
  border-radius: var(--border-radius);
  &:focus-within {
    border-color: #af7a43;
  }
  .markdown {
    padding: .5rem;
  }
}

</style>
