<template>
  <section class="container my-3 my-sm-5">
    <div class="mb-1 mb-sm-5">
      <Information
        v-if="broadcast.body.length > 0"
        type="broadcast"
        tag="broadcast"
        :title="broadcast.title"
        :description="broadcast.body"
        :time="broadcast.last_mod"
        :is-time-based="true"
        :is-closeable="true"
      />
      <Informations
        v-if="errors"
        :all-visible="true"
        :list="errors"
      />
      <Intro :user="user" />
      <Informations
        v-if="informations"
        :list="informations"
      />
      <Information
        v-if="quiz"
        tag="foodsaver.upgrade.ad_fs"
        :title="$i18n('foodsaver.upgrade.ad_fs')"
        :description="quiz.body"
        :is-closeable="quiz.closeable"
        :links="quiz.links"
      />
    </div>
    <div class="content row">
      <div
        class="col order-2 order-xl-1 mb-3"
        :class="{
          'col-md-6': !isFoodsaver(),
          'col-md-6 col-xl-3': isFoodsaver(),
        }"
      >
        <BasketList
          v-if="baskets.nearby.length > 0"
          title="basket.nearby"
          :list="baskets.nearby"
        />
        <BasketList
          v-else
          title="basket.recent"
          :list="baskets.recent"
        />
        <LinkList
          title="dashboard.my.groups"
          type="forum"
          :list="groups"
        />
        <LinkList
          title="dashboard.my.regions"
          type="forum"
          :list="regions"
        />
      </div>

      <div
        class="col order-3 order-xl-2"
        :class="{
          'col-md-6': !isFoodsaver(),
          'col-md-12 col-xl-6 ': isFoodsaver(),
        }"
      >
        <EventList
          title="dashboard.invitation"
          :list="events.invites"
          options
        />
        <EventList
          title="dashboard.event"
          :list="events.accepted"
        />
        <Activity :is-foodsaver="isFoodsaver()" />
      </div>
      <div
        v-if="isFoodsaver() && (pickups || stores)"
        class="col col-md-6 col-xl-3 order-1 order-xl-3 mb-3"
      >
        <PickupList :list="pickups" />
        <StoreList :list="stores" />
      </div>
    </div>
  </section>
</template>

<script>
import Intro from '@/components/intro/IntroField.vue'
import Information from '@/components/information/InformationField.vue'
import Informations from '@/components/information/InformationWrapper.vue'
import Activity from '@/components/lists/activity/ActivityOverview.vue'
import StoreList from '@/components/lists/store/StoreList.vue'
import PickupList from '@/components/lists/pickup/PickupList.vue'
import EventList from '@/components/lists/event/EventList.vue'
import BasketList from '@/components/lists/basket/BasketList.vue'
import LinkList from '@/components/lists/link/LinkList.vue'

import MediaQueryMixin from '@/mixins/MediaQueryMixin'

export default {
  components: {
    Intro,
    Information,
    Informations,
    Activity,
    StoreList,
    PickupList,
    EventList,
    BasketList,
    LinkList,
  },
  mixins: [MediaQueryMixin],
  props: {
    release: { type: Object, default: () => {} },
    broadcast: { type: Object, default: () => {} },
    user: { type: Object, default: () => {} },
    quiz: { type: Object, default: () => {} },
    errors: { type: Array, default: () => [] },
    informations: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    pickups: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    events: { type: Object, default: () => ({ accepted: [], invites: [] }) },
    baskets: { type: Object, default: () => ({ recent: [], nearby: [] }) },
  },
  methods: {
    isFoodsaver () {
      return this.user.rolle !== 0
    },
  },
}
</script>
