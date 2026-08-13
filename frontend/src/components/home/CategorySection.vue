<script setup lang="ts">
import { useCategoryStore } from '@/stores/category';
import { onMounted } from 'vue';

const categoryStore = useCategoryStore()
onMounted(() => categoryStore.load())
</script>

<template>
  <section class="container max-w-7xl mx-auto px-4">
    <div class="overflow-hidden rounded-4xl bg-white px-4 py-6 shadow-sm">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-slate-950">Kategori</h2>
          <p class="text-sm text-slate-500">Pilih kategori produk UMKM yang Anda butuhkan.</p>
        </div>
        <router-link to="/produk" class="text-sky-600 font-semibold no-underline hover:text-sky-700">Lihat
          Semua</router-link>
      </div>

      <div class="flex gap-4 overflow-x-auto pb-2">
        <div v-for="category in categoryStore.categories" :key="category.id" class="min-w-20 shrink-0 px-2">
          <router-link :to="`/kategori/${category.slug}`"
            class="flex flex-col items-center gap-3 rounded-[1.5rem] border border-[#e1e2ed] bg-white px-4 py-4 text-center no-underline shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
            <span
              class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-surface-container text-[#191b23] text-2xl">
              <span>{{ category.icon || '🏷️' }}</span>
            </span>
            <span class="text-[0.75rem] font-semibold text-slate-900">{{ category.name }}</span>
          </router-link>
        </div>
      </div>
    </div>
  </section>
</template>
