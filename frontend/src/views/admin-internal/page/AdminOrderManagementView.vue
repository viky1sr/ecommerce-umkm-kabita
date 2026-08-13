<script setup lang="ts">
import ProgressSpinner from 'primevue/progressspinner'
import { computed, onMounted, ref } from 'vue'
import { adminOrderService } from '@/services/adminOrderService'
import { getApiErrorMessage } from '@/services/apiError'
import { useToast } from 'primevue/usetoast'

import type { Order } from '@/types/entities'
import AdminOrderDetailModal from '../components/order/AdminOrderDetailModal.vue'
import AdminOrderFilter from '../components/order/AdminOrderFilter.vue'
import AdminOrderTable from '../components/order/AdminOrderTable.vue'

// States
const isLoading = ref(true)
const activeTab = ref('all')
const selectedOrder = ref<Partial<Order> | null>(null)
const showDetailModal = ref(false)
const toast = useToast()

const orders = ref<Partial<Order>[]>([])

// Computed Counts per Status
const statusCounts = computed(() => {
  const counts: Record<string, number> = { all: orders.value.length }
  orders.value.forEach((o) => {
    if (o.status) {
      counts[o.status] = (counts[o.status] || 0) + 1
    }
  })
  return counts
})

// Filtered Orders
const filteredOrders = computed(() => {
  if (activeTab.value === 'all') return orders.value
  return orders.value.filter((o) => o.status === activeTab.value)
})

// Handlers
const openDetail = (order: Partial<Order>) => {
  selectedOrder.value = order
  showDetailModal.value = true
}

onMounted(async () => {
  try { orders.value = (await adminOrderService.list({ per_page: 100 })).data }
  catch (error) { toast.add({ severity: 'error', summary: 'Gagal memuat pesanan', detail: getApiErrorMessage(error), life: 3500 }) }
  finally { isLoading.value = false }
})
</script>

<template>
  <div class="relative min-h-[80vh]">

    <div v-if="isLoading"
      class="fixed inset-0 z-100 flex flex-col items-center justify-center bg-white/90 backdrop-blur-xs transition-all duration-300">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
      <span class="mt-4 text-sm font-semibold text-slate-600 animate-pulse">Memuat data transaksi pesanan...</span>
    </div>

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Manajemen Pesanan</h1>
      <p class="text-sm text-slate-500 mt-1">Kelola dan pantau seluruh transaksi pesanan di platform Kabita</p>
    </div>

    <AdminOrderFilter v-model:activeTab="activeTab" :counts="statusCounts" />

    <AdminOrderTable :orders="filteredOrders" @viewDetail="openDetail" />

    <AdminOrderDetailModal v-model:visible="showDetailModal" :order="selectedOrder" />
  </div>
</template>
