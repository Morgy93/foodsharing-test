<template>
  <a
    class="d-flex"
    :class="classes"
    :href="bell.href"
    @click="$emit('bell-read', bell)"
    @click.middle="$emit('bell-read', bell)"
  >
    <div
      class="icon w-20 mr-2 d-flex text-center justifiy-content-center align-items-center"
      @mouseover="hover = bell.isCloseable"
      @mouseout="hover = false"
    >
      <i
        v-if="bell.icon && !hover"
        class="d-flex img-thumbnail w-100 h-100 align-items-center justify-content-center"
        :class="[bell.icon]"
      />
      <Avatar
        v-else-if="bell.image && !hover"
        class="img-thumbnail"
        style="min-width: 42px;"
        :url="bell.image"
        :size="35"
      />
      <i
        v-else
        class="d-flex img-thumbnail w-100 h-100 align-items-center justify-content-center"
        :class="'fas fa-times'"
        @click.stop.prevent="bell.isCloseable && $emit('remove', bell.id)"
      />
    </div>
    <div class="d-flex flex-column justify-content-between truncated">
      <div class="d-flex justify-content-between align-items-center">
        <h5
          class="mb-1 text-truncate"
        >
          {{ $i18n(`bell.${bell.title}`, bell.payload) }}
        </h5>
        <small class="text-muted text-right nowrap">
          {{ $dateDistanceInWords(bell.createdAt) }}
        </small>
      </div>
      <p
        class="mb-0 text-truncate"
      >
        {{ $i18n(`bell.${bell.key}`, bell.payload) }}
      </p>
    </div>
  </a>
</template>

<script>
import Avatar from '@/components/Avatar.vue'

export default {
  components: {
    Avatar,
  },
  props: {
    bell: {
      type: Object,
      default: () => ({}),
    },
  },
  data () {
    return {
      hover: false,
    }
  },
  computed: {
    classes () {
      return [
        'list-group-item',
        'list-group-item-action',
        !this.bell.isRead ? 'list-group-item-warning' : null,
        this.bell.isDeleting ? 'disabledLoading' : null,
      ]
    },
  },
}
</script>

<style lang="scss" scoped>
.list-group-item {
    padding: 0.4em 1em;
    border: unset;

    h5 {
      font-weight: bold;
      font-size: 0.9em;
    }

    p {
        font-size: 0.8em;
    }
}

.icon {
  height: 42px;
  width: 42px;
  line-height: 0.7em;
  font-size: 1.5rem;
}

.truncated {
  flex: 1;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
