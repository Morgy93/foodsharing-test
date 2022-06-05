<template>
  <b-navbar-nav
    id="topbar-navright"
    class="nav-row flex-md-row align-items-start align-items-md-center"
  >
    <menu-item
      v-if="viewIsSM"
      :url="$url('map')"
      icon="fa-map-marker-alt"
      :title="$i18n('storelist.map')"
    />
    <menu-admin
      v-if="hasAdminRights"
      :may="may"
    />
    <MenuBullhorn />
    <MenuInformation />
    <MenuEnvelope
      v-if="!viewIsSM"
      :display-mailbox="displayMailbox"
    />
    <menu-messages v-if="!viewIsSM" />
    <menu-bells v-if="!viewIsSM" />
    <menu-user
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
import MenuEnvelope from '../Items/Contact/MenuEnvelope'
import MenuItem from '../Items/MenuItem'
import MenuMessages from '../Items/Messages/MenuMessages'
import MenuBells from '../Items/Bells/MenuBells'
import MenuUser from '../Items/User/MenuUser'
import MenuAdmin from '../Items/Admin/MenuAdmin'

import MediaQueryMixin from '../../../utils/VueMediaQueryMixin'

export default {
  components: { MenuAdmin, MenuBullhorn, MenuInformation, MenuEnvelope, BNavbarNav, MenuItem, MenuMessages, MenuBells, MenuUser },
  directives: { VBTooltip },
  mixins: [MediaQueryMixin],
  props: {
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
  computed: {
    hasAdminRights () {
      return this.may.administrateBlog || this.may.editQuiz || this.may.handleReports || this.may.editContent || this.may.manageMailboxes || this.may.administrateNewsletterEmail || this.may.administrateRegions
    },
  },
}
</script>
