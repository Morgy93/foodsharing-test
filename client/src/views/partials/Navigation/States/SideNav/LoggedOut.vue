<template>
  <div class="navbar-navside">
    <MetaNavLoggedOut v-if="viewIsMobile" />
    <ul class="sidenav">
      <Link
        :href="$url('joininfo')"
        :title="$i18n('register.topbar')"
        class="testing-register-link"
      />
      <NavLogin />
    </ul>
  </div>
</template>

<script>
// Store
import MetaNavData from '../../Data/MetaNavData.json'
//
import Link from '@/components/Navigation/_NavItems/NavLink'
import NavLogin from '@/components/Navigation/Login/NavLogin.vue'
// State
import MetaNavLoggedOut from '../MetaNav/LoggedOut.vue'
// Mixins
import MediaQueryMixin from '@/mixins/MediaQueryMixin'

export default {
  components: {
    Link,
    NavLogin,
    MetaNavLoggedOut,
  },
  mixins: [MediaQueryMixin],
  data () {
    return {
      metaNav: MetaNavData,
    }
  },
}
</script>
