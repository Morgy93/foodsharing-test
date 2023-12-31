<template>
  <div class="py-3 px-4">
    <form
      @submit.prevent
    >
      <label class="d-block">
        <div class="mb-1">
          <i class="fas fa-user mr-1" />
          {{ $i18n('login.email_address') }}
        </div>
        <input
          ref="email"
          v-model="email"
          :placeholder="$i18n('login.email_address')"
          :aria-label="$i18n('login.email_address')"
          type="email"
          name="login-email"
          class="testing-login-input-email form-control"
          autocomplete="email"
          autofocus
          @focus="focusLogin=true"
        >
      </label>
      <label class="d-block">
        <div class="mb-1">
          <i class="fas fa-key mr-1" />
          {{ $i18n('login.password') }}
        </div>
        <input
          v-model="password"
          :placeholder="$i18n('login.password')"
          :aria-label="$i18n('login.password')"
          type="password"
          name="login-password"
          class="testing-login-input-password form-control"
          autocomplete="current-password"
        >
      </label>
      <label class="d-flex align-items-center mt-3 mb-3">
        <input
          v-model="rememberMe"
          class="testing-login-input-remember mr-2"
          type="checkbox"
          name="login-remember"
        >
        {{ $i18n('login.steady_login') }}
      </label>
      <b-overlay :show="isLoading">
        <template #overlay>
          <i class="fas fa-spinner fa-spin" />
        </template>
        <b-button
          :aria-label="$i18n('login.login_button_label')"
          type="submit"
          variant="primary"
          class="testing-login-click-submit btn btn-block"
          :disabled="$v.$invalid"
          @click="submit"
          @keydown.enter="submit"
        >
          <span>
            {{ $i18n('login.submit_btn') }}
          </span>
          <i class="fas fa-arrow-right mr-auto" />
        </b-button>
      </b-overlay>
    </form>
  </div>
</template>

<script>
import { isDev } from '@/helper/server-data'
import { login } from '@/api/user'
import { required, email } from 'vuelidate/lib/validators'

import { pulseError } from '@/script'

export default {
  name: 'MenuLogin',
  data () {
    return {
      email: isDev ? 'userbot@example.com' : '',
      password: isDev ? 'user' : '',
      rememberMe: false,
      isLoading: false,
      error: null,
      focusLogin: false,
    }
  },
  validations: {
    email: { required, email },
    password: { required },
  },
  watch: {
    focusLogin: function (val) {
      if (val) {
        this.$refs.email.focus()
        this.$refs.email.select()
      }
    },
  },
  created () {
    if (localStorage.getItem('login-rememberme')) {
      this.rememberMe = true
    }
  },
  methods: {
    async submit () {
      if (this.rememberMe) {
        localStorage.setItem('login-rememberme', 'true')
      } else {
        localStorage.removeItem('login-rememberme')
      }
      if (!this.email) {
        pulseError(this.$i18n('login.error_no_email'))
        return
      }
      if (!this.password) {
        pulseError(this.$i18n('login.error_no_password'))
        return
      }
      this.isLoading = true
      try {
        await login(this.email, this.password, this.rememberMe)
        window.location.href = this.$url('dashboard')
      } catch (err) {
        this.isLoading = false
        if (err.code && err.code === 401) {
          pulseError(this.$i18n('login.error_no_auth'))
        } else {
          pulseError(this.$i18n('error_unexpected'))
          throw err
        }
      }
    },
    focusRef (ref) {
      // Some references may be a component, functional component, or plain element
      // This handles that check before focusing, assuming a `focus()` method exists
      // We do this in a double `$nextTick()` to ensure components have
      // updated & popover positioned first
      this.$nextTick(() => {
        this.$nextTick(() => {
          ;(ref.$el || ref).focus()
        })
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.form-control {
  width: 100%;
}
</style>
