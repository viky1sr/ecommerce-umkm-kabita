<script setup lang="ts">
import { useCartStore } from '@/stores/cart'
import type { Cart, CartGroupByShop, CartItem } from '@/types'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import ProgressSpinner from 'primevue/progressspinner'
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const loadingToCheckout = ref<boolean>(false);

const cartStore = useCartStore()

onMounted(() => {
  if (!cartStore.cart) {
    cartStore.loadCart()
  }
})

const cartGroups = computed<CartGroupByShop[]>(() => cartStore.cart?.groups_by_shop ?? [])
const allItems = computed<CartItem[]>(() => cartGroups.value.flatMap((group) => group.items))

// --- SELECTION STATES ---
const selectedItemIds = ref<number[]>([])

// Keep selection in sync after the cart API response arrives.
watch(
  () => cartGroups.value.flatMap((group) => group.items.map((item) => item.id)),
  (ids) => {
    selectedItemIds.value = selectedItemIds.value.filter((id) => ids.includes(id))
    if (selectedItemIds.value.length === 0 && ids.length > 0) {
      selectedItemIds.value = [...ids]
    }
  },
  { immediate: true },
)

// Status Pilih Semua (Checkbox Atas)
const isSelectAll = computed({
  get: () => allItems.value.length > 0 && selectedItemIds.value.length === allItems.value.length,
  set: (val: boolean) => {
    if (val) {
      selectedItemIds.value = allItems.value.map((item) => item.id)
    } else {
      selectedItemIds.value = []
    }
  }
})

// Cek apakah seluruh item di satu toko terpilih
const isShopSelected = (group: CartGroupByShop) => {
  return group.items.every((item) => selectedItemIds.value.includes(item.id))
}

// Toggle Checkbox Toko
const toggleShopSelection = (group: CartGroupByShop, checked: boolean) => {
  const shopItemIds = group.items.map((item) => item.id)
  if (checked) {
    selectedItemIds.value = Array.from(new Set([...selectedItemIds.value, ...shopItemIds]))
  } else {
    selectedItemIds.value = selectedItemIds.value.filter((id) => !shopItemIds.includes(id))
  }
}

// Toggle Checkbox Item Tunggal
const toggleItemSelection = (id: number, checked: boolean) => {
  if (checked) {
    selectedItemIds.value.push(id)
  } else {
    selectedItemIds.value = selectedItemIds.value.filter((itemId) => itemId !== id)
  }
}

// --- AKSI KUANTITAS & HAPUS ---
const updateQuantity = async (item: CartItem, delta: number) => {
  const newQty = item.quantity + delta
  if (newQty >= 1 && newQty <= (item.product?.stock || 99)) {
    await cartStore.updateQuantity(item.id, newQty)
  }
}

const deleteItem = async (itemId: number) => {
  await cartStore.removeItem(itemId)
}

const deleteSelectedItems = async () => {
  for (const id of selectedItemIds.value) {
    await cartStore.removeItem(id)
  }
}

// --- RINGKASAN BELANJA ---

const totalSelectedCount = computed(() => {
  return allItems.value
    .filter((item) => selectedItemIds.value.includes(item.id))
    .reduce((sum, item) => sum + item.quantity, 0)
})

const subtotalAmount = computed(() => {
  return allItems.value
    .filter((item) => selectedItemIds.value.includes(item.id))
    .reduce((sum, item) => sum + item.subtotal, 0)
})

const grandTotalAmount = computed(() => {
  return subtotalAmount.value
})

const selectedCheckoutItems = computed<Cart[]>(() => {
  if (!cartStore.cart) return []
  const groups = cartGroups.value
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => selectedItemIds.value.includes(item.id)),
    }))
    .filter((group) => group.items.length > 0)
  return groups.length > 0 ? [{ ...cartStore.cart, groups_by_shop: groups }] : []
})

const goToCheckout = () => {
  if (selectedCheckoutItems.value.length === 0) return
  loadingToCheckout.value = true
  localStorage.setItem('checkoutItems', JSON.stringify(selectedCheckoutItems.value))
  router.push('/checkout').finally(() => { loadingToCheckout.value = false })
}

