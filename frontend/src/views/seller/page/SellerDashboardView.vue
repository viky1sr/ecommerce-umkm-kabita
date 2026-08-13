<script setup lang="ts">
import type { AnalyticsSalesRow, Order, SellerOverview } from '@/types'
import Button from 'primevue/button'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { sellerAnalyticsService } from '@/services/sellerAnalyticsService'
import { sellerOrderService } from '@/services/sellerOrderService'
import { getApiErrorMessage } from '@/services/apiError'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()
const isLoading = ref<boolean>(true)

// Stats Overview berdasarkan interface SellerOverview
const sellerOverview = ref<SellerOverview>({ total_products: 0, total_orders: 0, total_revenue: '0', pending_orders_count: 0 })

// Rating Toko (Properti opsional/display)
const shopRating = ref({ score: 0, percentile: 'Belum ada rating', productGrowth: 'Data aktual', orderGrowth: 'Data aktual' })

// Sales Chart Data berdasarkan AnalyticsSalesRow
const salesAnalytics = ref<AnalyticsSalesRow[]>([])

// Pesanan Terbaru (Recent Orders Display)
const recentActivity = ref<any[]>([])

// Pesanan Tertunda berdasarkan interface Order
const pendingOrders = ref<Order[]>([])
/* const legacyPendingOrders = ref<Order[]>([
  {
    id: 101,
    order_number: 'INV-2024-001',
    buyer_id: 12,
    shop_id: 1,
    subtotal: '150000',
    shipping_cost: '15000',
    total_amount: '165000',
    shipping_method: 'Kurir Regular',
    payment_method: 'Transfer Bank',
    status: 'pending',
    shipping_address: 'Jakarta',
    tracking_number: null,
    notes: null,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    items: [
      {
        id: 1,
        order_id: 101,
        product_id: 10,
        quantity: 2,
        price_snapshot: '75000',
        cost_snapshot: '50000',
        created_at: '',
        updated_at: '',
        product: {
          id: 10,
          shop_id: 1,
          category_id: 2,
          name: 'Kopi Luwak Premium (2 pcs)',
          slug: 'kopi-luwak-premium',
          description: '',
          price: '75000',
          cost_price: '50000',
          stock: 50,
          weight: 500,
          status: 'active',
          verified_at: null,
          rejection_reason: null,
          created_at: ''
        }
      }
    ]
  },
  {
    id: 102,
    order_number: 'INV-2024-004',
    buyer_id: 15,
    shop_id: 1,
    subtotal: '2500000',
    shipping_cost: '100000',
    total_amount: '2600000',
    shipping_method: 'Cargo',
    payment_method: 'Transfer Bank',
    status: 'pending',
    shipping_address: 'Bali',
    tracking_number: null,
    notes: null,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    items: [
      {
        id: 2,
        order_id: 102,
        product_id: 11,
        quantity: 1,
        price_snapshot: '2500000',
        cost_snapshot: '2000000',
        created_at: '',
        updated_at: '',
        product: {
          id: 11,
          shop_id: 1,
          category_id: 5,
          name: 'Rotan Chair Set (1 unit)',
          slug: 'rotan-chair-set',
          description: '',
          price: '2500000',
          cost_price: '2000000',
          stock: 5,
          weight: 15000,
          status: 'active',
          verified_at: null,
          rejection_reason: null,
          created_at: ''
        }
      }
    ]
  }
]) */

onMounted(async () => {
  try {
    const [overview, sales, orders] = await Promise.all([
      sellerAnalyticsService.getOverview(), sellerAnalyticsService.getSales({ period: 'daily' }), sellerOrderService.list({ status: 'pending', per_page: 5 })
    ])
    sellerOverview.value = overview
    salesAnalytics.value = sales as AnalyticsSalesRow[]
    pendingOrders.value = orders.data
    recentActivity.value = orders.data.slice(0, 4).map((order) => ({ id: order.id, title: order.order_number, location: order.shipping_address, time: order.created_at, icon: 'pi pi-shopping-bag', color: 'text-blue-600 bg-blue-100' }))
  } catch (error) { toast.add({ severity: 'error', summary: 'Dashboard gagal dimuat', detail: getApiErrorMessage(error), life: 3500 }) }
  finally { isLoading.value = false }
})

const handleProcessOrder = async (orderId: number) => {
  try { await sellerOrderService.process(orderId); pendingOrders.value = pendingOrders.value.filter((order) => order.id !== orderId); toast.add({ severity: 'success', summary: 'Pesanan diproses', detail: 'Status pesanan diperbarui.', life: 2500 }) }
  catch (error) { toast.add({ severity: 'error', summary: 'Gagal memproses', detail: getApiErrorMessage(error), life: 3500 }) }
}

