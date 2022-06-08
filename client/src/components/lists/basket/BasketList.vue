<template>
  <List
    v-if="list.length > 0"
    :tag="title"
    :title="$i18n(title)"
    :hide="defaultAmount > list.length"
    @show-full-list="showFullList"
    @reduce-list="reduceList"
  >
    <BasketField
      v-for="(entry, key) in filteredList"
      :key="key"
      :entry="entry"
    />
  </List>
</template>

<script>
import ListToggleMixin from '@/mixins/ListToggleMixin'

import List from '../_List.vue'
import BasketField from './BasketField'

export default {
  components: {
    List,
    BasketField,
  },
  mixins: [ListToggleMixin],
  props: {
    title: { type: String, default: 'basket.nearby' },
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
