<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'


const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const isSidebarOpen = ref(false)

const currentAdmin = computed(() => authStore.user)

// Menu Navigasi khusus Admin Internal
const sidebarMenus = ref([
  { label: 'Dashboard', slug: 'dashboard', path: '/admin/dashboard', icon: 'pi pi-th-large' },
  { label: 'Kelola Kategori', slug: 'kategori', path: '/admin/kategori', icon: 'pi pi-tags' },
  { label: 'Kelola Toko', slug: 'toko', path: '/admin/toko', icon: 'pi pi-shop' },
  { label: 'Kelola User', slug: 'user', path: '/admin/user', icon: 'pi pi-users' },
  { label: 'Verifikasi', slug: 'verifikasi', path: '/admin/verifikasi', icon: 'pi pi-verified' },
  { label: 'Transaksi', slug: 'transaksi', path: '/admin/transaksi', icon: 'pi pi-receipt' },
  { label: 'Analitik Platform', slug: 'analitik', path: '/admin/analitik', icon: 'pi pi-chart-bar' },
  { label: 'Pengaturan', slug: 'pengaturan', path: '/admin/pengaturan', icon: 'pi pi-cog' }
])

// Menentukan menu aktif berdasarkan prop slug atau route path
const activeSlug = computed(() => {
  const currentPath = route.path
  const foundMenu = sidebarMenus.value.find((menu) => currentPath === menu.path || currentPath.startsWith(`${menu.path}/`))
  return foundMenu ? foundMenu.slug : 'dashboard'
})

const currentTitle = computed(() => {
  const activeMenu = sidebarMenus.value.find((m) => m.slug === activeSlug.value)
  return activeMenu ? activeMenu.label : 'Admin Internal'
})

const navigateTo = (path: string) => {
  isSidebarOpen.value = false
  if (route.path !== path) void router.push(path)
}

const handleLogout = () => authStore.logout()

watch(() => route.path, () => {
  isSidebarOpen.value = false
})
</script>

<template>
  <div class="flex h-dvh min-h-0 overflow-hidden bg-slate-100 font-sans text-slate-800">
    <button v-if="isSidebarOpen" type="button" aria-label="Tutup menu"
      class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden" @click="isSidebarOpen = false" />

    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 flex h-dvh w-72 max-w-[85vw] -translate-x-full flex-col justify-between overflow-hidden border-r border-slate-800 bg-slate-900 text-slate-300 shadow-xl transition-transform duration-200 lg:static lg:z-auto lg:w-64 lg:translate-x-0',
        isSidebarOpen ? 'translate-x-0' : ''
      ]">
      <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <div class="flex h-16 items-center gap-3 border-b border-slate-800 bg-slate-950/50 px-5 sm:px-6">
          <div
            class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
            K
          </div>
          <div class="min-w-0">
            <h1 class="text-base font-bold text-white tracking-wide leading-tight">Kabita Admin</h1>
            <p class="text-[10px] text-blue-400 font-medium tracking-wider uppercase">Internal System</p>
          </div>
        </div>

        <nav class="p-4 space-y-1.5">
          <div class="px-3 py-2 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
            Menu Utama
          </div>
          <button v-for="menu in sidebarMenus" :key="menu.slug" @click="navigateTo(menu.path)"
            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
            :class="[
              activeSlug === menu.slug
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 font-semibold'
                : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
            ]">
            <i :class="[menu.icon, 'text-lg', activeSlug === menu.slug ? 'text-white' : 'text-slate-400']"></i>
            <span>{{ menu.label }}</span>
          </button>
        </nav>
      </div>

      <div class="shrink-0 border-t border-slate-800 bg-slate-950/30 p-4">
        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/50">
          <div class="flex items-center gap-2.5 overflow-hidden">
            <div
              class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-semibold text-xs border border-blue-500/30">
              AD
            </div>
            <div class="truncate">
              <p class="text-xs font-medium text-slate-200 truncate">{{ currentAdmin?.name || 'Admin' }}</p>
              <p class="text-[10px] text-slate-400 truncate">{{ currentAdmin?.email || '' }}</p>
            </div>
          </div>
          <Button icon="pi pi-sign-out" text rounded severity="secondary"
            class="text-slate-400! hover:text-red-400! w-8! h-8! p-0!" v-tooltip.top="'Keluar'"
            @click="handleLogout" />
        </div>
      </div>
    </aside>

    <div class="flex h-full min-w-0 flex-1 flex-col overflow-hidden">
      <header
        class="flex min-h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 shadow-xs z-10 sm:px-6 lg:px-8">
        <div>
          <div class="flex items-center gap-3">
            <Button icon="pi pi-bars" text rounded aria-label="Buka menu"
              class="lg:hidden! text-slate-700!" @click="isSidebarOpen = true" />
            <h2 class="text-base font-bold tracking-tight text-slate-800 sm:text-xl">{{ currentTitle }}</h2>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
          <div class="relative hidden w-64 md:block">
            <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <InputText placeholder="Cari data sistem..."
              class="w-full pl-10! pr-4! py-2! bg-slate-50! border-slate-200! rounded-full! text-sm! focus:bg-white! focus:ring-2! focus:ring-blue-500/20!" />
          </div>

          <Button icon="pi pi-bell" severity="secondary" text rounded
            class="relative! text-slate-600! hover:bg-slate-100!">
            <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-red-500"></span>
          </Button>

          <div class="flex items-center gap-2 border-l border-slate-200 pl-2 sm:gap-2.5 sm:pl-3">
            <template>
              <div
                class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                {{ (currentAdmin?.name || 'Admin').slice(0, 2).toUpperCase() }}
              </div>
              <div class="flex flex-col">
                <span class="hidden text-xs font-semibold leading-none text-slate-800 sm:block">{{ currentAdmin?.name }}</span>
                <span class="hidden text-[10px] font-semibold uppercase text-blue-600 mt-0.5 sm:block">Admin Internal</span>
              </div>
            </template>
          </div>
        </div>
      </header>

      <main class="min-h-0 min-w-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain bg-slate-50/50 p-4 sm:p-6 lg:p-8">
        <slot>
          <router-view />
        </slot>
      </main>
    </div>
  </div>
</template>
