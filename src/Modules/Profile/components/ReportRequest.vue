<template>
  <!-- eslint-disable vue/max-attributes-per-line -->
  <div id="report_request" class="popbox bootstrap m-2">
    <h3>
      {{ $i18n('profile.report.title', { name: foodSaverName }) }}
    </h3>
    <b-alert
      v-if="isReportedIdReportAdmin && !hasArbitrationGroup && !isReporterIdReportAdmin"
      variant="info" show
    >
      <div>
        {{ $i18n('profile.report.reportedAdminNoArbitration', { name: foodSaverName }) }}
      </div>
    </b-alert>
    <b-alert
      v-else-if="isReporterIdReportAdmin && !hasArbitrationGroup"
      variant="info" show
    >
      <div>
        <div>{{ $i18n('profile.report.reporterAdminNoArbitration') }}</div>
      </div>
    </b-alert>
    <b-alert
      v-else-if="!hasReportGroup"
      variant="info" show
    >
      <div>
        <div>{{ $i18n('profile.report.noReportGroup') }}</div>
      </div>
    </b-alert>
    <template
      v-else
    >
      <b-alert variant="info" show>
        <div>{{ $i18n('profile.report.info') }}</div>
      </b-alert>
      <b-form-select
        v-model="reportReason"
        :options="reportReasonOptions"
        class="mb-2"
        align-v="stretch"
      />
      <b-form-select
        v-if="storeListOptions.length > 1"
        v-model="storeList"
        :options="storeListOptions"
        class="mb-2"
        align-v="stretch"
      />
      <b-form-textarea
        v-model="reportText"
        class="mb-2"
        max-rows="8"
        size="sm"
      />
      <b-button
        class="text-right"
        variant="secondary"
        size="sm"
        @click="trySendReport"
      >
        {{ $i18n('profile.report.send') }}
      </b-button>
    </template>
  </div>
</template>

<script>
import $ from 'jquery'

import { addReport } from '@/api/report'
import { pulseError, pulseInfo } from '@/script'
import i18n from '@/i18n'

export default {
  props: {
    foodSaverName: { type: String, required: true },
    reportedId: { type: Number, required: true },
    reporterId: { type: Number, required: true },
    storeListOptions: { type: Array, default: () => { return [] } },
    isReportedIdReportAdmin: { type: Boolean, required: true },
    hasReportGroup: { type: Boolean, required: true },
    hasArbitrationGroup: { type: Boolean, required: true },
    isReporterIdReportAdmin: { type: Boolean, required: true },
  },
  data () {
    return {
      reportText: '',
      reportReason: null,
      storeList: null,
      reportReasonOptions: [
        { value: null, text: 'Bitte wähle die Art der Meldung' },
        { value: '1', text: 'Ist zu spät gekommen' },
        { value: '2', text: 'Ist nicht zum abholen erschienen' },
        { value: '10', text: 'Häufiges kurzfristiges Absagen der Abholungen ohne Ersatzsuche' },
        { value: '15', text: 'Verkauft gerettete Lebensmittel' },
      ],
    }
  },

  methods: {
    async trySendReport () {
      const reportReasonText = this.reportReasonOptions.find(reportReasonOptions => reportReasonOptions.value === this.reportReason)
      const message = this.reportText.trim()
      if (!message) return
      try {
        await addReport(this.reportedId, this.reporterId, this.reportReason, reportReasonText.text, message, this.storeList)
        pulseInfo(i18n('profile.report.sent'))
        this.reportText = ''
        $.fancybox.close()
      } catch (err) {
        console.error(err)
        pulseError(i18n('error_unexpected'))
      }
    },
  },
}
</script>
<style lang="scss" scoped>
#mediation_request {
  min-width: 50vw;
  max-width: 550px;
}
</style>