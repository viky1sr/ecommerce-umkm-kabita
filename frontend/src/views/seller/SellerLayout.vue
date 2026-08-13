<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import ProgressSpinner from 'primevue/progressspinner'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import type { Shop } from '@/types/entities'
import { sellerShopService } from '@/services/sellerShopService'

const route = useRoute()
const authStore = useAuthStore()
const isLoading = ref(true)
const isSidebarOpen = ref(false)

const currentShop = ref<Partial<Shop>>({})

const sidebarMenus = ref([
  { label: 'Dashboard', slug: 'dashboard', icon: 'pi pi-th-large' },
  { label: 'Produk', slug: 'produk', icon: 'pi pi-box' },
  { label: 'Pesanan', slug: 'pesanan', icon: 'pi pi-shopping-bag' },
  { label: 'Profil Toko', slug: 'profil-toko', icon: 'pi pi-shop' },
  { label: 'Analitik', slug: 'analitik', icon: 'pi pi-chart-bar' },
  { label: 'Pengaturan', slug: 'pengaturan', icon: 'pi pi-cog' }
])

const activeSlug = computed(() => {
  const routeSlug = (route.params.slug as string) || route.path.split('/')[2] || 'dashboard'
  return routeSlug === 'orders' ? 'pesanan' : routeSlug
})

const headerInfo = computed(() => {
  switch (activeSlug.value) {
    case 'analitik':
      return { title: 'Analitik Penjualan', subtitle: 'Pantau statistik dan statistik performa toko Anda.' }
    case 'produk':
      return { title: 'Kelola Produk', subtitle: 'Daftar dan stok produk yang tersedia di toko Anda.' }
    case 'pesanan':
      return { title: 'Daftar Pesanan', subtitle: 'Kelola transaksi dan pengiriman ke pembeli.' }
    case 'profil-toko':
      return { title: 'Profil Toko', subtitle: 'Pengaturan identitas dan tampilan toko Anda.' }
    case 'pengaturan':
      return { title: 'Pengaturan System', subtitle: 'Konfigurasi akun dan preference seller center.' }
    default:
      return { title: 'Ringkasan Toko', subtitle: 'Ringkasan performa toko Anda waktu nyata.' }
  }
})

const fetchSellerData = async () => {
  isLoading.value = true
  try { currentShop.value = await sellerShopService.getMyShop() }
  catch { currentShop.value = {} }
  finally { isLoading.value = false }
}

onMounted(() => {
  fetchSellerData()
})

const handleLogout = () => authStore.logout()

watch(() => route.path, () => {
  isSidebarOpen.value = false
})
</script>

<template>
  <div class="flex h-dvh min-h-0 w-full overflow-hidden bg-[#F8F9FA] font-sans text-slate-800">
    <button v-if="isSidebarOpen" type="button" aria-label="Tutup menu seller"
      class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden" @click="isSidebarOpen = false" />

    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 flex h-dvh w-72 max-w-[85vw] -translate-x-full flex-col justify-between overflow-hidden border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:static lg:z-20 lg:w-64 lg:translate-x-0',
        isSidebarOpen ? 'translate-x-0' : ''
      ]">
      <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <div class="p-6 text-center border-b border-slate-100">
          <div class="relative w-20 h-20 mx-auto mb-3">
            <template v-if="isLoading">
              <div class="w-20 h-20 rounded-full bg-slate-200 animate-pulse mx-auto"></div>
            </template>
            <template v-else>
              <img
                :src="currentShop.logo || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=250'"
                alt="Seller Profile"
                class="w-20 h-20 rounded-full object-cover shadow-sm border-2 border-white ring-2 ring-blue-500/20" />
            </template>
          </div>
          <h2 class="text-xl font-bold text-blue-600 leading-tight">Seller Center</h2>
          <p class="text-xs text-slate-400 mt-0.5">Shop Dashboard</p>
        </div>

        <nav class="p-4 space-y-1.5">
          <router-link v-for="menu in sidebarMenus" :key="menu.slug" :to="`/seller/${menu.slug}`" :class="[
            'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200',
            activeSlug === menu.slug
              ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20 font-semibold'
              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]">
            <i :class="[menu.icon, 'text-lg']"></i>
            <span>{{ menu.label }}</span>
          </router-link>
        </nav>
      </div>

      <div class="p-4 border-t border-slate-100 space-y-2 shrink-0 bg-white">
        <router-link to="/seller/help"
          class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-xl transition-colors">
          <i class="pi pi-question-circle text-lg"></i>
          <span>Pusat Bantuan</span>
        </router-link>

        <button type="button" @click="handleLogout"
          class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-xl transition-colors">
          <i class="pi pi-sign-out text-lg"></i>
          <span>Keluar</span>
        </button>

        <div class="pt-2">
          <Button label="Tambah Produk Baru" icon="pi pi-plus"
            class="w-full bg-blue-600! hover:bg-blue-700! border-none! py-3! rounded-xl! text-sm! font-semibold! shadow-sm!" />
        </div>
      </div>
    </aside>

    <div class="flex h-full min-w-0 flex-1 flex-col overflow-hidden">

      <header class="flex min-h-20 items-center justify-between gap-3 border-b border-slate-200/80 bg-white px-4 shrink-0 z-10 sm:px-6 lg:px-8">
        <div>
          <div class="flex items-center gap-3">
            <Button icon="pi pi-bars" text rounded aria-label="Buka menu seller"
              class="lg:hidden! text-slate-700!" @click="isSidebarOpen = true" />
            <div class="min-w-0">
              <h1 class="truncate text-lg font-bold text-slate-900 sm:text-2xl">{{ headerInfo.title }}</h1>
              <p class="hidden text-xs text-slate-500 mt-0.5 sm:block">{{ headerInfo.subtitle }}</p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
          <span class="relative hidden sm:inline-flex">
            <Button icon="pi pi-bell" text rounded class="!p-2.5 !bg-slate-100 hover:!bg-slate-200 !text-slate-600" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
          </span>

          <div class="relative hidden w-72 md:block">
            <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <InputText placeholder="Search system..."
              class="w-full! pl-10! pr-4! py-2! bg-slate-50/80! border-slate-200! rounded-full! text-sm! focus:bg-white! focus:ring-2! focus:ring-blue-500/20!" />
          </div>

          <div class="flex items-center gap-2 border-l border-slate-200 pl-2 sm:gap-2.5">
            <template v-if="isLoading">
              <div class="w-8 h-8 rounded-full bg-slate-200 animate-pulse"></div>
              <div class="w-20 h-4 bg-slate-200 rounded animate-pulse"></div>
            </template>
            <template v-else>
              <img
                :src="currentShop.logo || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100'"
                alt="Shop Logo" class="w-8 h-8 rounded-full object-cover" />
              <span class="hidden text-xs font-semibold text-slate-700 sm:block">{{ currentShop.name }}</span>
            </template>
          </div>
        </div>
      </header>

      <main class="min-h-0 min-w-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain p-4 sm:p-6 lg:p-8">
        <div v-if="isLoading" class="h-full min-h-100 flex flex-col items-center justify-center gap-3 text-slate-500">
          <ProgressSpinner style="width: 48px; height: 48px" strokeWidth="4" animationDuration=".8s" />
          <p class="text-sm font-medium">Memuat Dashboard Seller...</p>
        </div>

        <RouterView v-else />
      </main>

    </div>

  </div>
</template>
