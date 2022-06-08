<template>
  <a
    class="list-group-item list-group-item-action d-flex flex-column"
    :href="$url('store', entry.id)"
    style="min-height: 65px;"
  >
    <div class="d-flex mb-auto justify-content-between align-items-center">
      <strong
        v-b-tooltip="entry.name.length > 30 ? entry.name : ''"
        class="mb-0 mr-2 d-inline-block text-truncate"
        v-html="entry.name"
      />
      <i
        v-if="entry.isManaging"
        v-b-tooltip="$i18n('store.tooltip_managing')"
        class="fas fa-users-cog text-muted"
        style="cursor: help;"
      />
    </div>
    <div
      v-if="entry.pickupStatus > 0"
      v-b-tooltip="$i18n('store.tooltip_'+['yellow', 'orange', 'red'][entry.pickupStatus - 1])"
      class="d-flex align-items-center"
    >
      <i
        class="fas fa-circle mr-1"
        :class="{
          'text-primary': entry.pickupStatus === 1,
          'text-warning': entry.pickupStatus === 2,
          'text-danger': entry.pickupStatus === 3
        }"
      />
      <small
        class="align-self-start d-inline-block text-truncate"
        v-html="$i18n('store.short_tooltip_'+['yellow', 'orange', 'red'][entry.pickupStatus - 1])"
      />
    </div>
  </a>
</template>

<script>
export default {
  props: {
    entry: { type: Object, default: () => {} },
  },
}
</script>