// Helper Currency
const formatCurrency = (val: number) => {
  return 'Rp ' + val.toLocaleString('id-ID')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Loading GET -->
      <div v-if="cartStore.initialLoading" class="flex flex-col items-center justify-center py-20 gap-3">
        <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
        <span class="text-xs text-slate-500">Memuat keranjang...</span>
      </div>

      <!-- Error -->
      <div v-else-if="cartStore.error" class="bg-white rounded p-6 shadow-sm border border-rose-100 text-center">
        <p class="text-xs text-rose-600 mb-3">{{ cartStore.error }}</p>
        <Button label="Coba lagi" size="small" @click="cartStore.loadCart()" />
      </div>

      <!-- Konten utama -->
      <template v-else>

        <!-- Page Title -->
          <h1 class="text-xl font-bold text-slate-800">Keranjang Belanja</h1>

          <!-- Main Layout Grid -->
            <div v-if="allItems.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

              <!-- KOLOM KIRI (Daftar Item & Toko) -->
                <div class="lg:col-span-8 space-y-4">

                  <!-- Card Header Select All -->
                    <div
                      class="bg-white rounded p-4 shadow-sm border border-slate-100 flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <Checkbox v-model="isSelectAll" :binary="true" inputId="selectAll" />
                        <label for="selectAll" class="text-xs font-semibold text-slate-700 cursor-pointer">
                          Pilih Semua ({{ totalSelectedCount }} Item)
                        </label>
                      </div>
                      <button v-if="selectedItemIds.length > 0" @click="deleteSelectedItems"
                        class="text-xs text-rose-600 font-semibold hover:underline">
                        Hapus
                      </button>
                    </div>

                    <!-- Group Per Toko -->
                      <div v-for="group in cartGroups" :key="group.shop.id"
                        class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
                        <!-- Header Toko -->
                          <div class="bg-slate-50/50 p-4 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                              <Checkbox :modelValue="isShopSelected(group)" :binary="true"
                                @update:modelValue="(val) => toggleShopSelection(group, val)" />
                              <div class="flex items-center gap-2">
                                <i class="pi pi-shop text-slate-700 text-sm"></i>
                                <span class="text-xs font-bold text-slate-800">{{ group.shop.name }}</span>
                              </div>
                            </div>
                          </div>

                          <!-- List Product Cart Item -->
                            <div class="divide-y divide-slate-100">
                              <div v-for="item in group.items" :key="item.id"
                                class="p-4 flex items-start gap-3 sm:gap-4">
                                <!-- Checkbox Item -->
                                  <Checkbox :modelValue="selectedItemIds.includes(item.id)" :binary="true" class="mt-4"
                                    @update:modelValue="(val) => toggleItemSelection(item.id, val)" />

                                  <!-- Gambar Produk -->
                                    <div v-if="!item.product?.images?.[0]?.url"
                                      class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-400">
                                      <i class="pi pi-image text-xl"></i>
                                    </div>
                                    <img v-else :src="item.product.images[0].url" :alt="item.product?.name"
                                      class="w-20 h-20 rounded-xl object-cover border border-slate-100 shrink-0" />

                                    <!-- Detail Produk & Kontrol Kuantitas -->
                                      <div class="flex-1 min-w-0 space-y-1">
                                        <div class="flex items-start justify-between gap-2">
                                          <div>
                                            <h3 class="text-xs font-bold text-slate-800 line-clamp-1">{{
                                              item.product?.name }}</h3>
                                          </div>
                                          <!-- Tombol Hapus Single Item -->
                                            <button @click="deleteItem(item.id)"
                                              class="text-slate-400 hover:text-rose-600 transition-colors">
                                              <i class="pi pi-trash text-xs"></i>
                                            </button>
                                        </div>

                                        <!-- Harga & Quantity Selector -->
                                          <div class="flex flex-wrap items-center justify-between pt-2 gap-2">
                                            <span class="text-xs font-bold text-blue-600">
                                              {{ formatCurrency(item.product?.price ?? 0) }}
                                            </span>

                                            <!-- Plus Minus Quantity Counter -->
                                              <div
                                                class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white">
                                                <button @click="updateQuantity(item, -1)" :disabled="item.quantity <= 1"
                                                  class="px-2.5 py-1 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                                                  <i class="pi pi-minus text-[10px]"></i>
                                                </button>
                                                <span
                                                  class="px-3 py-1 text-xs font-semibold text-slate-800 border-x border-slate-200 min-w-[32px] text-center">
                                                  {{ item.quantity }}
                                                </span>
                                                <button @click="updateQuantity(item, 1)"
                                                  class="px-2.5 py-1 text-slate-500 hover:bg-slate-50 transition-colors">
                                                  <i class="pi pi-plus text-[10px]"></i>
                                                </button>
                                              </div>
                                          </div>
                                      </div>
                              </div>
                            </div>

                      </div>

                </div>

                <!-- KOLOM KANAN (Ringkasan Belanja) -->
                  <div class="lg:col-span-4 space-y-4">
                    <div class="bg-white rounded p-6 shadow-sm border border-slate-100 space-y-4">
                      <h2 class="font-bold text-slate-800 text-sm">Ringkasan Belanja</h2>

                      <div class="space-y-2 text-xs border-b border-slate-100 pb-4">
                        <div class="flex items-center justify-between text-slate-600">
                          <span>Total Harga ({{ totalSelectedCount }} Barang)</span>
                          <span class="font-medium text-slate-800">{{ formatCurrency(subtotalAmount) }}</span>
                        </div>
                      </div>

                      <!-- Total Harga Akhir -->
                        <div class="flex items-center justify-between pt-1">
                          <span class="text-xs font-bold text-slate-800">Total Harga</span>
                          <span class="text-lg font-bold text-blue-600">{{ formatCurrency(grandTotalAmount) }}</span>
                        </div>

                        <!-- Tombol Beli / Checkout -->
                          <Button :loading="loadingToCheckout" :label="`Beli (${totalSelectedCount})`"
                            :disabled="totalSelectedCount === 0 || loadingToCheckout"
                            class="w-full bg-emerald-500! border-emerald-500! py-3! text-xs! font-bold! rounded! shadow-sm! hover:bg-emerald-600! disabled:opacity-50! disabled:cursor-not-allowed! mt-2"
                            @click="goToCheckout" />
                    </div>
                  </div>

            </div>

            <div v-else class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
              <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <i class="pi pi-shopping-bag text-3xl"></i>
              </div>
              <h2 class="mt-5 text-xl font-bold text-slate-900">Keranjang masih kosong</h2>
              <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                Yuk temukan produk UMKM pilihan dan tambahkan ke keranjang untuk mulai berbelanja.
              </p>
              <Button label="Mulai Belanja" icon="pi pi-arrow-right" iconPos="right"
                class="mt-6 rounded-xl! bg-blue-600! border-blue-600! px-6! py-3!" @click="router.push('/produk')" />
            </div>


      </template>

    </div>
  </div>
</template>
