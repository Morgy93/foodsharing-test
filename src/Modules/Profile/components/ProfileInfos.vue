<template>
  <div class="infos bootstrap">
    <ul>
      <li
        v-if="lastLogin"
        class="mb-2"
      >
        <b>{{ $i18n('profile.infos.lastLogin') }}:</b> {{ lastLogin }}
      </li>
      <li
        v-if="registrationDate"
        class="mb-2"
      >
        <b>{{ $i18n('profile.infos.registrationDate') }}:</b> {{ registrationDate }}
      </li>
      <li
        v-if="privateMail"
        class="mb-2"
      >
        <b>{{ $i18n('profile.infos.privateMail') }}:</b>
        <p><a :href="getMailboxUrl(privateMail)">{{ privateMail }}</a></p>
      </li>
      <li
        v-if="fsMail"
        class="mb-2"
      >
        <b>{{ $i18n('profile.infos.fsMail') }}:</b>
        <p><a :href="getMailboxUrl(fsMail)">{{ fsMail }}</a></p>
      </li>
      <li class="mb-2">
        <b>{{ $i18n('profile.infos.buddies') }}:</b>
        <p>{{ buddycountTranslation }}</p>
      </li>
      <li>
        <b>{{ getFsIdTranslation }}:</b> {{ fsId }}
      </li>
    </ul>
  </div>
</template>

<script>

export default {
  props: {
    lastLogin: { type: String, default: '' },
    registrationDate: { type: String, default: '' },
    privateMail: { type: String, default: null },
    fsMail: { type: String, default: '' },
    buddyCount: { type: Number, default: 0 },
    name: { type: String, default: '' },
    fsId: { type: Number, required: true },
    fsIdSession: { type: Number, required: true },
    isfoodsaver: { type: Boolean, default: false },
  },
  computed: {
    getFsIdTranslation () {
      let value = this.$i18n('profile.infos.foodsaverId')
      if (!this.isfoodsaver) {
        value = this.$i18n('profile.infos.foodsharerId')
      }
      return value
    },
    buddycountTranslation () {
      let value = this.$i18n('profile.infos.buddycount1', { name: this.name, count: this.buddyCount })
      if (this.buddyCount > 1) {
        value = this.$i18n('profile.infos.buddycount', { name: this.name, count: this.buddyCount })
      }
      return value
    },
  },
  methods: {
    async getMailboxUrl (mail) {
      let mailboxUrl = this.$url('mailboxMailto', mail)
      if (this.fsIdSession === this.fsId && mail.includes('foodsharing.network')) {
        mailboxUrl = this.$url('mailbox')
      }
      return mailboxUrl
    },
  },
}
</script>

<style lang="scss" scoped>
li {
  list-style: none;
}
</style>
