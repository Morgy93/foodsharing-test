<template>
  <!-- eslint-disable vue/max-attributes-per-line -->
  <div class="release-entry">
    <b-card no-body class="mb-1">
      <b-card-header header-tag="header" class="p-1" role="tab">
        <b-button v-b-toggle="releaseId" class="release-toggle" :data-id="releaseId" variant="light" block>
          {{ releaseTitle }}
        </b-button>
      </b-card-header>
      <b-collapse :id="releaseId" :visible="isVisible" role="tabpanel">
        <b-card-body>
          <b-card-text>
            <Markdown :source="markdownContent" />
          </b-card-text>
        </b-card-body>
      </b-collapse>
    </b-card>
  </div>
</template>

<script>
import Markdown from '@/components/Markdown/Markdown'

export default {
  components: { Markdown },
  props: {
    releaseId: { type: String, required: true },
    releaseTitle: { type: String, required: true },
    markdownContent: { type: String, default: '' },
    isVisible: { type: Boolean, default: false },
  },
}
</script>

<style lang="scss" scoped>
::v-deep img {
  margin: 0.5rem 1rem;
  margin-top: 0;

  &[src$="left"] {
    float: left;
    margin-left: 0;
  }

  &[src$="right"] {
    float: right;
    margin-right: 0;
  }

  &[src$="left-clear"] {
    float: left;
    margin-left: 0;
    clear: both;
  }

  &[src$="right-clear"] {
    float: right;
    margin-right: 0;
    clear: both;
  }

  @media (max-width: 627px) {
    &[src] {
      float: none;
      display: block;
      margin: 0.5rem auto;
      max-width: 100%;
    }
  }
}

.bootstrap .btn.release-toggle {
  border-width: 0.5rem;
  font-weight: bolder;

  // Jackfrucht
  &[data-id="2023-09"] { border-color: #d7c955; }

  // Icecream
  &[data-id="2022-12"] { border-color: #76b5c5; }

  // Heidelbeere
  &[data-id="2022-05"] { border-color: #5780a9; }

  // Grapefruit
  &[data-id="2022-01"] { border-color: #CC4D46; }

  // Feige
  &[data-id="2021-09"] { border-color: #c2d8bc; }

  // Erdbeere
  &[data-id="2021-03"] { border-color: #fc6c85; }

  // Dragonfruit
  &[data-id="2020-12"] { border-color: #f35d8b; }

  // Cranberry
  &[data-id="2020-10"] { border-color: #843e62; }

  // Birne
  &[data-id="2020-08"] { border-color: #d1e231; }

  // Apfelsine
  &[data-id="2020-05"] { border-color: #ffa500; }
}
</style>
