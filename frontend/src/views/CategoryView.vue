<script setup lang="ts">
import ProductGrid from '@/components/home/ProductGrid.vue'
import { getApiErrorMessage } from '@/services/apiError'
import { categoryService } from '@/services/categoryService'
import type { Category, Product } from '@/types'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const category = ref<Category | null>(null)
const search = ref('')
const loading = ref(true)
const errorMessage = ref('')
const allProducts = ref<Product[]>([])

const products = computed(() => {
  const query = search.value.trim().toLowerCase()
  return query ? allProducts.value.filter((product) => `${product.name} ${product.description || ''}`.toLowerCase().includes(query)) : allProducts.value
})

const fetchCategory = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await categoryService.getBySlug(String(route.params.slug))
    category.value = response.data
    allProducts.value = (response.data.products || []).filter((product) => product.status === 'approved')
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Kategori tidak dapat dimuat.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchCategory)
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
      <div v-if="loading" class="flex flex-col items-center justify-center rounded-2xl bg-white py-24 shadow-sm"><ProgressSpinner /><span class="mt-3 text-sm text-slate-500">Memuat kategori...</span></div>
      <Message v-else-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <template v-else-if="category">
        <div class="rounded-3xl bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white shadow-sm sm:p-8"><p class="text-sm font-semibold text-blue-100">Kategori produk</p><h1 class="mt-2 text-3xl font-bold">{{ category.name }}</h1><p class="mt-2 max-w-2xl text-sm text-blue-100">{{ category.description || 'Temukan produk pilihan dari kategori ini.' }}</p><p class="mt-4 text-xs text-blue-100">{{ allProducts.length }} produk tersedia</p></div>
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div class="relative w-full sm:w-96"><i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i><InputText v-model="search" placeholder="Cari di kategori ini..." class="w-full rounded-xl! pl-9!" /></div><span class="text-sm text-slate-500">{{ products.length }} produk ditemukan</span></div>
        <ProductGrid v-if="products.length" :products="products" />
        <div v-else class="rounded-2xl bg-white py-20 text-center shadow-sm"><i class="pi pi-search text-3xl text-slate-300"></i><h2 class="mt-4 font-bold text-slate-800">Produk tidak ditemukan</h2><p class="mt-1 text-sm text-slate-500">Coba kata kunci lain dalam kategori ini.</p><Button v-if="search" label="Hapus pencarian" text class="mt-3" @click="search = ''" /></div>
      </template>
    </div>
  </div>
</template>
