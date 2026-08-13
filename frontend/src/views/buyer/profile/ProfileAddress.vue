<script setup lang="ts">
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { ref } from 'vue'

interface Address {
  id: number
  label: string
  recipientName: string
  phone: string
  fullAddress: string
  isPrimary: boolean
}

const addresses = ref<Address[]>([
  {
    id: 1,
    label: 'Rumah',
    recipientName: 'Admin Kabita',
    phone: '081234567890',
    fullAddress: 'Jl. Pemuda No. 12, RT 02/RW 05, Rawamangun, Pulo Gadung, Kota Jakarta Timur, DKI Jakarta 13220',
    isPrimary: true
  },
  {
    id: 2,
    label: 'Kantor',
    recipientName: 'Admin Kabita (Kantor)',
    phone: '089876543210',
    fullAddress: 'Gedung Wisma Millenia Lt. 4, Jl. MT Haryono, Pancoran, Jakarta Selatan, DKI Jakarta 12810',
    isPrimary: false
  }
])

const setPrimary = (id: number) => {
  addresses.value.forEach(addr => {
    addr.isPrimary = addr.id === id
  })
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm p-6 lg:p-8 border border-slate-100">
    <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 mb-6 border-b border-slate-100 gap-4">
        <div>
          <h1 class="text-lg font-bold text-slate-800">Alamat Saya</h1>
          <p class="text-xs text-slate-500 mt-1">Kelola alamat pengiriman untuk mempermudah transaksi belanja Anda.</p>
        </div>
        <Button label="Tambah Alamat Baru" icon="pi pi-plus"
          class="bg-blue-600! border-blue-600! text-xs! px-4! py-2!.5 rounded-xl!" />
      </div>

      <!-- Address Cards -->
        <div class="space-y-4">
          <div v-for="address in addresses" :key="address.id" :class="[
            'p-5 rounded-xl border transition-colors space-y-3',
            address.isPrimary ? 'border-blue-500 bg-blue-50/20' : 'border-slate-100 hover:border-slate-200'
          ]">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800">{{ address.label }}</span>
                <Tag v-if="address.isPrimary" value="Utama" severity="info" class="text-![10px] px-2! py-0!.5" />
              </div>
              <div class="flex items-center gap-2 text-xs">
                <button class="text-blue-600 hover:underline font-medium">Ubah</button>
                <span class="text-slate-300">|</span>
                <button class="text-rose-600 hover:underline font-medium">Hapus</button>
              </div>
            </div>

            <div>
              <p class="text-xs font-semibold text-slate-700">
                {{ address.recipientName }} <span class="text-slate-400 font-normal">({{ address.phone }})</span>
              </p>
              <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ address.fullAddress }}</p>
            </div>

            <div v-if="address!.isPrimary" class="pt-2">
              <Button label="Atur Sebagai Utama" severity="secondary" outlined
                class="text-xs! px-3! py-1!.5 rounded-lg!" @click="setPrimary(address.id)" />
            </div>
          </div>
        </div>
  </div>
</template>