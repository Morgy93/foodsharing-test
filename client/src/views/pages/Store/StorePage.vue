<template>
  <section
    v-if="storeInformation"
    class="container my-3 my-sm-5"
  >
    <div class="row">
      <div class="col-lg-3 mr-lg-4 mr-xl-0">
        <StoreOptions
          :store-name="storeInformation.name"
          :team-conversation-id="permissions.teamConversationId"
          :jumper-conversation-id="permissions.jumperConversationId"
          :may-edit-store="permissions.mayEditStore"
          :is-user-in-store="isUserInStore"
          :may-leave-store-team="permissions.mayLeaveStoreTeam"
          :is-jumper="permissions.isJumper"
          :fs-id="userId"
          :store-id="storeId"
          :is-coordinator="permissions.isCoordinator"
          :is-verified="isVerified"
        />
        <StoreWall
          v-if="viewIsMobile"
          :may-read-store-wall="permissions.mayReadStoreWall"
          :store-id="storeId"
          :managers="storeManagers"
          :may-write-post="permissions.mayWritePost"
          :may-delete-everything="permissions.mayDeleteEverything"
        />
        <StoreTeam
          v-if="!viewIsMobile"
          :fs-id="userId"
          :is-coordinator="permissions.isCoordinator"
          :may-edit-store="permissions.mayEditStore"
          :team="storeMember"
          :store-id="storeId"
          :store-title="storeInformation.name"
          :region-id="storeInformation.region.id"
        />
      </div>
      <div class="col">
        <div
          v-if="permissions.isJumper && !permissions.mayEditStore"
          class="alert alert-info"
          role="alert"
        >
          {{ $i18n('store.willgetcontacted') }}
        </div>
        <div
          v-if="!permissions.mayDoPickup && !permissions.isJumper && !isVerified"
          class="alert alert-info"
          role="alert"
        >
          {{ $i18n('store.not_verified') }}
        </div>
        <PickupHistory
          v-if="permissions.maySeePickupHistory"
          :store-id="storeId"
          :cooperation-start="storeInformation.cooperationStart"
        />
        <StoreLog
          v-if="permissions.maySeeStoreLog"
          :store-id="storeId"
          :cooperation-start="storeInformation.cooperationStart"
        />
        <StoreWall
          v-if="!viewIsMobile"
          :may-read-store-wall="permissions.mayReadStoreWall"
          :store-id="storeId"
          :managers="storeManagers"
          :may-write-post="permissions.mayWritePost"
          :may-delete-everything="permissions.mayDeleteEverything"
        />
      </div>
      <div class="col-lg-3">
        <StoreInfos
          :particularities-description="storeInformation.description"
          :weight-type="storeInformation.weight"
          :store-title="storeInformation.name"
          :street="storeInformation.address.street"
          :postcode="storeInformation.address.zipCode"
          :city="storeInformation.address.city"
          :last-fetch-date="lastFetchDate"
          :press="storeInformation.publicity"
          :region-pickup-rules="storeInformation.options.useRegionPickupRule"
          :region-pickup-rule-active="regionPickupRule.regionPickupRuleActive"
          :region-pickup-rule-timespan="regionPickupRule.regionPickupRuleTimespan"
          :region-pickup-rule-limit="regionPickupRule.regionPickupRuleLimit"
          :region-pickup-rule-limit-day="regionPickupRule.regionPickupRuleLimitDay"
          :region-pickup-rule-inactive="regionPickupRule.regionPickupRuleInactive"
        />
        <PickupList
          v-if="permissions.mayDoPickup"
          :may-do-pickup="permissions.mayDoPickup"
          :store-id="storeId"
          :store-title="storeInformation.name"
          :is-coordinator="permissions.isCoordinator"
          :may-edit-store="permissions.mayEditStore"
          :team-conversation-id="permissions.teamConversationId"
        />
        <StoreTeam
          v-if="viewIsMobile"
          :fs-id="userId"
          :is-coordinator="permissions.isCoordinator"
          :may-edit-store="permissions.mayEditStore"
          :team="storeMember"
          :store-id="storeId"
          :store-title="storeInformation.name"
          :region-id="storeInformation.region.id"
        />
      </div>
    </div>
  </section>
