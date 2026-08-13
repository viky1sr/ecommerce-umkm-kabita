<script setup lang="ts">
import ProductGrid from '@/components/home/ProductGrid.vue'
import { getApiErrorMessage } from '@/services/apiError'
import { publicProductService } from '@/services/publicProductService'
import type { Product } from '@/types'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const products = ref<Product[]>([])
const search = ref(String(route.query.search || ''))
const sort = ref<'newest' | 'price_asc' | 'price_desc'>('newest')
const page = ref(1)
const perPage = 12
const total = ref(0)
const lastPage = ref(1)
const loading = ref(true)
const errorMessage = ref('')
const sortOptions = [{ label: 'Terbaru', value: 'newest' }, { label: 'Harga terendah', value: 'price_asc' }, { label: 'Harga tertinggi', value: 'price_desc' }]

const fetchProducts = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await publicProductService.list({ search: search.value.trim() || undefined, sort: sort.value, per_page: perPage, page: page.value })
    products.value = result.data
    total.value = result.meta.total
    lastPage.value = result.meta.last_page
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Produk tidak dapat dimuat.')
  } finally {
    loading.value = false
  }
}

const applySearch = () => {
  page.value = 1
  router.replace({ query: search.value.trim() ? { search: search.value.trim() } : {} })
  fetchProducts()
}
const changeSort = () => { page.value = 1; fetchProducts() }
const changePage = (value: number) => { page.value = value; fetchProducts() }

watch(() => route.query.search, (value) => {
  const next = String(value || '')
  if (next !== search.value) { search.value = next; page.value = 1; fetchProducts() }
})
onMounted(fetchProducts)
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
      <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><h1 class="text-2xl font-bold text-slate-900">Produk UMKM</h1><p class="mt-1 text-sm text-slate-500">Temukan produk pilihan dari penjual lokal.</p></div>
        <div class="flex w-full gap-2 sm:w-auto">
          <div class="relative flex-1 sm:w-72"><i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i><InputText v-model="search" placeholder="Cari produk..." class="w-full rounded-xl! pl-9!" @keyup.enter="applySearch" /></div>
          <Button icon="pi pi-search" aria-label="Cari produk" class="rounded-xl!" @click="applySearch" />
        </div>
      </div>
      <div class="flex flex-wrap items-center justify-between gap-3"><span class="text-sm text-slate-500">{{ total.toLocaleString('id-ID') }} produk ditemukan</span><Select v-model="sort" :options="sortOptions" option-label="label" option-value="value" class="w-48" @update:model-value="changeSort" /></div>
      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <div v-if="loading" class="flex flex-col items-center justify-center rounded-2xl bg-white py-24 shadow-sm"><ProgressSpinner /><span class="mt-3 text-sm text-slate-500">Memuat produk...</span></div>
      <template v-else><ProductGrid v-if="products.length" :products="products" /><div v-else class="rounded-2xl bg-white py-20 text-center shadow-sm"><i class="pi pi-search text-3xl text-slate-300"></i><h2 class="mt-4 font-bold text-slate-800">Produk tidak ditemukan</h2><p class="mt-1 text-sm text-slate-500">Coba gunakan kata kunci lain.</p></div></template>
      <div v-if="lastPage > 1" class="flex items-center justify-center gap-3"><Button icon="pi pi-chevron-left" text rounded :disabled="page <= 1" @click="changePage(page - 1)" /><span class="text-sm font-semibold text-slate-600">Halaman {{ page }} dari {{ lastPage }}</span><Button icon="pi pi-chevron-right" text rounded :disabled="page >= lastPage" @click="changePage(page + 1)" /></div>
    </div>
  </div>
</template>
