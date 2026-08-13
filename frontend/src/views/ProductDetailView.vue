<script setup lang="ts">
import { publicProductService } from '@/services/publicProductService'
import { getApiErrorMessage } from '@/services/apiError'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import type { Product } from '@/types'
import { formatRupiah } from '@/utils/format'
import { useToast } from 'primevue/usetoast'
import { computed, onMounted, ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const slug = computed(() => String(route.params.id ?? ''))
const product = ref<Product | null>(null)
const isLoadingGet = ref<boolean>(true)
// const productStore = useProductStore()
const cartStore = useCartStore()
const toast = useToast()
const authStore = useAuthStore();

async function getProduct() {
  try {
    const response = await publicProductService.getBySlug(slug.value);

    if (response.success && response.data) {
      product.value = response.data;
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: getApiErrorMessage(error, 'Gagal memuat detail barang'),
      life: 3000,
    })
  } finally {
    isLoadingGet.value = false;
  }
}

onMounted(() => {
  getProduct()
})

const selectedImageIndex = ref(0)
const quantity = ref(1)

watchEffect(() => {
  selectedImageIndex.value = 0
  quantity.value = 1
})

const selectedImage = computed(() => {
  const images = product.value?.images
  return images?.[selectedImageIndex.value]?.url ?? images?.[0]?.url ?? 'https://placehold.co/600x600?text=Produk'
})

const incrementQuantity = () => {
  if (!product.value) return
  if (quantity.value < product.value.stock) {
    quantity.value += 1
  }
}

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value -= 1
  }
}

const changeImage = (index: number) => {
  selectedImageIndex.value = index
}

const handleAddToCart = async () => {
  if (!product.value) return
  const success = await cartStore.addToCart(product.value, quantity.value)
  toast.add({
    severity: success ? 'success' : 'error',
    summary: success ? 'Berhasil' : 'Gagal',
    detail: success ? `${product.value.name} berhasil ditambahkan ke keranjang` : (cartStore.error || 'Gagal menambahkan produk'),
    life: 3000,
  })
}

const handleBuyNow = async () => {
  if (!product.value) return
  const success = await cartStore.addToCart(product.value, quantity.value)
  if (!success) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: cartStore.error || 'Gagal menambahkan produk', life: 3000 })
    return
  }
  toast.add({
    severity: 'success',
    summary: 'Siap checkout',
    detail: `${product.value.name} sudah siap dibeli`,
    life: 3000,
  })
}
</script>

