<template>
  <div>
    <fs-dropdown-menu
      v-if="workingGroups.length > 0"
      id="dropdown-groups"
      menu-title="menu.entry.your_groups"
      :show-menu-title="false"
      icon="fa-users"
    >
      <template #content>
        <div
          v-for="group in workingGroups"
          :key="group.id"
          class="group"
        >
          <a
            v-if="!alwaysOpen"
            v-b-toggle="`topbargroup_${group.id}`"
            role="menuitem"
            class="dropdown-header dropdown-item text-truncate"
            href="#"
            target="_self"
            v-html="group.name"
          />
          <h3
            v-if="alwaysOpen"
            role="menuitem"
            class="dropdown-header text-truncate"
            v-html="group.name"
          />
          <b-collapse
            :id="`topbargroup_${group.id}`"
            :visible="alwaysOpen"
            :accordion="alwaysOpen ? null : 'groups'"
          >
            <a
              v-for="(entry,key) in generateMenu(group)"
              :key="key"
              :href="entry.href ? $url(entry.href, group.id, entry.special) : '#'"
              role="menuitem"
              class="dropdown-item sub"
              @click="entry.func ? entry.func() : null"
            >
              <i
                class="fas"
                :class="entry.icon"
              />
              {{ $i18n(entry.text) }}
            </a>
          </b-collapse>
        </div>
      </template>
      <template #actions>
        <a
          :href="$url('workingGroups')"
          role="menuitem"
          class="dropdown-item"
        >
          <small>
            <i class="fas fa-users" />
            {{ $i18n('menu.entry.groups') }}
          </small>
        </a>
      </template>
    </fs-dropdown-menu>
    <menu-item
      v-else
      :url="$url('workingGroups')"
      icon="fa-users"
      :title="$i18n('menu.entry.groups')"
      :hide-title-always="true"
    />
  </div>
</template>
<script>
import { BCollapse, VBToggle } from 'bootstrap-vue'
import FsDropdownMenu from '../FsDropdownMenu'
import MenuItem from '../MenuItem'

import ConferenceOpener from '@/mixins/ConferenceOpenerMixin'

export default {
  components: { BCollapse, FsDropdownMenu, MenuItem },
  directives: { VBToggle },
  mixins: [ConferenceOpener],
  props: {
    workingGroups: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    alwaysOpen () {
      return this.workingGroups.length <= 2
    },
  },
  methods: {
    generateMenu (group) {
      const menu = [
        {
          href: 'wall', icon: 'fa-bullhorn', text: 'menu.entry.wall',
        },
        {
          href: 'forum', icon: 'fa-comment-alt', text: 'menu.entry.forum',
        },
        {
          href: 'events', icon: 'fa-calendar-alt', text: 'menu.entry.events',
        },
        {
          href: 'polls', icon: 'fa-poll-h', text: 'terminology.polls',
        },
        {
          href: 'members', icon: 'fa-user', text: 'menu.entry.members',
        },

      ]

      if (group.hasConference) {
        menu.push({
          icon: 'fa-users', text: 'menu.entry.conference', func: () => this.showConferencePopup(group.id),
        })
      }

      if (group.isAdmin) {
        menu.push({
          href: 'workingGroupEdit', icon: 'fa-cog', text: 'menu.entry.workingGroupEdit',
        })
      }

      return menu
    },
  },
}
</script>
