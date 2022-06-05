import serverData from '@/server-data'
import { GET } from '@/script'

const pages = ['bezirk', 'betrieb', 'foodsaver', 'passgen']

export default {
  data: function () {
    return {
      activeRegionId: null,
    }
  },
  mounted () {
    this.$nextTick(function () {
      window.addEventListener('change', this.updateRegionId)
      this.updateRegionId()
    })
  },
  methods: {
    updateRegionId () {
      let regionId
      if (pages.includes(serverData.page) && GET('bid')) {
        regionId = parseInt(GET('bid'))
      } else if (serverData.page === 'groups' && GET('p')) {
        regionId = parseInt(GET('p'))
      } else if (localStorage.getItem('lastRegion')) {
        regionId = parseInt(localStorage.getItem('lastRegion'))
      } else if (serverData.user.regularRegion) {
        regionId = serverData.user.regularRegion
      }

      if (regionId !== this.activeRegionId) {
        this.activeRegionId = regionId
        localStorage.setItem('lastRegion', regionId)
      }
    },
  },
  beforeDestroy () {
    window.removeEventListener('change', this.updateRegionId)
  },
}
