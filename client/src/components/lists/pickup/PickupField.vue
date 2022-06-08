<template>
  <a
    class="list-group-item list-group-item-action d-flex flex-column"
    :href="$url('store', entry.store_id)"
    style="min-height: 65px;"
  >
    <div class="d-flex mb-auto justify-content-between align-items-center">
      <small
        v-b-tooltip="entry.store_name.length > 30 ? entry.store_name : ''"
        class="mb-0 mr-2 d-inline-block text-truncate"
        v-html="entry.store_name"
      />
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
    </div>
    <h5
      class="d-flex align-items-center font-weight-bold"
      :class="{
        'text-danger': getHourDifferenceToNow(entry.timestamp * 1000) < 24,
        'text-black-50': !getHourDifferenceToNow(entry.timestamp * 1000) > 24
      }"
    >
      <i class="fas fa-clock mr-2" />
      <span
        v-if="getHourDifferenceToNow(entry.timestamp * 1000) < 24"
        v-b-tooltip="dateFormat(entry.timestamp * 1000)"
        v-html="dateDistanceInWords(entry.timestamp * 1000)"
      />
      <span
        v-else
        v-html="dateFormat(entry.timestamp * 1000, 'full-short')"
      />
    </h5>
  </a>
</template>

<script>
import DateFormatterMixin from '@/mixins/DateFormatterMixin'

export default {
  mixins: [DateFormatterMixin],
  props: {
    entry: { type: Object, default: () => {} },
  },
}
</script>
