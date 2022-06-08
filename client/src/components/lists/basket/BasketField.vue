<template>
  <a
    class="list-group-item list-group-item-action d-flex align-items-center"
    :href="$url('basket', entry.id)"
  >
    <div class="img-thumbnail mr-2">
      <img
        :alt="$i18n('basket.by', {name: entry.fs_name})"
        :src="getImageUrl(entry.picture)"
        width="30"
        height="30"
      >
    </div>
    <div class="flex flex-column truncated">
      <div class="d-flex justify-content-between align-items-center">
        <strong
          v-b-tooltip="entry.description.length > 30 ? entry.description : ''"
          class="mr-2 text-truncate"
          v-html="entry.description"
        />
        <span
          v-if="distanceNumber(entry.distance) > 0"
          class="badge list-group-item-dark badge-pill"
        >
          <i class="fas fa-directions" />
          {{ distanceString(entry.distance) }}
        </span>
      </div>
      <small
        v-b-tooltip.hover.html="$i18n('basket.expires', {date: dateFormat(new Date(entry.until), 'full-short')})"
        v-html="$i18n('basket.by', {name: entry.fs_name})"
      />
    </div>
  </a>
</template>

<script>
import DateFormatterMixin from '@/mixins/DateFormatterMixin'

export default {
  mixins: [DateFormatterMixin],
  props: {
    entry: { type: Object, default: () => {} },
  },
  methods: {
    distanceNumber (num) {
      return Math.round(num * 10) / 10
    },
    distanceString (num) {
      num = this.distanceNumber(num)
      if (num < 1) {
        return `${(num * 1000).toLocaleString()} m`
      } else {
        return `${(num).toFixed(2).toLocaleString()} km`
      }
    },
    getImageUrl (picture) {
      if (picture) {
        return `/images/basket/thumb-${picture}`
      } else {
        return '/img/basket.png'
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.truncated {
  flex: 1;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
