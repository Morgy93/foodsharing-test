<!-- eslint-disable vue/max-attributes-per-line -->
<template>
  <b-modal
    id="addBasketModal"
    ref="addBasketModal"
    :title="$i18n(edit ? 'basket.edit' : 'basket.add')"
    size="lg"
    :cancel-title="$i18n('globals.close')"
    :ok-title="$i18n('globals.save')"
    no-close-on-esc
    no-close-on-backdrop
    :ok-disabled="!isDataValid"
    @ok="save"
  >
    <b-alert type="info" show>
      <i class="fas fa-info-circle" />
      {{ $i18n('basket.public-info') }}
    </b-alert>
    <FileUpload
      class="mb-3"
      :filename="imageUrl"
      :is-image="true"
      :img-width="600"
      :img-height="400"
      :enable-resize="true"
      @change="(file) => imageUrl = file.url"
    />

    <label for="basket-description-input">{{ $i18n('basket.description') }}:</label>
    <b-form-textarea
      id="basket-description-input"
      v-model="description"
      class="mb-3"
      rows="1"
      max-rows="3"
    />

    <label>{{ $i18n('basket.contact_types') }}:</label>
    <b-form-group
      :invalid-feedback="$i18n('basket.modal_error.no_contact')"
      :state="contact.chat || contact.phone"
    >
      <b-form-checkbox id="chat-checkbox" v-model="contact.chat" inline>
        {{ $i18n('basket.contact.write') }}
      </b-form-checkbox>
      <b-form-checkbox id="phone-checkbox" v-model="contact.phone" inline>
        {{ $i18n('basket.contact.call') }}
      </b-form-checkbox>
    </b-form-group>
    <b-form-group
      v-if="contact.phone"
      :invalid-feedback="$i18n('basket.modal_error.no_phone')"
      :state="!!phoneNumber"
    >
      <label for="phone-number-input">{{ $i18n('globals.telephone_number') }}</label>
      <b-form-input
        id="phone-number-input"
        v-model="phoneNumber"
        type="tel"
        placeholder="+49 ..."
        size="sm"
        inline
      />
    </b-form-group>

    <div v-if="!edit" class="mb-3">
      <label for="duration-select">{{ $i18n('lifetime') }}</label>
      <b-form-select id="duration-select" v-model="durationInDays" :options="durationOptions" size="sm" />
    </div>

    <b-form-group
      :label="$i18n('address') + ':'"
      label-for="location-input"
    >
      <b-form-checkbox v-model="useHomeAddress" inline switch :disabled="!hasValidHomeAddress">
        {{ $i18n('basket.use_home_address') }}
        <span v-if="hasValidHomeAddress">
          ({{ user.address }}, {{ user.postcode }} {{ user.city }})
        </span>
      </b-form-checkbox>
      <LeafletLocationSearch
        v-if="!useHomeAddress"
        id="location-input"
        :zoom="17"
        :coordinates="location"
        :street="address.street"
        :postal-code="address.zipCode"
        :city="address.city"
        icon-name="shopping-basket"
        icon-color="green"
        @address-change="onAddressChanged"
      />
    </b-form-group>
  </b-modal>
</template>

<script>
import FileUpload from '@/components/upload/FileUpload.vue'
import LeafletLocationSearch from '@/components/map/LeafletLocationSearch.vue'
import DataUser, { mutations as userStoreMutations } from '@/stores/user.js'
import { addBasket, editBasket } from '@/api/baskets'
import { mutations as basketStoreMutations } from '@/stores/baskets'
import { pulseInfo } from '@/script'

const defaultBasketData = {
  imageUrl: null,
  description: '',
  contact: {
    phone: false,
    chat: true,
  },
  phoneNumber: null,
  durationInDays: 3,
  location: { lat: 50.89, lon: 10.13 },
  address: {},
  useHomeAddress: false,
  hasValidHomeAddress: undefined,
}

