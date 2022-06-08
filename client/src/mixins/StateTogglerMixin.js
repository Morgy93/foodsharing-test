export default {
  data () {
    return {
      state: false,
      isAutoClose: true,
    }
  },
  mounted () {
    document.addEventListener('click', this.close)
  },
  beforeDestroy () {
    document.removeEventListener('click', this.close)
  },
  methods: {
    toggleState () {
      this.state = !this.state
    },
    close (e) {
      if (this.isAutoClose && !this.$el.contains(e.target)) {
        this.state = false
      }
    },
  },
}
