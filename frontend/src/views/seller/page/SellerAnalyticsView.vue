<script setup lang="ts">
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import Dropdown from 'primevue/dropdown'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref, watch } from 'vue'

import type { SellerTopProduct } from '@/types/entities'
import { getApiErrorMessage } from '@/services/apiError'
import { sellerAnalyticsService } from '@/services/sellerAnalyticsService'
import { formatRupiah } from '@/utils/format'
import { useRouter } from 'vue-router'

const router = useRouter()
const toast = useToast()

const isLoadingGet = ref(true)
const errorMessage = ref('')
const selectedPeriod = ref('month')

const periodOptions = [
  { label: 'Periode: Hari Ini', value: 'today' },
  { label: 'Periode: 7 Hari Terakhir', value: 'week' },
  { label: 'Periode: Bulan Ini', value: 'month' },
  { label: 'Periode: Tahun Ini', value: 'year' }
]

const overviewMetrics = ref({
  totalRevenue: 'Rp0', revenueGrowth: '', totalOrders: '0 Order', ordersGrowth: '',
  productsSold: '0 Unit', conversionRate: '-'
})

const topProducts = ref<SellerTopProduct[]>([])
const salesRows = ref<Array<{ date: string; total_revenue: string; orders_count?: number }>>([])

// PrimeVue Chart Configs
const barChartData = ref()
const barChartOptions = ref()
const donutChartData = ref()
const donutChartOptions = ref()

// ----------------------------------------------------------------
// 1. SETUP DATA & PRIMEVUE V4 CHART CONFIG
// ----------------------------------------------------------------
const initCharts = (sales: Array<{ date: string; total_revenue: string }>) => {
  // Bar Chart Data (Grafik Penjualan 30 Hari Terakhir)
  barChartData.value = {
    labels: sales.map((row) => row.date),
    datasets: [
      {
        label: 'Pendapatan (Rp)',
        backgroundColor: '#3b82f6',
        borderRadius: 6,
        data: sales.map((row) => Number(row.total_revenue))
      }
    ]
  }

  barChartOptions.value = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: '#94a3b8' }
      },
      y: {
        grid: { color: '#f1f5f9' },
        ticks: { display: false }
      }
    }
  }

  // Kategori belum tersedia pada endpoint seller; jangan tampilkan data rekaan.
  donutChartData.value = {
    labels: [], datasets: [{ data: [], backgroundColor: [], borderWidth: 0 }]
  }

  donutChartOptions.value = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '75%',
    plugins: {
      legend: { display: false }
    }
  }
}

// ----------------------------------------------------------------
const fetchAnalyticsData = async () => {
  isLoadingGet.value = true
  try {
    const [overview, sales, products] = await Promise.all([
      sellerAnalyticsService.getOverview(),
      sellerAnalyticsService.getSales({ period: selectedPeriod.value === 'year' ? 'monthly' : 'daily' }),
      sellerAnalyticsService.getTopProducts(),
    ])
    overviewMetrics.value = {
      totalRevenue: formatRupiah(overview.total_revenue), revenueGrowth: `${overview.pending_orders_count} pesanan menunggu`,
      totalOrders: `${overview.total_orders} Order`, ordersGrowth: 'Pesanan bulan berjalan',
      productsSold: `${products.reduce((sum, item) => sum + item.total_sold, 0)} Unit`, conversionRate: `${overview.total_products} Produk aktif`,
    }
    topProducts.value = products
    salesRows.value = sales
    initCharts(sales)
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Data analitik toko gagal dimuat.')
  } finally {
    isLoadingGet.value = false
  }
}

// ----------------------------------------------------------------
// 3. EXPORT LAPORAN & NAVIGASI
// ----------------------------------------------------------------
const handleExportReport = () => {
  const csv = [['Tanggal', 'Pendapatan', 'Jumlah Pesanan'], ...salesRows.value.map((row) => [row.date, row.total_revenue, String(row.orders_count ?? 0)])]
    .map((row) => row.join(',')).join('\n')
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = `laporan-penjualan-${selectedPeriod.value}.csv`
  link.click()
  URL.revokeObjectURL(url)
  toast.add({ severity: 'success', summary: 'Export berhasil', detail: 'Laporan penjualan berhasil diunduh.', life: 3000 })
}

const goToTopProductsPage = () => {
  router.push('/seller/analitik/top-products')
}

watch(selectedPeriod, () => {
  fetchAnalyticsData()
})

onMounted(() => {
  fetchAnalyticsData()
})
</script>

