<template>
  <div>
    <Container
      :title="storeName"
      tag="store_options"
    >
      <StoreInformationModal
        :is-jumper="isJumper"
        :store-id="storeId"
        :may-edit-store="mayEditStore"
        :is-coordinator="isCoordinator"
        :is-verified="isVerified"
      />
      <button
        v-if="applications.storeRequests && applications.storeRequests.length > 0"
        type="button"
        class="list-group-item list-group-item-action"
        @click="$bvModal.show('requests')"
        v-text="$i18n('store.requests', { count: applications.storeRequests.length})"
      />
      <button
        v-if="teamConversationId != null && isUserInStore"
        type="button"
        class="list-group-item list-group-item-action"
        @click="openChat(teamConversationId)"
        v-text="$i18n('store.chat.team')"
      />
      <button
        v-if="jumperConversationId != null && isUserInStore || isJumper"
        type="button"
        class="list-group-item list-group-item-action"
        @click="openChat(jumperConversationId)"
        v-html="$i18n('store.chat.jumper')"
      />
      <button
        type="button"
        class="list-group-item list-group-item-action"
        @click="$bvModal.show('storeInformationModal')"
        v-text="$i18n('storeview.show_information')"
      />
      <button
        v-if="mayLeaveStoreTeam && isUserInStore || isJumper"
        type="button"
        class="list-group-item list-group-item-action"
        href="#"
        @click="removeFromTeam(fsId, $i18n('storeedit.team.leave_myself'))"
        v-text="$i18n('storeedit.team.leave')"
      />
    </Container>
    <StoreApplications
      :store-id="storeId"
      :store-title="storeName"
      :store-requests="applications.storeRequests"
    />
  </div>
</template>

<script>
import conversationStore from '@/stores/conversations'
import { pulseError } from '@/script'
import DataUser from '@/stores/user'
import { removeStoreMember } from '@/api/stores'
import StoreInformationModal from '@/components/Modals/Store/StoreInformationModal.vue'
import Container from '@/components/Container/Container.vue'
import StoreApplications from '@/components/Modals/Store/StoreApplications.vue'
import StoreData from '@/stores/stores'

export default {
  components: {
    StoreApplications,
    StoreInformationModal,
    Container,
  },
  props: {
    storeName: { type: String, required: true },
    fsId: { type: Number, required: true },
    mayLeaveStoreTeam: { type: Boolean, default: false },
    teamConversationId: {
      type: Number,
      default: null,
    },
    jumperConversationId: {
      type: Number,
      default: null,
    },
    mayEditStore: {
      type: Boolean,
      default: null,
    },
    isCoordinator: {
      type: Boolean,
      default: null,
    },
    storeId: {
      type: Number,
      default: null,
    },
    isUserInStore: { type: Boolean, default: false },
    isJumper: { type: Boolean, default: false },
    mayDoPickup: { type: Boolean, default: false },
    isVerified: { type: Boolean, default: false },
  },
  computed: {
    applications () {
      return StoreData.getters.getStoreApplications()
    },
  },
  async mounted () {
    if (this.mayEditStore) {
      await StoreData.mutations.loadStoreApplications(this.storeId)
    }
  },
  methods: {
    openChat (conversationId) {
      conversationStore.openChat(conversationId)
    },
    async removeFromTeam (fsId, fsName) {
      if (!fsId) {
        return
      }
      if (!confirm(this.$i18n('store.sm.reallyRemove', { name: fsName }))) {
        return
      }
      this.isBusy = true
      try {
        await removeStoreMember(this.storeId, DataUser.getters.getUserId())
        window.location.href = this.$url('dashboard')
      } catch (e) {
        pulseError(this.$i18n('error_unexpected'))
        this.isBusy = false
        return
      }
      this.isBusy = false
    },
  },
}
</script>
<style lang="scss" scoped>
.list-group-item:not(:last-child) {
  border-bottom: 0;
}
</style>