export default {
  components: { FileUpload, LeafletLocationSearch },
  props: {
    basket: { type: Object, default: null },
    edit: { type: Boolean, default: false },
  },
  data () {
    const durationOptions = [1, 2, 3, 5, 7, 14, 21].map(days => ({ value: days, text: this.$i18n(`basket.valid.${days}`) }))
    if (!this.edit) {
      this.initUsingUserDetails()
      return Object.assign({}, { durationOptions }, defaultBasketData)
    }
    this.testHomeRegion()
    return {
      durationOptions,
      imageUrl: this.basket.picture,
      description: this.basket.description,
      contact: {
        chat: this.basket.contact_type.includes(1),
        phone: this.basket.contact_type.includes(2),
      },
      phoneNumber: this.basket.handy || this.basket.tel,
      durationInDays: undefined,
      location: { lat: this.basket.lat, lon: this.basket.lon },
      address: {},
      useHomeAddress: false,
      hasValidHomeAddress: undefined,
    }
  },
  computed: {
    user () {
      return DataUser.getters.getUserDetails()
    },
    isDataValid () {
      return this.description.trim() && (this.contact.chat || this.contact.phone) && (this.contact.phone ? this.phoneNumber : true)
    },
  },
  methods: {
    onAddressChanged (coordinates, street, postalCode, city) {
      this.location = coordinates
      this.address.street = street
      this.address.zipCode = postalCode
      this.address.city = city
    },
    async initUsingUserDetails () {
      await userStoreMutations.fetchDetails()
      this.phoneNumber = this.user.mobile
      this.hasValidHomeAddress = Boolean(this.user.coordinates.lat) && Boolean(this.user.address) && Boolean(this.user.city)
      this.useHomeAddress = true
      if (this.hasValidHomeAddress) {
        this.location = Object.assign({}, this.user.coordinates)
        this.address = {
          street: this.user.address,
          zipCode: this.user.postcode,
          city: this.address.city,
        }
      }
    },
    async testHomeRegion () {
      await userStoreMutations.fetchDetails()
      this.phoneNumber ||= this.user.mobile
      this.hasValidHomeAddress = Boolean(this.user.coordinates.lat) && Boolean(this.user.address) && Boolean(this.user.city)
      this.useHomeAddress = this.hasValidHomeAddress &&
        Math.abs(this.basket.lat - this.user.coordinates.lat) < 1e-5 &&
        Math.abs(this.basket.lon - this.user.coordinates.lon) < 1e-5
      if (this.useHomeAddress) {
        this.address = {
          street: this.user.address,
          zipCode: this.user.postcode,
          city: this.address.city,
        }
      }
    },
    getBasketData () {
      return {
        description: this.description,
        imageUrl: this.imageUrl,
        contactTypes: [...(this.contact.chat ? [1] : []), ...(this.contact.phone ? [2] : [])],
        mobile: this.phoneNumber,
        lifeTimeInDays: this.durationInDays,
        lat: this.location.lat,
        lon: this.location.lon,
      }
    },
    async addBasket () {
      await addBasket(this.getBasketData())
      pulseInfo(this.$i18n('basket.published'))
      this.resetModal()
      basketStoreMutations.fetchOwn()
    },
    async editBasket () {
      await editBasket(this.basket.id, this.getBasketData())
      location.reload() // as long as part of the basket page is written in php, the new basket data only is used in the page upon reload.
    },
    resetModal () {
      Object.assign(this, defaultBasketData)
      this.initUsingUserDetails()
    },
    save () {
      if (this.edit) {
        this.editBasket()
      } else {
        this.addBasket()
      }
    },
  },
}
</script>

<style scoped lang="scss">
#addBasketModal #location-input{
  padding: 0.75em 0.75em 0.25em 0.75em;
  box-shadow: 0 0 4px 2px #0002 inset;
  border-radius: var(--border-radius);
  margin-top: 0.5em;
}
</style>
