<template>
  <b-nav-item
    v-b-tooltip.hover.bottom
    :href="url"
    :title="title"
    :aria-label="title"
    @click="$emit('click')"
  >
    <i :class="`fas ${icon}`" />
    <span
      class="headline"
      :class="{'d-inline-block': showTitle}"
    >
      {{ title }}
    </span>
  </b-nav-item>
</template>

<script>
import { VBTooltip } from 'bootstrap-vue'

export default {
  directives: { VBTooltip },
  props: {
    url: { type: String, default: undefined },
    icon: { type: String, default: undefined },
    title: { type: String, default: undefined },
    showTitle: { type: Boolean, default: false },
  },
}
</script>

<style lang="scss" scoped>
.headline {
  display: none;

  .collapse.show & {
    display: inline-block;
  }
}
</style>
