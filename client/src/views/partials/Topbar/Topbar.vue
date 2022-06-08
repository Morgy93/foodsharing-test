<template>
  <div
    id="topbar"
    class="bootstrap"
  >
    <b-navbar
      fixed="top"
      toggleable="lg"
      class="navbar-expand-lg"
      type="custom"
    >
      <b-container>
        <b-navbar-brand class="text-center">
          <Logo :link-url="loggedIn ? $url('dashboard') : $url('home')" />
        </b-navbar-brand>

        <!-- When not logged in -->
        <b-navbar-nav
          v-if="!loggedIn"
          class="nav-row flex-row justify-content-lg-end"
        >
          <menu-item
            :url="$url('joininfo')"
            icon="fa-hands-helping"
            :title="$i18n('register.topbar')"
            :show-title="true"
          />
          <menu-item
            id="login"
            :url="$url('login')+'&ref='+encodeURIComponent(loginReferrer)"
            icon="fa-sign-in-alt"
            :title="$i18n('login.topbar')"
            :show-title="true"
          />
        </b-navbar-nav>

        <!-- When logged in -->
        <logged-in-fixed-nav
          v-if="loggedIn"
          :has-fs-role="hasFsRole"
          :regions="regions"
          :display-mailbox="mailbox"
          :working-groups="workingGroups"
          :may-add-store="may.addStore"
          :avatar="avatar"
          :user-id="userId"
          @open-search="searchOpen = !searchOpen"
        />

        <b-navbar-toggle
          target="nav-collapse"
          @click.stop="toggleState();toggleOverflow()"
        >
          <i
            class="fa fa-bars"
            :class="{ 'fa-bars': state,'fa-times': state }"
          />
        </b-navbar-toggle>

        <b-collapse
          id="nav-collapse"
          is-nav
          class="justify-content-end mb-3 mb-sm-0"
        >
          <search
            v-if="hasFsRole"
            class="my-3 my-md-0"
          />
          <menu-loggedout v-if="!loggedIn" />
          <menu-loggedin
            v-if="loggedIn"
            :has-fs-role="hasFsRole"
            :regions="regions"
            :working-groups="workingGroups"
            :display-mailbox="mailbox"
            :user-id="userId"
            :avatar="avatar"
            :may="may"
          />
        </b-collapse>
      </b-container>
    </b-navbar>
  </div>
</template>

<script>
import { BNavbarBrand, BNavbarToggle, BCollapse } from 'bootstrap-vue'
import Logo from '@/components/Topbar/Items/Logo'
import MenuLoggedout from '@/components/Topbar/States/NavLoggedout'
import MenuLoggedin from '@/components/Topbar/States/NavLoggedin'
import MenuItem from '@/components/Topbar/Items/MenuItem'
import LoggedInFixedNav from '@/components/Topbar/States/NavFixed'
import Search from '@/components/Topbar/Items/Search/Search'

import MediaQueryMixin from '@/mixins/MediaQueryMixin'
import StateTogglerMixin from '@/mixins/StateTogglerMixin'

export default {
  components: {
    BCollapse,
    BNavbarToggle,
    BNavbarBrand,
    MenuLoggedout,
    Logo,
    MenuItem,
    LoggedInFixedNav,
    MenuLoggedin,
    Search,
  },
  mixins: [MediaQueryMixin, StateTogglerMixin],
  props: {
    userId: {
      type: Number,
      default: null,
    },
    loggedIn: {
      type: Boolean,
      default: true,
    },
    avatar: {
      type: String,
      default: '',
    },
    mailbox: {
      type: Boolean,
      default: true,
    },
    hasFsRole: {
      type: Boolean,
      default: false,
    },
    may: {
      type: Object,
      default: () => ({}),
    },
    regions: {
      type: Array,
      default: () => [],
    },
    workingGroups: {
      type: Array,
      default: () => [],
    },
  },
  data () {
    return {
      searchOpen: false,
    }
  },
  computed: {
    loginReferrer () {
      const url = new URL(window.location.href)
      const path = url.pathname + url.search
      return (path === '/') ? this.$url('dashboard') : path
    },
  },
  methods: {
    toggleOverflow () {
      if (this.state) {
        document.querySelector('#main').style = 'opacity: 0.25; pointer-events: none;'
        document.body.classList.add('overflow-hidden')
      } else {
        document.body.classList.remove('overflow-hidden')
        document.querySelector('#main').style = ''
      }
    },
  },
}
</script>
<style lang="scss" scoped>
#topbar,
nav,
.nav-row {
  min-height: 45px;
}

#topbar nav {
    box-shadow: 0em 0em 5px 0px rgba(0, 0, 0, 0.35);
    border-bottom: 1px solid var(--border);
    background-color: var(--fs-beige);
    color: var(--primary);
}

.bootstrap .navbar-brand {
  padding: 0;
  margin-right: 3px;
}

.nav-row {
  margin:0;
  display: flex;
  align-items: center;
  justify-content: space-evenly;
}

::v-deep .nav-item,
::v-deep .navbar-brand,
.navbar-toggler {
  min-width: 25px;
  color: var(--primary);
  padding: .25rem;

  @media (min-width: 321px) {
    min-width: 35px;
  }

  @media (min-width: 450px) {
    min-width: 45px;
  }
}

::v-deep .nav-item {
  text-align: center;

  .collapse.show & {
    text-align: unset;
    display: inline-block !important;
    width: 100%;

    & .nav-link i {
      width: 20px;
      margin-right: 10px;
      text-align: center;
    }
  }
}

::v-deep .group .show {
    width: 100%;
}

::v-deep .navbar-collapse {
  &.show {
    // Only when menu is shown. Fixes problem that list of dropdown items is to long.
    max-height: 99vh;
    overflow: auto;

    .dropdown-menu .scroll-container  {
      max-height: initial;
    }
  }
  order: 2;
}

</style>