const handleOrderDetail = (orderId: number) => {
  router.push(`/seller/orders/${orderId}`)
}
</script>

<template>
  <div class="space-y-6">

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20">
      <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
      <p class="text-slate-500 text-sm mt-3">Memuat data dashboard...</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <i class="pi pi-box text-lg"></i>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600">
              {{ shopRating.productGrowth }}
            </span>
          </div>
          <div class="mt-4">
            <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ sellerOverview.total_products }}</h3>
            <p class="text-xs text-slate-400 font-medium mt-1">Total Produk</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <i class="pi pi-shopping-cart text-lg"></i>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600">
              {{ shopRating.orderGrowth }}
            </span>
          </div>
          <div class="mt-4">
            <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ sellerOverview.total_orders }}</h3>
            <p class="text-xs text-slate-400 font-medium mt-1">Pesanan Baru</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-md shadow-blue-500/20 text-white flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <i class="pi pi-wallet text-lg text-blue-500"></i>
            </div>
          </div>
          <div class="mt-4">
            <h3 class="text-3xl text-black font-extrabold tracking-tight">{{ sellerOverview.total_revenue }}</h3>
            <p class="text-xs text-slate-400 font-medium mt-1">Pendapatan Hari Ini</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <i class="pi pi-star text-lg"></i>
            </div>
            <span class="text-xs font-semibold text-slate-500">
              {{ shopRating.percentile }}
            </span>
          </div>
          <div class="mt-4">
            <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ shopRating.score }}</h3>
            <p class="text-xs text-slate-400 font-medium mt-1">Rating Toko</p>
          </div>
        </div>

      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <div
          class="lg:col-span-8 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-base font-bold text-slate-900">Performa Penjualan (7 Hari)</h2>
            <div
              class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold flex items-center gap-2 cursor-pointer hover:bg-slate-200 transition-colors">
              <span>7 Hari Terakhir</span>
              <i class="pi pi-chevron-down text-xs"></i>
            </div>
          </div>

          <div class="h-64 flex items-end justify-between gap-3 pt-6 px-2">
            <div v-for="(row, idx) in salesAnalytics" :key="idx"
              class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
              <div class="w-full max-w-13.5 bg-slate-100 rounded-t-lg relative overflow-hidden flex items-end h-full">
                <div :class="[
                  'w-full rounded-t-lg transition-all duration-500 group-hover:opacity-90',
                  idx === 4 ? 'bg-blue-600' : 'bg-blue-400/80'
                ]" :style="{ height: `${row.orders_count}%` }"></div>
              </div>
              <span class="text-xs font-medium text-slate-400 mt-1">{{ row.date }}</span>
            </div>
          </div>
        </div>

        <div
          class="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900 mb-4">Pesanan Terbaru</h2>
            <div class="space-y-4">
              <div v-for="item in recentActivity" :key="item.id" class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-3">
                  <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0', item.color]">
                    <i :class="item.icon"></i>
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-800 text-xs sm:text-sm line-clamp-1">{{ item.title }}</h4>
                    <p class="text-[11px] text-slate-400">{{ item.location }} • {{ item.time }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-2">
            <button @click="router.push('/seller/orders')"
              class="w-full py-2.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-xl transition-colors border border-blue-100 text-center">
              Lihat Semua Pesanan
            </button>
          </div>
        </div>

      </div>

      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-bold text-slate-900">Perlu Tindakan: Pesanan Tertunda</h2>
          <button @click="router.push('/seller/orders?status=pending')"
            class="text-xs font-semibold text-blue-600 hover:underline">
            Lihat Semua
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="order in pendingOrders" :key="order.id"
            class="p-4 border border-slate-100 rounded-xl bg-slate-50/50 flex flex-col justify-between">
            <div class="flex items-start gap-3.5">
              <div
                class="w-10 h-10 bg-slate-200/70 text-slate-600 rounded-lg flex items-center justify-center shrink-0">
                <i class="pi pi-box text-lg"></i>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm">Order #{{ order.order_number }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ order.items?.[0]?.product?.name || 'Produk' }} • {{ order.shipping_address }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3 mt-4">
              <Button label="Proses Pesanan" @click="handleProcessOrder(order.id)"
                class="flex-1 bg-blue-600! hover:bg-blue-700! border-none! py-2! rounded-lg! text-xs! font-semibold!" />
              <Button label="Detail" variant="outlined" @click="handleOrderDetail(order.id)"
                class="flex-1 border-slate-300! text-slate-700! hover:bg-slate-100! py-2! rounded-lg! text-xs! font-semibold!" />
            </div>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>
