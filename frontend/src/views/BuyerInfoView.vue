<script setup lang="ts">
import Button from 'primevue/button'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const isHelp = route.path === '/bantuan'
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-12 sm:px-6">
    <div class="mx-auto max-w-3xl rounded-3xl bg-white p-6 shadow-sm sm:p-10">
      <i :class="isHelp ? 'pi pi-question-circle' : 'pi pi-heart'" class="text-3xl text-blue-600"></i>
      <h1 class="mt-4 text-3xl font-bold text-slate-900">{{ isHelp ? 'Pusat Bantuan' : 'Tentang Kabita' }}</h1>
      <p class="mt-4 leading-7 text-slate-600">{{ isHelp ? 'Temukan produk, periksa status pesanan, dan hubungi seller melalui detail pesanan. Jika ada kendala pembayaran atau pengiriman, simpan nomor pesanan saat meminta bantuan.' : 'Kabita membantu pembeli menemukan produk UMKM lokal dan membantu seller mengelola toko, produk, serta pesanan dalam satu platform.' }}</p>
      <Button label="Kembali Belanja" icon="pi pi-arrow-left" class="mt-8" @click="router.push('/produk')" />
    </div>
  </div>
</template>
