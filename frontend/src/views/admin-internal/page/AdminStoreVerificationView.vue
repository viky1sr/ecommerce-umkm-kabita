<script setup lang="ts">
import { getApiErrorMessage } from '@/services/apiError'
import { adminShopService } from '@/services/adminShopService'
import type { Shop } from '@/types/entities'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { computed, onMounted, ref } from 'vue'

import AdminStoreApproveModal from '../components/store-verification/AdminStoreApproveModal.vue'
import AdminStoreDetailModal from '../components/store-verification/AdminStoreDetailModal.vue'
import AdminStoreFilter from '../components/store-verification/AdminStoreFilter.vue'
import AdminStoreRejectModal from '../components/store-verification/AdminStoreRejectModal.vue'
import AdminStoreTable from '../components/store-verification/AdminStoreTable.vue'

const toast = useToast()

const isLoading = ref(true)
const isError = ref(false)
const errorMessage = ref('')
const activeTab = ref('pending')
const shops = ref<Partial<Shop>[]>([])
const selectedShop = ref<Partial<Shop> | null>(null)

const showDetailModal = ref(false)
const showApproveModal = ref(false)
const showRejectModal = ref(false)

const pendingCount = computed(() => shops.value.filter((shop) => shop.status === 'pending').length)
const filteredShops = computed(() => shops.value.filter((shop) => shop.status === activeTab.value))

const fetchShops = async () => {
  isLoading.value = true
  isError.value = false
  errorMessage.value = ''

  try {
    const response = await adminShopService.listPending({ per_page: 100 })
    shops.value = response.data
  } catch (error) {
    isError.value = true
    errorMessage.value = getApiErrorMessage(error, 'Gagal memuat data verifikasi toko.')
  } finally {
    isLoading.value = false
  }
}

const openDetail = (shop: Partial<Shop>) => {
  selectedShop.value = shop
  showDetailModal.value = true
}

const openApprove = (shop: Partial<Shop>) => {
  selectedShop.value = shop
  showApproveModal.value = true
}

const openReject = (shop: Partial<Shop>) => {
  selectedShop.value = shop
  showRejectModal.value = true
}

const executeApprove = async () => {
  if (!selectedShop.value?.id) return

  try {
    const verifiedShop = await adminShopService.verify(selectedShop.value.id)
    shops.value = shops.value.map((shop) => (shop.id === verifiedShop.id ? verifiedShop : shop))

    showApproveModal.value = false
    showDetailModal.value = false
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Verifikasi toko disetujui.', life: 3000 })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: getApiErrorMessage(error, 'Gagal memverifikasi toko.'),
      life: 3000,
    })
  }
}

const executeReject = async (payload: { reason: string }) => {
  if (!selectedShop.value?.id) return

  try {
    const rejectedShop = await adminShopService.reject(selectedShop.value.id, {
      rejection_reason: payload.reason,
    })
    shops.value = shops.value.map((shop) => (shop.id === rejectedShop.id ? rejectedShop : shop))

    showRejectModal.value = false
    showDetailModal.value = false
    toast.add({ severity: 'warn', summary: 'Ditolak', detail: 'Toko telah ditolak dengan alasan yang direkam.', life: 3000 })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: getApiErrorMessage(error, 'Gagal menolak toko.'),
      life: 3000,
    })
  }
}

onMounted(fetchShops)
</script>

<template>
  <div class="relative min-h-[80vh]">

    <div v-if="isLoading"
      class="fixed inset-0 z-100 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm transition-all duration-300">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
      <span class="mt-4 text-sm font-semibold text-slate-600 animate-pulse">Memuat pengajuan toko...</span>
    </div>

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Verifikasi Toko</h1>
      <p class="text-sm text-slate-500 mt-1">Tinjau dan verifikasi toko yang terdaftar di platform</p>
    </div>

    <Message v-if="isError" severity="error" class="mb-4">{{ errorMessage }}</Message>

    <AdminStoreFilter v-model:activeTab="activeTab" :pendingCount="pendingCount" />

    <AdminStoreTable :shops="filteredShops" @viewDetail="openDetail" @approve="openApprove" @reject="openReject" />

    <AdminStoreDetailModal v-model:visible="showDetailModal" :shop="selectedShop" @approve="executeApprove" />
    <AdminStoreApproveModal v-model:visible="showApproveModal" :shop="selectedShop" @confirm="executeApprove" />
    <AdminStoreRejectModal v-model:visible="showRejectModal" :shop="selectedShop" @confirm="executeReject" />
  </div>
</template>
