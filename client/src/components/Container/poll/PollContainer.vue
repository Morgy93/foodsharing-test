<template>
  <Container
    v-if="data && data.length > 0"
    :tag="title"
    :title="$i18n('dashboard.polls')"
    :toggle-visiblity="data && data.length > defaultAmount"
    @show-full-list="showFullList"
    @reduce-list="reduceList"
  >
    <PollField
      v-for="(entry, key) in filteredList"
      :key="key"
      :entry="entry"
    />
  </Container>
</template>

<script>
import DataGroups from '@/stores/groups'
import DataRegions from '@/stores/regions'
import DataPolls from '@/stores/polls'

import Container from '../Container.vue'
import PollField from './PollField'

import ListToggleMixin from '@/mixins/ContainerToggleMixin'

export default {
  name: 'PollList',
  components: {
    Container,
    PollField,
  },
  mixins: [ListToggleMixin],
  data () {
    return {
      title: 'dashboard.my.polls',
    }
  },
  computed: {
    groupsAndRegions: () => DataGroups.getters.get().concat(DataRegions.getters.get()),
    data () {
      let data = DataPolls.getters.getPolls()
      if (data === null) {
        DataPolls.mutations.fetchPolls()
        data = DataPolls.getters.getPolls()
      }
      this.setList(data)
      return data
    },
  },
}
</script>
