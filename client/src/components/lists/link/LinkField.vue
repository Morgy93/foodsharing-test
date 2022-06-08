<template>
  <a
    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
    :href="$url(type, entry.id)"
  >
    <strong
      v-b-tooltip="entry.name.length > 35 ? entry.name : ''"
      class="mb-0 d-inline-block text-truncate"
      v-html="entry.name"
    />
  </a>
</template>

<script>
export default {
  props: {
    entry: { type: Object, default: () => {} },
    type: { type: String, default: 'home' },
  },
}
</script>
