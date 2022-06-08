<template>
  <b-navbar-nav
    id="topbar-navright"
    class="nav-row flex-lg-row align-items-start align-items-lg-center"
  >
    <menu-item
      v-if="!viewIsLG"
      :url="$url('map')"
      icon="fa-map-marker-alt"
      :title="$i18n('storelist.map')"
    />
    <menu-mailbox v-if="viewIsLG && displayMailbox" />
    <menu-messages v-if="viewIsLG" />
    <menu-bells v-if="viewIsLG" />
    <MenuBullhorn />
    <MenuInformation />
    <menu-user
      :may="may"
      :user-id="userId"
      :avatar="avatar"
      :show-title="true"
    />
  </b-navbar-nav>
</template>

<script>

import { VBTooltip, BNavbarNav } from 'bootstrap-vue'
import MenuBullhorn from '../Items/Bullhorn/MenuBullhorn'
import MenuInformation from '../Items/Information/MenuInformation'
import MenuItem from '../Items/MenuItem'
import MenuMessages from '../Items/Messages/MenuMessages'
import MenuMailbox from '../Items/Mailbox/MenuMailbox'
import MenuBells from '../Items/Bells/MenuBells'
import MenuUser from '../Items/User/MenuUser'

import MediaQueryMixin from '@/mixins/MediaQueryMixin'

export default {
  components: { MenuBullhorn, MenuInformation, MenuMailbox, BNavbarNav, MenuItem, MenuMessages, MenuBells, MenuUser },
  directives: { VBTooltip },
  mixins: [MediaQueryMixin],
  props: {
    hasFsRole: {
      type: Boolean,
      default: true,
    },
    regions: {
      type: Array,
      default: () => [],
    },
    workingGroups: {
      type: Array,
      default: () => [],
    },
    displayMailbox: {
      type: Boolean,
      default: false,
    },
    userId: {
      type: Number,
      default: null,
    },
    avatar: {
      type: String,
      default: '',
    },
    may: {
      type: Object,
      default: () => ({}),
    },
  },
}
</script>