</template>

<script>
import MediaQueryMixin from '@/mixins/MediaQueryMixin'
import StoreOptions from '@/components/Stores/StoreOptions.vue'
import StoreTeam from '@/components/Stores/StoreTeam/StoreTeam.vue'
import StoreInfos from '@/components/Stores/StoreInfos.vue'
import PickupHistory from '@/components/Stores/PickupHistory.vue'
import StoreWall from '@/components/Stores/StoreWall.vue'
import PickupList from '@/components/Stores/PickupList.vue'
import DataUser from '@/stores/user'
import StoreData from '@/stores/stores'
import { pulseInfo } from '@/script'
import StoreLog from '@/components/Stores/StoreLog.vue'

export default {
  components: {
    StoreOptions,
    StoreTeam,
    StoreInfos,
    PickupHistory,
    StoreWall,
    PickupList,
    StoreLog,
  },
  mixins: [MediaQueryMixin],
  props: {
    storeId: { type: Number, required: true },
    collectionQuantity: { type: String, default: '' },
    storeManagers: { type: Array, default: () => [] },
    showTeamRequests: { type: Boolean, default: false },
  },
  data () {
    return {
      isUserInStore: false,
      lastFetchDate: null,
    }
  },
  computed: {
    isVerified () {
      return DataUser.getters.isVerified()
    },
    userId () {
      return DataUser.getters.getUserId()
    },
    storeMember () {
      return StoreData.getters.getStoreMember()
    },
    storeInformation () {
      return StoreData.getters.getStoreInformation()
    },
    permissions () {
      return StoreData.getters.getStorePermissions()
    },
    regionPickupRule () {
      return StoreData.getters.getStoreRegionOptions()
    },
  },
  async mounted () {
    await StoreData.mutations.loadPermissions(this.storeId)
    await DataUser.mutations.fetchDetails()
    await StoreData.mutations.loadStoreInformation(this.storeId)
    await StoreData.mutations.loadGetRegionOptions(this.storeInformation.region.id)
    await StoreData.mutations.loadStoreMember(this.storeId)
    await StoreData.mutations.loadStoreLog(this.storeId, this.storeInformation.calendarInterval)

    this.checkIsUserInStore()
    this.getLastFetchDate()
    this.loadRightsInfo()

    const applications = StoreData.getters.getStoreApplications()
    if (this.showTeamRequests && applications.storeRequests && applications.storeRequests.length > 0) {
      this.$bvModal.show('requests')
    }
  },
  methods: {
    loadRightsInfo () {
      if (this.permissions.mayEditStore && !this.permissions.isManager) {
        if (this.permissions.isOrgUser) {
          pulseInfo(this.$i18n('storeedit.team.orga'))
        } else if (this.permissions.isCoordinator) {
          pulseInfo(this.$i18n('storeedit.team.coordinator'))
        } else if (this.permissions.isAmbassador) {
          pulseInfo(this.$i18n('storeedit.team.amb'))
        }
      }
    },
    checkIsUserInStore () {
      this.isUserInStore = this.storeMember.some(item => item.id === this.userId)
    },
    getLastFetchDate () {
      const userItem = this.storeMember.find(item => item.id === this.userId)
      if (userItem) {
        const MILLISECONDS_PER_SECOND = 1000
        // Multiplication by 1000 used in this context to convert a Unix timestamp from seconds to milliseconds.
        // JavaScript expects Unix timestamps in milliseconds, while it comes from databases in seconds.
        this.lastFetchDate = userItem?.last_fetch ? new Date(userItem.last_fetch * MILLISECONDS_PER_SECOND) : null
      }
    },
  },
}

</script>
