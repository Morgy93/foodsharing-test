<template>
  <b-dropdown
    :variant="variant"
    split
    right
    class="phone-button"
    :split-href="$url('phone_number', phoneNumber, true)"
  >
    <template #button-content>
      <i class="fas fa-phone" />
    </template>
    <b-dropdown-item
      :href="$url('phone_number', phoneNumber, true)"
    >
      <i class="fas fa-phone" />
      {{ $i18n('pickup.call') }}
    </b-dropdown-item>
    <b-dropdown-item
      @click.prevent="copyIntoClipboard(phoneNumber)"
    >
      <i class="fas fa-clone" />
      {{ $i18n('pickup.copyNumber') }}
    </b-dropdown-item>
  </b-dropdown>
</template>

<script>
import { pulseSuccess } from '@/script'

export default {
  props: {
    phoneNumber: {
      type: String,
      required: true,
    },
    variant: {
      type: String,
      default: 'primary',
    },
  },
  computed: {
    isClipboardAvailable () {
      return navigator.clipboard
    },
  },
  methods: {
    async copyIntoClipboard (text) {
      if (this.isClipboardAvailable) {
        await navigator.clipboard.writeText(text)
        pulseSuccess(this.$i18n('pickup.copiedNumber', { number: text }))
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.phone-button ::v-deep{
   .btn:not(.dropdown-toggle-split) {
    padding-right: 3px;
  }
  .dropdown-toggle-split {
    padding-left: 3px;
  }
}
</style>
