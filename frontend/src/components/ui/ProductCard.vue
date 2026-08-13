<script setup lang="ts">
import { useCartStore } from '@/stores/cart'
import type { Product } from '@/types'
import { formatRupiah } from '@/utils/format'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { computed } from 'vue'

const props = defineProps<{ product: Product }>()

const cartStore = useCartStore()
const toast = useToast()
const cartItem = computed(() => cartStore.flatItems.find((item) => item.product_id === props.product.id))

async function handleAddToCart() {
  const success = await cartStore.addToCart(props.product)
  toast.add({
    severity: success ? 'success' : 'error',
    summary: success ? 'Berhasil' : 'Gagal',
    detail: success ? `${props.product.name} berhasil ditambahkan ke keranjang` : (cartStore.error || 'Gagal menambahkan produk'),
    life: 3000,
  })
}

async function decreaseQuantity() {
  if (!cartItem.value) return
  if (cartItem.value.quantity <= 1) await cartStore.removeItem(cartItem.value.id)
  else await cartStore.updateQuantity(cartItem.value.id, cartItem.value.quantity - 1)
}
</script>

<template>
  <div class="h-full overflow-hidden rounded-[24px] border border-[#e1e2ed] bg-white shadow-sm">
    <router-link :to="`/produk/${props.product.slug}`" class="block overflow-hidden bg-surface-container p-4">
      <div v-if="props.product.images?.[0]?.url" class="flex h-45.5 items-center justify-center">
        <img :src="props.product.images[0].url!" :alt="props.product.name" class="h-full w-full object-contain" />
      </div>
      <div v-else class="flex h-45.5 items-center justify-center text-sm text-slate-400">Tidak ada gambar</div>
    </router-link>

    <div class="p-6">
      <router-link :to="`/produk/${props.product.slug}`"
        class="no-underline text-base font-semibold text-slate-950 hover:text-slate-700">
        {{ props.product.name }}
      </router-link>
      <p class="text-sm text-slate-500 mt-2 mb-4">{{ props.product.shop?.name }}</p>
      <div class="flex items-center justify-between gap-3">
        <span class="text-lg font-bold text-slate-950">{{ formatRupiah(props.product.price) }}</span>
        <div v-if="cartItem" class="flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 p-1">
          <Button icon="pi pi-minus" text rounded size="small" aria-label="Kurangi jumlah"
            :disabled="cartStore.actionLoading" @click="decreaseQuantity" />
          <span class="min-w-6 text-center text-sm font-bold text-blue-700">{{ cartItem.quantity }}</span>
          <Button icon="pi pi-plus" text rounded size="small" aria-label="Tambah jumlah"
            :disabled="cartStore.actionLoading" @click="handleAddToCart" />
        </div>
        <Button v-else icon="pi pi-plus" :loading="cartStore.actionLoading" :disabled="cartStore.actionLoading" class="p-button-rounded p-button-primary" aria-label="Tambah ke Keranjang"
          @click="handleAddToCart" />
      </div>
    </div>
  </div>
</template>
