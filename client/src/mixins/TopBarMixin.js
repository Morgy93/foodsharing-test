// Stores
import DataUser from '@/stores/user'
// Mixins
import MediaQueryMixin from '@/mixins/MediaQueryMixin'

export default {
  mixins: [MediaQueryMixin],
  props: {
    showTitle: {
      type: Boolean,
      default: false,
    },
  },
  watch: {
    viewIsLG (newVal, _) {
      if (this.state && newVal === true) {
        this.state = false
        this.toggleBody()
      }
    },
  },
  computed: {
    user: () => DataUser.getters.getUser(),
    isFoodsaver: () => DataUser.getters.isFoodsaver(),
    hasMailBox: () => DataUser.getters.getMailBox() > 0,
    getUnreadCount: () => DataUser.getters.getMailUnreadCount(),
    isVisibleOnMobile () {
      return !this.viewIsXL
    },
    homeHref () {
      return (this.user) ? this.$url('dashboard') : this.$url('home')
    },
    loginReferrerHref () {
      return this.$url('login') + '&ref=' + encodeURIComponent(this.loginReferrer())
    },
  },
  methods: {
    loginReferrer () {
      const url = new URL(window.location.href)
      const path = url.pathname + url.search
      return (path === '/') ? this.$url('dashboard') : path
    },
    toggleMenu () {
      this.toggleState()
      this.toggleBody()
    },
    toggleBody () {
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
