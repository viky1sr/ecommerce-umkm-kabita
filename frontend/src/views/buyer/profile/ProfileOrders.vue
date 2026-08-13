<script setup lang="ts">
import { buyerOrderService } from '@/services/buyerOrderService'
import { getApiErrorMessage } from '@/services/apiError'
import type { Order, OrderStatus } from '@/types'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const orders = ref<Order[]>([])
const searchQuery = ref('')
const isLoading = ref(true)
const errorMessage = ref('')

const filteredOrders = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return orders.value
  return orders.value.filter((order) => [
    order.order_number,
    order.shop?.name,
    order.status,
  ].some((value) => String(value ?? '').toLowerCase().includes(query)))
})

const loadOrders = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await buyerOrderService.list({ sort: 'newest', per_page: 50 })
    orders.value = response.data
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Pesanan belum dapat dimuat.')
  } finally {
    isLoading.value = false
  }
}

const formatCurrency = (amount: string | number) => new Intl.NumberFormat('id-ID', {
  style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(amount) || 0)

const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', {
  day: 'numeric', month: 'long', year: 'numeric',
})

const getSeverity = (status: OrderStatus) => ({
  pending: 'warn', processing: 'info', shipped: 'info', delivered: 'success',
  completed: 'success', cancelled: 'danger',
}[status] || 'secondary')

const getStatusLabel = (status: OrderStatus) => ({
  pending: 'Menunggu pembayaran', processing: 'Diproses', shipped: 'Dikirim',
  delivered: 'Selesai', completed: 'Selesai', cancelled: 'Dibatalkan',
}[status] || status)

onMounted(loadOrders)
</script>

<template>
  <section class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
    <header class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Pesanan Saya</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau status pesanan dan riwayat belanja Anda.</p>
      </div>
      <div class="relative w-full sm:w-72">
        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <InputText v-model="searchQuery" placeholder="Cari nomor pesanan atau toko..." class="w-full rounded-xl! py-2.5! pl-9! text-sm!" />
      </div>
    </header>

    <div v-if="isLoading" class="flex flex-col items-center justify-center gap-3 py-20 text-slate-500">
      <ProgressSpinner style="width: 42px; height: 42px" />
      <span class="text-sm">Memuat pesanan...</span>
    </div>
    <div v-else-if="errorMessage" class="py-16 text-center">
      <p class="text-sm text-rose-600">{{ errorMessage }}</p>
      <Button label="Coba lagi" icon="pi pi-refresh" outlined class="mt-4 rounded-xl!" @click="loadOrders" />
    </div>
    <div v-else-if="filteredOrders.length === 0" class="py-16 text-center">
      <i class="pi pi-receipt text-4xl text-slate-300"></i>
      <h2 class="mt-4 font-bold text-slate-800">Pesanan tidak ditemukan</h2>
      <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau mulai belanja produk UMKM.</p>
      <Button label="Mulai belanja" icon="pi pi-shopping-bag" class="mt-5 rounded-xl!" @click="router.push('/produk')" />
    </div>
    <div v-else class="mt-5 space-y-4">
      <article v-for="order in filteredOrders" :key="order.id" class="rounded-2xl border border-slate-100 p-4 transition hover:border-blue-200 hover:shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 text-xs">
          <div class="flex flex-wrap items-center gap-2 text-slate-500">
            <span class="font-bold text-slate-800">{{ order.shop?.name || 'Toko Kabita' }}</span>
            <span>•</span><span>{{ formatDate(order.created_at) }}</span>
            <span>•</span><span>{{ order.order_number }}</span>
          </div>
          <Tag :value="getStatusLabel(order.status)" :severity="getSeverity(order.status)" />
        </div>
        <div v-for="item in order.items" :key="item.id" class="flex min-w-0 items-center gap-3 py-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-[10px] text-slate-400">Produk</div>
          <div class="min-w-0 flex-1">
            <h3 class="truncate text-sm font-semibold text-slate-800">{{ item.product?.name || 'Produk' }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ item.quantity }} barang × {{ formatCurrency(item.price_snapshot) }}</p>
          </div>
          <span class="shrink-0 text-sm font-bold text-slate-800">{{ formatCurrency(Number(item.price_snapshot) * item.quantity) }}</span>
        </div>
        <footer class="flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
          <span class="text-sm text-slate-500">Total <strong class="text-slate-900">{{ formatCurrency(order.total_amount) }}</strong></span>
          <Button label="Lihat detail" icon="pi pi-arrow-right" iconPos="right" text class="rounded-xl!" @click="router.push(`/order-detail?id=${order.id}`)" />
        </footer>
      </article>
    </div>
  </section>
</template>
