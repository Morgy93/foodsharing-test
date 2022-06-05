<template>
  <b-navbar-nav
    class="nav-row flex-row justify-content-center"
  >
    <menu-item
      v-if="!hasFsRole"
      :url="$url('upgradeToFs')"
      icon="fa-hands-helping"
      :title="$i18n('foodsaver.upgrade.to_fs')"
      :show-title="true"
    />
    <menu-region
      v-if="hasFsRole && !viewIsSM"
      :regions="regions"
    />
    <menu-groups
      v-if="hasFsRole && !viewIsSM"
      :working-groups="workingGroups"
    />
    <menu-stores
      v-if="hasFsRole"
      :may-add-store="mayAddStore"
    />
    <menu-baskets
      :show-title="!hasFsRole"
    />
    <menu-item
      v-if="!viewIsSM"
      :url="$url('map')"
      icon="fa-map-marker-alt"
      :title="$i18n('storelist.map')"
      :show-title="!hasFsRole"
    />
    <menu-messages
      v-if="viewIsSM"
    />
    <menu-envelope
      v-if="viewIsSM"
      :display-mailbox="hasFsRole"
    />
    <menu-bells
      v-if="viewIsSM"
    />
  </b-navbar-nav>
</template>

<script>
import MenuItem from '../Items/MenuItem'
import MenuRegion from '../Items/Region/MenuRegion'
import MenuStores from '../Items/Stores/MenuStores'
import MenuGroups from '../Items/Groups/MenuGroups'
import MenuBaskets from '../Items/Baskets/MenuBaskets'
import MenuMessages from '../Items/Messages/MenuMessages'
import MenuBells from '../Items/Bells/MenuBells'
import MenuEnvelope from '../Items/Contact/MenuEnvelope'

import MediaQueryMixin from '../../../utils/VueMediaQueryMixin'

export default {
  components: {
    MenuItem,
    MenuRegion,
    MenuStores,
    MenuGroups,
    MenuBaskets,
    MenuMessages,
    MenuBells,
    MenuEnvelope,
    // MenuUser,
  },
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
    mayAddStore: {
      type: Boolean,
      default: false,
    },
    avatar: {
      type: String,
      default: '',
    },
    userId: {
      type: Number,
      default: null,
    },
  },
}

</script>

<style lang="scss" scoped>
.bootstrap .navbar-nav ::v-deep .dropdown-menu {
  position: absolute;
}
</style>
