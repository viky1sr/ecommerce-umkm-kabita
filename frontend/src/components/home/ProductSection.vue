<script setup lang="ts">
import ProductGrid from '@/components/home/ProductGrid.vue';
import { publicProductService } from '@/services/publicProductService';
import type { Product } from '@/types';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import ProgressSpinner from 'primevue/progressspinner';

const isLoadingGet = ref(true)
const listProduct = ref<Product[]>([]);
const toast = useToast()

async function getProduct() {
  try {
    const response = await publicProductService.listAtHome();

    if (response.success && response.data) {
      listProduct.value = response.data;
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: `Gagal memuat barang`,
      life: 3000,
    })
  } finally {
    isLoadingGet.value = false;
  }
}

onMounted(() => {
  getProduct();
})

</script>

<template>
  <section class="container max-w-7xl mx-auto px-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-950">Semua Produk</h2>
        <p class="text-sm text-slate-500">Temukan pilihan produk UMKM spesial hari ini.</p>
      </div>
      <router-link to="/produk" class="text-sky-600 font-semibold no-underline hover:text-sky-700">Lihat
        Semua</router-link>
    </div>

    <Transition name="fade">
      <div v-if="isLoadingGet"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm">
        <ProgressSpinner style="width: 56px; height: 56px" strokeWidth="4" fill="transparent" animationDuration=".8s"
          aria-label="Loading Daftar Barang" />
      </div>
    </Transition>

    <div v-if="!isLoadingGet" class="w-full h-fit">
      <ProductGrid :products="listProduct" />
    </div>

  </section>
</template>
