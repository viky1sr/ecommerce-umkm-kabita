<script setup lang="ts">
// @ts-ignore
import MapPicker from '@/components/maps/MapPicker.vue'
import { locationService } from '@/services/locationService'
import type { CodLocation, StoreCodLocationRequest } from '@/types'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import { computed, ref, watch } from 'vue'

const toast = useToast()

const props = defineProps<{
  visible: boolean
  mode: 'add' | 'edit'
  codLocation?: CodLocation | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  saved: []
}>()

const isSubmitting = ref(false)

const form = ref<StoreCodLocationRequest>({
  name: '',
  address: '',
  phone: '',
  latitude: null,
  longitude: null,
  is_default: false,
})

const mapLocation = computed({
  get: () => {
    const lat = form.value.latitude ? Number(form.value.latitude) : undefined
    const lng = form.value.longitude ? Number(form.value.longitude) : undefined
    if (lat == null || lng == null || isNaN(lat) || isNaN(lng)) return null
    return {
      latitude: lat,
      longitude: lng,
      address: form.value.address || '',
    }
  },
  set: (val) => {
    if (val) {
      form.value.latitude = String(val.latitude)
      form.value.longitude = String(val.longitude)
      form.value.address = val.address
    } else {
      form.value.latitude = null
      form.value.longitude = null
    }
  },
})

watch(
  () => props.visible,
  (val) => {
    if (!val) return
    if (props.mode === 'edit' && props.codLocation) {
      form.value = {
        name: props.codLocation.name,
        address: props.codLocation.address,
        phone: props.codLocation.phone,
        latitude: props.codLocation.latitude,
        longitude: props.codLocation.longitude,
        is_default: props.codLocation.is_default ?? false,
      }
    } else {
      form.value = {
        name: '',
        address: '',
        phone: '',
        latitude: null,
        longitude: null,
        is_default: false,
      }
    }
  }
)

const handleSave = async () => {
  if (!form.value.name || !form.value.phone || !form.value.address) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Nama, telepon, dan alamat wajib diisi.', life: 3000 })
    return
  }

  isSubmitting.value = true
  try {
    if (props.mode === 'add') {
      await locationService.create(form.value)
    } else if (props.mode === 'edit' && props.codLocation?.id) {
      await locationService.update(props.codLocation.id, form.value)
    }
    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: props.mode === 'add' ? 'Alamat COD berhasil ditambahkan.' : 'Alamat COD berhasil diperbarui.',
      life: 3000,
    })
    emit('saved')
    emit('update:visible', false)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat menyimpan.', life: 3000 })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
    :header="mode === 'add' ? 'Tambah Alamat COD' : 'Edit Alamat COD'" :style="{ width: '90%', maxWidth: '560px' }"
    class="rounded-2xl">
    <div class="space-y-4 py-2">
      <div class="space-y-1">
        <label class="text-xs font-semibold text-slate-700">Nama Penerima</label>
        <InputText v-model="form.name" placeholder="mis. Budi Santoso" class="w-full text-xs!" />
      </div>

      <div class="space-y-1">
        <label class="text-xs font-semibold text-slate-700">Nomor Telepon</label>
        <InputText v-model="form.phone" placeholder="mis. 081234567890" class="w-full text-xs!" />
      </div>

      <div class="space-y-1">
        <label class="text-xs font-semibold text-slate-700">Alamat Lengkap</label>
        <Textarea v-model="form.address" rows="3" placeholder="Jalan, No. Rumah, RT/RW, Kecamatan, Kota, Kode Pos"
          class="w-full text-xs!" />
      </div>

      <div class="space-y-1">
        <label class="text-xs font-semibold text-slate-700">Lokasi Pickup</label>
        <MapPicker v-model="mapLocation" />
      </div>

      <div class="flex items-center gap-2">
        <Checkbox v-model="form.is_default" :binary="true" inputId="cod_is_default" />
        <label for="cod_is_default" class="text-xs font-semibold text-slate-700">Jadikan alamat utama</label>
      </div>

      <div class="flex gap-2 pt-2">
        <Button label="Batal" severity="secondary" outlined class="flex-1 text-xs!"
          @click="emit('update:visible', false)" />
        <Button label="Simpan" :loading="isSubmitting" class="flex-1 text-xs!" @click="handleSave" />
      </div>
    </div>
  </Dialog>
</template>
