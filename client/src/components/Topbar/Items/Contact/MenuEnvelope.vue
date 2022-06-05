<template>
  <fs-dropdown-menu
    menu-title="menu.entry.contact"
    :items="headings"
    icon="fa-envelope"
    :badge="unread && displayMailbox ? unread : null"
    right
  />
</template>

<script>
import { getMailUnreadCount } from '@/api/mailbox'
import FsDropdownMenu from '../FsDropdownMenu'

export default {
  components: { FsDropdownMenu },
  props: {
    displayMailbox: {
      type: Boolean,
      default: false,
    },
  },
  data () {
    const contactMenuItems = []
    if (this.displayMailbox) {
      contactMenuItems.push({ url: 'mailbox', menuTitle: 'menu.entry.mailbox' })
    }
    contactMenuItems.push({ url: 'contact', menuTitle: 'menu.entry.contact' })
    contactMenuItems.push({ url: 'press', menuTitle: 'menu.entry.press' })
    contactMenuItems.push({ url: 'infosCompany', menuTitle: 'menu.entry.forcompanies' })
    contactMenuItems.push({ url: 'imprint', menuTitle: 'menu.entry.imprint' })
    return {
      headings: [{
        heading: 'menu.entry.contact',
        menuItems: contactMenuItems,
      },
      {
        heading: 'menu.entry.regionalgroups',
        menuItems: [
          { url: 'communitiesGermany', menuTitle: 'menu.entry.Germany' },
          { url: 'communitiesAustria', menuTitle: 'menu.entry.Austria' },
          { url: 'communitiesSwitzerland', menuTitle: 'menu.entry.Swiss' },
          { url: 'international', menuTitle: 'menu.entry.international' },
        ],
      }],
      unread: 0,
    }
  },
  async mounted () {
    if (this.displayMailbox) {
      this.unread = await getMailUnreadCount()
    }
  },
}

</script>
