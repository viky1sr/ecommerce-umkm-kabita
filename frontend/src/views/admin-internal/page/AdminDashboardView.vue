<script setup lang="ts">
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import AdminPendingVerifications from '../components/dashboard/AdminDashboardPendingVerifications.vue'
import AdminRecentTransactions from '../components/dashboard/AdminDashboardRecentTransactions.vue'
import AdminRevenueChart from '../components/dashboard/AdminDashboardRevenueChart.vue'
import AdminStatCards from '../components/dashboard/AdminDashboardStatCards.vue'
import AdminTopPerformers from '../components/dashboard/AdminDashboardTopPerformers.vue'
import { adminAnalyticsService } from '@/services/adminAnalyticsService'
import { adminShopService } from '@/services/adminShopService'
import { adminOrderService } from '@/services/adminOrderService'

import type { Order, PlatformStats, Shop, TopProduct, TopSeller } from '@/types/entities'

const toast = useToast()
const isLoading = ref(true)

const platformStats = ref<PlatformStats | null>(null)

const pendingShops = ref<Partial<Shop>[]>([])

const recentOrders = ref<Partial<Order>[]>([])

const topSellers = ref<TopSeller[]>([])

const topProducts = ref<TopProduct[]>([])

// Handler Verifikasi Toko
const handleVerifyShop = async (shopId: number) => {
  await adminShopService.verify(shopId)
  pendingShops.value = pendingShops.value.filter((s) => s.id !== shopId)
  toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Toko telah diverifikasi', life: 3000 })
}

const handleRejectShop = async (shopId: number) => {
  await adminShopService.reject(shopId, { rejection_reason: 'Ditolak oleh admin' })
  pendingShops.value = pendingShops.value.filter((s) => s.id !== shopId)
  toast.add({ severity: 'warn', summary: 'Ditolak', detail: 'Pengajuan toko telah ditolak', life: 3000 })
}

onMounted(async () => {
  try {
    const [stats, shops, sellers, products, orders] = await Promise.all([
      adminAnalyticsService.getPlatformStats(),
      adminShopService.listPending({ per_page: 100 }),
      adminAnalyticsService.getTopSellers(5),
      adminAnalyticsService.getTopProducts(5),
      adminOrderService.list({ per_page: 5 }),
    ])
    platformStats.value = stats
    pendingShops.value = shops.data
    topSellers.value = sellers as unknown as TopSeller[]
    topProducts.value = products as unknown as TopProduct[]
    recentOrders.value = orders.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal memuat dashboard', detail: 'Data dashboard tidak dapat diambil dari API.', life: 4000 })
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div>
    <div v-if="isLoading" class="min-h-[60vh] flex flex-col items-center justify-center gap-3">
      <ProgressSpinner style="width: 48px; height: 48px" strokeWidth="4" />
      <span class="text-xs font-semibold text-slate-500">Memuat data Dashboard Admin...</span>
    </div>

    <div v-else class="space-y-8">
      <AdminStatCards :stats="platformStats" />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <AdminRevenueChart :monthlyTransactions="platformStats.monthly_transactions" />
        </div>
        <div>
          <AdminPendingVerifications :pendingShops="pendingShops" @verify="handleVerifyShop"
            @reject="handleRejectShop" />
        </div>
      </div>

      <AdminRecentTransactions :orders="recentOrders" />

      <AdminTopPerformers :topSellers="topSellers" :topProducts="topProducts" />
    </div>
  </div>
</template>
