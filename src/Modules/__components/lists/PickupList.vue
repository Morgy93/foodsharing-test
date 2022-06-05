<template>
  <List
    v-if="list.length > 0"
    :tag="title"
    :title="$i18n(title)"
    :hide="defaultAmount > list.length - 1"
    @show-full-list="showFullList"
    @reduce-list="reduceList"
  >
    <a
      v-for="(entry, key) in filteredList"
      :key="key"
      class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
      :href="$url('store', entry.store_id)"
    >
      <div
        class="flex-grow-1"
        :class="{
          'text-danger': getPickupIsSoon(entry.timestamp),
          'text-black-50': !getPickupIsSoon(entry.timestamp)
        }"
      >
        <small
          v-b-tooltip="`${entry.store_name}, ${entry.address}`"
          class="d-inline-block text-truncate"
          style="max-width: 200px;"
          v-html="entry.store_name"
        />
        <p
          class="font-weight-bold mb-0"
        >
          <i
            class="fas fa-clock"
          />
          <span
            v-if="getPickupIsSoon(entry.timestamp)"
            v-b-tooltip="getStringTime(entry.timestamp)"
            v-html="getRelativeTime(entry.timestamp)"
          />
          <span
            v-else
            v-html="getStringTime(entry.timestamp)"
          />
        </p>
      </div>
      <span
        v-b-tooltip="!entry.confirmed ? $i18n('pickup.to_be_confirmed') : ''"
        class="badge badge-pill d-flex p-1 align-items-center"
        :class="{
          'badge-danger': !entry.confirmed,
          'badge-success': entry.confirmed,
        }"
      >
        <i
          class="fas"
          :class="{
            'fa-check-circle': entry.confirmed,
            'fa-clock': !entry.confirmed,
          }"
        />
        <span
          v-if="entry.max_fetchers > 1"
          class="ml-1 mr-1"
        >
          {{ entry.slot_confimations.split(",").filter(e=>e==='1').length }} / {{ entry.max_fetchers }}
        </span>
      </span>
    </a>
  </List>
</template>

<script>
import List from './_List.vue'
export default {
  components: {
    List,
  },
  props: {
    title: { type: String, default: 'dashboard.pickupdates' },
    list: { type: Array, default: () => [] },
  },
  data () {
    const defaultAmount = 3
    return {
      defaultAmount: defaultAmount,
      amount: defaultAmount,
    }
  },
  computed: {
    filteredList () {
      return this.list.slice(0, this.amount)
    },
  },
  methods: {
    showFullList () {
      this.amount = this.list.length
    },
    reduceList () {
      this.amount = this.defaultAmount
    },
    getStringTime (time) {
      const opt = {
        weekday: 'short',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
      }
      return new Date(time * 1000).toLocaleString('de-DE', opt)
    },
    getRelativeTime (time) {
      const difference = -Math.floor(Date.now() / 1000 - time)
      const formatter = new Intl.RelativeTimeFormat('de')
      if (difference < 3600) {
        return formatter.format(Math.floor(difference / 60), 'minutes')
      } else if (difference < 86400) {
        return formatter.format(Math.floor(difference / 3600), 'hours')
      } else if (difference < 2620800) {
        return formatter.format(Math.floor(difference / 86400), 'days')
      } else {
        return time
      }
    },
    getPickupIsSoon (time) {
      const difference = -Math.floor(Date.now() / 1000 - time)
      return difference < 86400
    },
  },
}
</script>

<style lang="scss" scoped>
.list-group.pickup-list {
  margin-bottom: 1rem;
}

.list-group-item.showMore {
  padding: 0.5rem;
}

.list-group-item-action {
  cursor: pointer;
}
</style>