<template>
  <div class="max-w-7xl container mx-auto px-4 py-8">
    <div class="flex flex-col gap-6">
      <nav aria-label="Breadcrumb" class="text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
          <li>
            <router-link to="/" class="text-slate-500 hover:text-slate-700">Home</router-link>
          </li>
          <li>
            <span class="text-slate-300">/</span>
          </li>
          <li>
            <router-link :to="product?.category ? `/kategori/${product.category.slug}` : '/produk'"
              class="text-slate-500 hover:text-slate-700">
              {{ product?.category?.name ?? 'Produk' }}
            </router-link>
          </li>
          <li>
            <span class="text-slate-300">/</span>
          </li>
          <li class="text-slate-900 font-semibold">
            {{ product?.name ?? 'Produk tidak ditemukan' }}
          </li>
        </ol>
      </nav>

      <section v-if="product" class="grid gap-8 sm:grid-cols-[5fr_7fr]">
        <div class="space-y-4">
          <div class="rounded-2xl border detail-page__border bg-white p-4 shadow-sm">
            <div class="aspect-square overflow-hidden rounded-xl bg-slate-50">
              <img :src="selectedImage" :alt="product.name" class="h-full w-full object-contain" />
            </div>
          </div>

          <div class="grid grid-cols-4 gap-3">
            <button v-for="(image, index) in product.images" :key="image.id" type="button"
              class="detail-page__gallery-thumb rounded-lg border bg-white p-1 transition focus:outline-none"
              :class="{ 'detail-page__gallery-thumb--active': selectedImageIndex === index }"
              @click="changeImage(index)">
              <img :src="image.url ?? 'https://placehold.co/120x120?text=Gambar'" :alt="`Thumbnail ${index + 1}`"
                class="h-20 w-full object-contain" />
            </button>
          </div>
        </div>

        <div class="space-y-6">
          <div class="space-y-3">
            <h1 class="text-2xl font-bold leading-tight text-slate-950 lg:text-3xl">
              {{ product.name }}
            </h1>

            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
              <!-- <div class="flex items-center gap-2">
                <span
                  class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-yellow-100 text-yellow-700">
                  <i class="pi pi-star text-xs"></i>
                </span>
                <span class="font-semibold text-tertiary-container">4.9</span>
                <span>(120 Ulasan)</span>
              </div> -->
              <!-- <span class="h-1 w-1 rounded-full bg-slate-300" aria-hidden="true"></span> -->
              <!-- <span>Terjual 350+</span> -->
            </div>

            <div class="text-3xl font-bold text-primary leading-tight">
              {{ formatRupiah(product.price) }}
            </div>

            <!-- <div
              class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-[#b4c5ff] px-3 py-2 text-sm font-medium text-on-primary-fixed">
              <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-white">
                <i class="pi pi-truck" style="font-size: 0.65rem"></i>
              </span>
              Gratis Ongkir ke Jakarta Selatan
            </div> -->
          </div>

          <div class="space-y-4 rounded-2xl border detail-page__border bg-white p-6">

            <div class="flex flex-wrap items-center gap-4">
              <div class="min-w-30">
                <div class="text-sm font-bold text-slate-950">Kuantitas</div>
                <div>
                  <div v-show="authStore"
                    class="mt-2 inline-flex overflow-hidden rounded-2xl border detail-page__quantity-control bg-white mb-2">
                    <button type="button"
                      class="px-2 text-lg text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed"
                      @click="decrementQuantity" :disabled="quantity <= 1">
                      −
                    </button>
                    <div class="flex min-w-12 items-center justify-center px-4 text-sm font-semibold text-slate-900">
                      {{ quantity }}
                    </div>
                    <button type="button"
                      class="px-2 text-lg text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed"
                      @click="incrementQuantity" :disabled="quantity >= product.stock">
                      +
                    </button>
                  </div>
                  <div class="text-sm text-slate-500">Tersedia: {{ product.stock }}</div>
                </div>

              </div>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
              <button type="button" @click="handleAddToCart"
                class="detail-page__secondary-button inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border px-5 py-4 text-sm font-semibold transition hover:bg-slate-50">
                <i class="pi pi-shopping-cart"></i>
                Tambah Keranjang
              </button>
              <button type="button" @click="handleBuyNow"
                class="detail-page__primary-button inline-flex flex-1 items-center justify-center rounded-2xl px-5 py-4 text-sm font-semibold text-white transition hover:bg-[#0037a0]">
                Beli Sekarang
              </button>
            </div>
          </div>

          <div class="rounded-2xl border detail-page__border bg-surface-container p-5">
            <div class="flex flex-wrap items-center gap-4">
              <div class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 bg-white">
                <i class="pi pi-store text-lg text-slate-700"></i>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-base font-bold text-slate-950">{{ product.shop?.name ?? 'DigiCam Official' }}</div>
                <div class="text-sm font-medium text-slate-500">Kota Bandung</div>
              </div>
              <button type="button"
                class="detail-page__secondary-button rounded-2xl border px-4 py-3 text-sm font-semibold transition hover:bg-slate-50">
                Kunjungi Toko
              </button>
            </div>
          </div>

          <div class="space-y-4 rounded-2xl border detail-page__border bg-white p-6">
            <div>
              <h2 class="text-xl font-bold text-slate-950">Deskripsi Produk</h2>
            </div>
            <div class="space-y-4 text-sm leading-7 text-slate-600">
              <p>{{ product.description }}</p>
            </div>
            <!-- <a href="#" class="text-sm font-semibold text-primary hover:underline">Baca Selengkapnya</a> -->
          </div>
        </div>
      </section>

      <section v-else class="rounded-2xl border detail-page__border bg-white p-8 text-center text-slate-700 shadow-sm">
        <p class="text-lg font-semibold">Produk tidak ditemukan.</p>
        <p class="mt-2 text-sm text-slate-500">Silakan kembali ke daftar produk atau cari produk lain.</p>
      </section>
    </div>
  </div>
</template>

<style scoped>
.detail-page__border {
  border: 1px solid #c3c6d7;
}

.detail-page__gallery-thumb {
  border: 1px solid #c3c6d7;
}

.detail-page__gallery-thumb--active {
  border-color: #004ac6;
}

.detail-page__quantity-control {
  border: 1px solid #c3c6d7;
}

.detail-page__color-option {
  border: 1px solid #c3c6d7;
  color: #434655;
  background-color: #ffffff;
}

.detail-page__color-option--active {
  border-color: #004ac6;
  background-color: #dbe1ff;
  color: #004ac6;
}

.detail-page__primary-button {
  background-color: #004ac6;
}

.detail-page__secondary-button {
  border: 2px solid #004ac6;
  color: #004ac6;
  background-color: #ffffff;
}
</style>