<template>
  <div class="relative min-h-[80vh]">
    <Transition name="fade">
      <div v-if="isLoadingGet"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm">
        <ProgressSpinner style="width: 56px; height: 56px" strokeWidth="4" fill="transparent" animationDuration=".8s" />
        <p class="mt-4 font-medium text-slate-600 text-sm animate-pulse">
          Memuat Laporan & Analitik Penjualan...
        </p>
      </div>
    </Transition>

    <Message v-if="errorMessage" severity="error" class="mx-auto mb-4 max-w-6xl">{{ errorMessage }}</Message>

    <div v-if="!isLoadingGet" class="max-w-6xl mx-auto space-y-6 pb-12">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Ringkasan Performa Toko</h1>
          <p class="text-xs text-slate-500 mt-1">
            Pantau metrik penjualan dan perilaku pembeli secara real-time.
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <Dropdown v-model="selectedPeriod" :options="periodOptions" optionLabel="label" optionValue="value"
            class="bg-white! border-slate-200! rounded-xl! text-xs!" />
          <Button label="Export Laporan" icon="pi pi-download" outlined
            class="border-blue-600! text-blue-600! hover:bg-blue-50! rounded-xl! text-xs! font-semibold! py-2.5!"
            @click="handleExportReport" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Pendapatan</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <i class="pi pi-wallet text-sm"></i>
            </div>
          </div>
          <div class="mt-4">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ overviewMetrics.totalRevenue }}</h3>
            <span
              class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md mt-2">
              <i class="pi pi-arrow-up-right text-[10px]"></i> {{ overviewMetrics.revenueGrowth }}
            </span>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Pesanan</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
              <i class="pi pi-shopping-bag text-sm"></i>
            </div>
          </div>
          <div class="mt-4">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ overviewMetrics.totalOrders }}</h3>
            <span
              class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md mt-2">
              <i class="pi pi-arrow-up-right text-[10px]"></i> {{ overviewMetrics.ordersGrowth }}
            </span>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Produk Terjual</span>
            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
              <i class="pi pi-box text-sm"></i>
            </div>
          </div>
          <div class="mt-4">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ overviewMetrics.productsSold }}</h3>
            <p class="text-[11px] text-slate-400 mt-2">Total unit produk sukses terkirim</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tingkat Konversi</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
              <i class="pi pi-chart-line text-sm"></i>
            </div>
          </div>
          <div class="mt-4">
            <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ overviewMetrics.conversionRate }}</h3>
            <p class="text-[11px] text-slate-400 mt-2">Pengunjung yang melakukan transaksi</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div
          class="lg:col-span-8 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-slate-900">Grafik Penjualan 30 Hari Terakhir</h2>
            <div class="flex items-center gap-4 text-xs font-medium">
              <span class="flex items-center gap-1.5 text-slate-600">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Pendapatan
              </span>
            </div>
          </div>

          <div class="h-64 relative">
            <Chart type="bar" :data="barChartData" :options="barChartOptions" class="h-full w-full" />
          </div>
        </div>

        <div
          class="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900 mb-2">Kategori Terlaris</h2>
            <div class="flex h-44 items-center justify-center text-center text-xs text-slate-400">
              <div><i class="pi pi-chart-pie mb-2 text-2xl"></i><p>Data kategori belum tersedia.</p></div>
            </div>
          </div>

        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-6 flex items-center justify-between border-b border-slate-100">
          <h2 class="text-base font-bold text-slate-900">Top 5 Produk Terlaris</h2>
          <button @click="goToTopProductsPage"
            class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-1">
            <span>Lihat Semua</span>
            <i class="pi pi-chevron-right text-[10px]"></i>
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead
              class="bg-slate-50/70 text-slate-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-100">
              <tr>
                <th class="py-3.5 px-6">Produk</th>
                <th class="py-3.5 px-4">Kategori</th>
                <th class="py-3.5 px-4 text-center">Terjual</th>
                <th class="py-3.5 px-4">Pendapatan</th>
                <th class="py-3.5 px-6 text-center">Stok</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in topProducts" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="py-4 px-6 font-semibold text-slate-800">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                      <i class="pi pi-box text-slate-400"></i>
                    </div>
                    <div>
                      <p class="text-xs font-bold text-slate-800">{{ item.name }}</p>
                      <p class="text-[10px] text-slate-400">SKU: {{ item.id }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-slate-500">{{ item.category?.name || '—' }}</td>
                <td class="py-4 px-4 text-center font-bold text-slate-800">{{ item.total_sold }}</td>
                <td class="py-4 px-4 font-bold text-blue-600">{{ formatRupiah(item.total_revenue) }}</td>
                <td class="py-4 px-6 text-center">
                  <span class="text-slate-500">{{ item.stock ?? '—' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
