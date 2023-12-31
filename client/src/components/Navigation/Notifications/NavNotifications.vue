<template>
  <Dropdown
    :title="$i18n('navigation.notifications')"
    icon="fa-bell"
    :badge="unread"
    direction="right"
    is-fixed-size
    is-scrollable
  >
    <template
      v-if="bells.length > 0"
      #content
    >
      <NotificationsEntry
        v-for="bell in bells"
        :key="bell.id"
        :bell="bell"
        @remove="onBellDelete"
        @read="onBellRead"
      />
    </template>
    <template
      v-else
      #content
    >
      <small
        role="menuitem"
        class="disabled dropdown-item"
        v-text="$i18n('bell.no_bells')"
      />
    </template>
    <template #actions>
      <button
        role="menuitem"
        class="dropdown-item dropdown-action"
        @click="reloadUncached()"
      >
        <i class="icon-subnav fas fa-sync" />
        {{ $i18n('menu.entry.refresh') }}
      </button>
      <button
        role="menuitem"
        class="dropdown-item dropdown-action"
        :class="{ 'disabled': allLoaded }"
        @click="loadMoreBells()"
      >
        <i class="icon-subnav fas fa-angle-double-down" />
        {{ $i18n('menu.entry.load_more') }}
      </button>
      <button
        role="menuitem"
        class="dropdown-item dropdown-action"
        :class="{ 'disabled': !unread }"
        @click="markNewBellsAsRead()"
      >
        <i class="icon-subnav fas fa-check-double" />
        {{ $i18n('menu.entry.mark_as_read') }}
      </button>
      <button
        role="menuitem"
        class="dropdown-item dropdown-action"
        :class="{ 'disabled': !readMessagesDisplayed }"
        @click="deleteAllReadBells()"
      >
        <i class="icon-subnav fas fa-trash" />
        {{ $i18n('menu.entry.delete_read') }}
      </button>
    </template>
  </Dropdown>
</template>
<script>
// Stores
import DataBell from '@/stores/bells'
// Components
import Dropdown from '../_NavItems/NavDropdown'
import NotificationsEntry from './NavNotificationsEntry'
// Mixins
import { pulseError } from '@/script'

export default {
  components: {
    NotificationsEntry,
    Dropdown,
  },
  computed: {
    bells () {
      const bells = DataBell.getters.get()
      bells.sort((a, b) => a.isRead - b.isRead)
      return bells
    },
    allLoaded () {
      return DataBell.getters.getAreAllLoaded()
    },
    readMessagesDisplayed () {
      return this.bells.some(b => b.isRead)
    },
    unread () {
      let { count, maybeMore: plus } = DataBell.getters.getUnreadCount()
      if (!count) return ''
      if (count > 99) {
        count = 99
        plus = true
      }
      return `${count}${plus ? '+' : ''}`
    },
  },
  methods: {
    async onBellDelete (id) {
      try {
        await DataBell.mutations.delete([id])
      } catch (err) {
        pulseError(this.$i18n('error_unexpected'))
      }
    },
    async onBellRead (bell) {
      if (!bell.isRead) {
        try {
          await DataBell.mutations.markAsRead(bell)
        } catch (err) {
          pulseError(this.$i18n('error_unexpected'))
        }
      }
    },
    markNewBellsAsRead () {
      try {
        DataBell.mutations.markNewBellsAsRead()
      } catch {
        pulseError(this.$i18n('error_unexpected'))
      }
    },
    loadMoreBells () {
      try {
        DataBell.mutations.loadMore()
      } catch {
        pulseError(this.$i18n('error_unexpected'))
      }
    },
    reloadUncached () {
      try {
        DataBell.mutations.fetch(true)
      } catch {
        pulseError(this.$i18n('error_unexpected'))
      }
    },
    async deleteAllReadBells () {
      const ids = this.bells.filter(b => b.isRead).map(b => b.id)
      const confimation = await this.$bvModal.msgBoxConfirm(this.$i18n('menu.bell.delete_read_confirmation.text', { count: ids.length }), {
        title: this.$i18n('menu.bell.delete_read_confirmation.title'),
        okVariant: 'danger',
        okTitle: this.$i18n('button.delete'),
        cancelTitle: this.$i18n('button.cancel'),
        hideHeaderClose: false,
        centered: true,
      })
      if (!confimation) return
      try {
        DataBell.mutations.delete(ids)
      } catch {
        pulseError(this.$i18n('error_unexpected'))
      }
    },
  },
}
</script>
