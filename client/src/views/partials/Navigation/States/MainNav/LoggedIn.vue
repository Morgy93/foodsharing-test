<template>
  <ul class="mainnav">
    <Logo v-if="viewIsMobile" />
    <Link
      v-if="!isFoodsaver"
      :title="$i18n('foodsaver.upgrade.to_fs')"
      icon="fa-hands-helping"
      :href="$url('quiz_foodsaver')"
    />
    <NavRegions v-if="isFoodsaver && !viewIsMobile" />
    <NavGroups v-if="isFoodsaver && !viewIsMobile" />
    <NavStores v-if="isFoodsaver" />
    <NavBaskets />
    <NavConversations v-if="viewIsMobile" />
    <NavNotifications v-if="viewIsMobile" />
    <b-navbar-toggle target="nav-collapse">
      <template #default="{ expanded }">
        <span
          v-if="getMailUnreadCount"
          class="badge badge-danger"
          :class="{
            'onlyNine': String(getMailUnreadCount).length === 1,
            'overNinetyNine': String(getMailUnreadCount).length > 2,
          }"
          v-html="getMailUnreadCount"
        />
        <i
          class="fas"
          :class="{
            'fa-bars': !expanded,
            'fa-times': expanded,
          }"
        />
      </template>
    </b-navbar-toggle>
  </ul>
</template>

<script>
// Store
import DataUser from '@/stores/user'
//
import Link from '@/components/Navigation/_NavItems/NavLink'
import Logo from '@/components/Navigation/Logo'
//
import NavNotifications from '@/components/Navigation/Notifications/NavNotifications'
import NavConversations from '@/components/Navigation/Conversations/NavConversations'
import NavBaskets from '@/components/Navigation/Baskets/NavBaskets'
import NavStores from '@/components/Navigation/Stores/NavStores'
import NavGroups from '@/components/Navigation/Groups/NavGroups'
import NavRegions from '@/components/Navigation/Regions/NavRegions'
// Mixins
import MediaQueryMixin from '@/mixins/MediaQueryMixin'

export default {
  components: {
    Logo,
    Link,
    NavNotifications,
    NavConversations,
    NavBaskets,
    NavStores,
    NavGroups,
    NavRegions,
  },
  mixins: [MediaQueryMixin],
  computed: {
    isFoodsaver () {
      return DataUser.getters.isFoodsaver()
    },
    hasMailBox () {
      return DataUser.getters.hasMailBox()
    },
    getMailUnreadCount () {
      return DataUser.getters.getMailUnreadCount()
    },
  },
}
</script>
