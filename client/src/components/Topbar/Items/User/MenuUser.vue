<template>
  <fs-dropdown-menu
    v-b-tooltip="$i18n('menu.entry.your_account')"
    no-caret
    class="user"
    right
    menu-title="menu.entry.your_account"
  >
    <template #heading-icon>
      <img
        width="22px"
        :src="avatar"
        class="rounded-circle border border-primary mr-2"
      >
    </template>
    <template #content>
      <a
        :href="$url('profile', userId)"
        role="menuitem"
        class="dropdown-item"
      >
        <i class="fas fa-address-card" /> {{ $i18n('profile.title') }}
      </a>
      <a
        :href="$url('settings')"
        role="menuitem"
        class="dropdown-item"
      >
        <i class="fas fa-cog" /> {{ $i18n('settings.header') }}
      </a>
      <div class="dropdown-divider" />
      <a
        href="#"
        role="menuitem"
        class="dropdown-item"
        @click="$refs.languageChooser.show()"
      >
        <i class="fas fa-language" /> {{ $i18n('menu.entry.language') }}
      </a>
      <div class="dropdown-divider" />
      <a
        :href="$url('logout')"
        role="menuitem"
        class="dropdown-item"
      >
        <i class="fas fa-power-off" /> {{ $i18n('login.logout') }}
      </a>
    </template>
    <template #actions>
      <language-chooser ref="languageChooser" />
    </template>
  </fs-dropdown-menu>
</template>
<script>
import FsDropdownMenu from '../FsDropdownMenu'
import LanguageChooser from './LanguageChooser'

export default {
  components: { LanguageChooser, FsDropdownMenu },
  props: {
    userId: {
      type: Number,
      default: null,
    },
    avatar: {
      type: String,
      default: null,
    },
    showTitle: { type: Boolean, default: false },
  },
}
</script>
