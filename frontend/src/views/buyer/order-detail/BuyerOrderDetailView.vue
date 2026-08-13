<script setup lang="ts">
import { getApiErrorMessage } from '@/services/apiError'
import { buyerOrderService } from '@/services/buyerOrderService'
import type { Order } from '@/types'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const order = ref<Order | null>(null)
const loading = ref(true)
const actionLoading = ref(false)
const errorMessage = ref('')
const helpVisible = ref(false)

const isCod = computed(() => order.value?.shipping_method === 'cod')
const totalItems = computed(() => order.value?.items?.reduce((sum, item) => sum + item.quantity, 0) || 0)
const canConfirm = computed(() => order.value?.status === 'shipped')
const isStepDone = (step: string) => {
  const rank: Record<string, number> = { pending: 1, processing: 2, shipped: 3, delivered: 4, cancelled: 0 }
  const stepRank: Record<string, number> = { 'Pesanan dibuat': 1, Diproses: 2, Dikirim: 3, Selesai: 4 }
  return (rank[order.value?.status || 'pending'] || 0) >= stepRank[step]
}

const currency = (value: string | number) => new Intl.NumberFormat('id-ID', {
  style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(value) || 0)

const date = (value?: string) => value ? new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium', timeStyle: 'short',
}).format(new Date(value)) : '—'

const loadOrder = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    order.value = await buyerOrderService.getDetail(Number(route.query.id))
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Detail pesanan tidak dapat dimuat.')
  } finally {
    loading.value = false
  }
}

const confirmReceived = async () => {
  if (!order.value || !canConfirm.value) return
  actionLoading.value = true
  try {
    order.value = await buyerOrderService.confirmReceived(order.value.id)
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Pesanan belum dapat diselesaikan.')
  } finally {
    actionLoading.value = false
  }
}

const contactSeller = () => {
  const phone = order.value?.shop?.phone?.replace(/\D/g, '')
  if (phone) {
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(`Halo, saya ingin menanyakan pesanan ${order.value?.order_number}.`)}`, '_blank', 'noopener')
  } else {
    helpVisible.value = true
  }
}

onMounted(loadOrder)
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl space-y-5">
      <button class="flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600" @click="router.push('/profile/orders')">
        <i class="pi pi-arrow-left"></i>Kembali ke pesanan
      </button>

      <div v-if="loading" class="flex flex-col items-center justify-center rounded-2xl bg-white py-24 shadow-sm">
        <ProgressSpinner style="width: 42px; height: 42px" />
        <span class="mt-3 text-sm text-slate-500">Memuat detail pesanan...</span>
      </div>
      <Message v-else-if="errorMessage" severity="error">{{ errorMessage }}</Message>

      <template v-else-if="order">
        <div class="flex flex-col justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm sm:flex-row sm:items-center">
          <div><h1 class="text-xl font-bold text-slate-900">Detail Pesanan</h1><p class="mt-1 text-sm text-slate-500">{{ order.order_number }} · {{ date(order.created_at) }}</p></div>
          <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-bold capitalize text-blue-700">{{ order.status }}</span>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
          <div class="space-y-5">
            <section class="rounded-2xl bg-white p-5 shadow-sm">
              <h2 class="mb-4 font-bold text-slate-900">Status Pesanan</h2>
              <div class="flex items-center gap-2 text-xs">
                <span v-for="step in ['Pesanan dibuat', 'Diproses', 'Dikirim', 'Selesai']" :key="step" class="flex flex-1 items-center gap-2">
                  <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full" :class="isStepDone(step) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400'"><i :class="isStepDone(step) ? 'pi pi-check' : 'pi pi-circle-fill text-[6px]' "></i></span>
                  <span class="hidden sm:inline" :class="isStepDone(step) ? 'text-slate-700' : 'text-slate-400'">{{ step }}</span>
                </span>
              </div>
              <Button v-if="canConfirm" label="Pesanan Diterima / Selesaikan" icon="pi pi-check" :loading="actionLoading" class="mt-6 w-full" @click="confirmReceived" />
              <p v-else-if="order.status === 'delivered'" class="mt-5 rounded-xl bg-emerald-50 p-3 text-center text-sm font-semibold text-emerald-700">Pesanan sudah selesai.</p>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm">
              <h2 class="mb-4 font-bold text-slate-900">Produk ({{ totalItems }})</h2>
              <div v-for="item in order.items" :key="item.id" class="flex gap-3 border-b border-slate-100 py-3 last:border-0">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-400"><i class="pi pi-box text-xl"></i></div>
                <div class="min-w-0 flex-1"><p class="font-semibold text-slate-800">{{ item.product?.name || 'Produk' }}</p><p class="text-xs text-slate-500">{{ item.quantity }} × {{ currency(item.price_snapshot) }}</p></div>
                <strong class="text-sm text-slate-800">{{ currency(Number(item.price_snapshot) * item.quantity) }}</strong>
              </div>
            </section>
          </div>

          <aside class="space-y-5">
            <section class="rounded-2xl bg-white p-5 shadow-sm"><h2 class="mb-4 font-bold text-slate-900">Ringkasan Pembayaran</h2><div class="space-y-2 text-sm"><div class="flex justify-between"><span>Subtotal</span><span>{{ currency(order.subtotal) }}</span></div><div class="flex justify-between"><span>Pengiriman</span><span>{{ currency(order.shipping_cost) }}</span></div><div class="flex justify-between border-t pt-3 font-bold"><span>Total</span><span class="text-blue-600">{{ currency(order.total_amount) }}</span></div></div></section>
            <section class="rounded-2xl bg-white p-5 shadow-sm"><h2 class="mb-1 font-bold text-slate-900">{{ order.shop?.name || 'Toko' }}</h2><p class="mb-4 text-xs text-slate-500">{{ order.shipping_address || 'Alamat belum tersedia' }}</p><Button label="Hubungi Penjual" icon="pi pi-whatsapp" class="mb-2 w-full" @click="contactSeller" /><Button label="Bantuan Pesanan" icon="pi pi-question-circle" outlined class="w-full" @click="helpVisible = true" /></section>
          </aside>
        </div>
      </template>
    </div>
    <Dialog v-model:visible="helpVisible" modal header="Bantuan Pesanan" :style="{ width: 'min(440px, 92vw)' }"><p class="text-sm leading-6 text-slate-600">Simpan nomor pesanan saat menghubungi bantuan. Untuk kendala pembayaran, pengiriman, atau barang diterima, hubungi admin Kabita melalui pusat bantuan.</p><Button label="Ke Profil Pesanan" class="mt-4 w-full" @click="helpVisible = false; router.push('/profile/orders')" /></Dialog>
  </div>
</template>
