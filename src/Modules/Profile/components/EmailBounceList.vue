<template>
  <!-- eslint-disable vue/max-attributes-per-line -->
  <div class="bootstrap">
    <b-alert variant="danger" show>
      <h3>{{ $i18n('profile.warning') }}</h3>
      <div v-if="mayRemove">
        {{ $i18n('profile.mail_bounce.warning_others', { email: emailAddress }) }}
      </div>
      <div v-else>
        {{ $i18n('profile.mail_bounce.warning_1', { email: emailAddress }) }}
        <a href="/?page=settings"> {{ $i18n('profile.mail_bounce.warning_2') }} </a>
        {{ $i18n('profile.mail_bounce.warning_3') }}
        <a href="https://foodsharing.freshdesk.com/support/solutions/articles/77000299947-e-mail-sperre-im-profil"> {{ $i18n('profile.mail_bounce.warning_4') }}</a>
      </div>
    </b-alert>

    <div v-if="mayRemove">
      <ul>
        <li
          v-for="event in bounceEvents"
          :key="event.date"
        >
          {{ $dateFormatter.date(convertDate(event.date)) }}: "{{ event.category }}"
        </li>
      </ul>
      <b-button @click.prevent="removeBounces()">
        {{ $i18n('profile.mail_bounce.remove_button') }}
      </b-button>
    </div>
  </div>
</template>

<script>
import { BAlert, BButton } from 'bootstrap-vue'
import { hideLoader, pulseError, reload, showLoader } from '@/script'
import { removeUserFromBounceList } from '@/api/profile'
import i18n from '@/helper/i18n'

export default {
  components: { BAlert, BButton },
  props: {
    userId: { type: Number, required: true },
    emailAddress: { type: String, required: true },
    mayRemove: { type: Boolean, default: false },
    bounceEvents: { type: Array, default: () => { return [] } },
  },
  methods: {
    convertDate (date) {
      return new Date(Date.parse(date))
    },
    async removeBounces () {
      showLoader()
      try {
        await removeUserFromBounceList(this.userId)
        reload()
      } catch (e) {
        pulseError(i18n('error_unexpected'))
      }
      hideLoader()
    },
  },
}
</script>
