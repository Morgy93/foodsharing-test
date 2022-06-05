<template>
  <div class="intro alert alert-secondary d-flex align-items-center">
    <a
      :href="'profile/' + id"
      class="img-thumbnail position-relative"
    >
      <img
        v-if="sleep_status"
        alt="zZzZzz..."
        class="position-absolute"
        style="top:-5px;left:-8px"
        src="/img/sleep35x35.png"
      >
      <img
        :alt="name"
        width="45px"
        :src="image"
      >
    </a>
    <div class="ml-3 d-flex flex-column">
      <h3
        style="font-family: 'Alfa Slab One', serif"
        v-html="$i18n('dashboard.greeting', {name})"
      />
      <p
        v-if="role === 0 || !region"
        class="mb-0"
        v-html="$i18n('dashboard.foodsharer')"
      />
      <p
        v-else-if="pickups > 0 && Number(weight) > 0"
        class="mb-0"
        v-html="$i18n('dashboard.foodsaver_amount', {pickups, weight})"
      />
      <p
        v-else
        class="mb-0"
        v-html="$i18n('dashboard.homeRegion', {region})"
      />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    user: { type: Object, required: true },
  },
  data () {
    return {}
  },
  computed: {
    id () {
      return this.user.id
    },

    name () {
      return this.user.name
    },

    sleep_status () {
      return this.user.sleep_status
    },

    image () {
      return this.user.photo || '/img/50_q_avatar.png'
    },

    region () {
      return this.user.bezirk_name
    },

    pickups () {
      return this.user.stat_fetchcount
    },

    weight () {
      return this.user.stat_fetchweight
    },

    role () {
      return this.user.rolle
    },
  },
}
</script>
