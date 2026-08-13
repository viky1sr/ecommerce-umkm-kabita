<script setup lang="ts">
import type { Order } from '@/types'
import Button from 'primevue/button'
import { computed, ref } from 'vue'

// Mock Data berdasarkan Interface Order
const order = ref<Order>({
  id: 101,
  order_number: 'KBT-882910',
  buyer_id: 1,
  shop_id: 10,
  subtotal: '240000.00',
  shipping_cost: '0.00',
  total_amount: '242000.00', // Subtotal + Biaya Layanan
  shipping_method: 'cod', // Bisa diganti ke 'kurir' untuk mode pengiriman kurir
  payment_method: 'Cash on Delivery (Ketemuan)',
  status: 'shipped', // Mapped ke 'Siap Ketemuan' / 'Dalam Pengiriman'
  shipping_address: 'Jl. Asia Afrika, Balonggede, Regol, Kota Bandung, Jawa Barat 40251',
  tracking_number: null, // Berisi no resi jika dikirim via kurir
  notes: 'Alun-alun Bandung', // Digunakan sebagai Lokasi Ketemuan jika COD
  created_at: '2024-10-24T14:30:00Z',
  updated_at: '2024-10-24T15:00:00Z',
  shop: {
    id: 10,
    seller_id: 2,
    name: 'Toko Aneka Kerajinan',
    slug: 'toko-aneka-kerajinan',
    description: null,
    logo: 'https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png',
    status: 'verified',
    verified_by: 1,
    verified_at: '',
    rejection_reason: null,
    created_at: '',
    updated_at: ''
  },
  items: [
    {
      id: 1,
      order_id: 101,
      product_id: 1,
      quantity: 1,
      price_snapshot: '150000.00',
      cost_snapshot: '120000.00',
      created_at: '',
      updated_at: '',
      product: {
        id: 1,
        shop_id: 10,
        category_id: 1,
        name: 'Kerajinan Anyaman Rotan Khas Daerah',
        slug: 'kerajinan-anyaman-rotan-khas-daerah',
        description: null,
        price: '150000.00',
        cost_price: null,
        stock: 5,
        weight: 500,
        status: 'active',
        verified_at: null,
        rejection_reason: null,
        created_at: '',
        images: [
          { id: 1, url: 'https://primefaces.org/cdn/primevue/images/galleria/galleria2.jpg' }
        ]
      }
    },
    {
      id: 2,
      order_id: 101,
      product_id: 2,
      quantity: 2,
      price_snapshot: '45000.00',
      cost_snapshot: '35000.00',
      created_at: '',
      updated_at: '',
      product: {
        id: 2,
        shop_id: 10,
        category_id: 2,
        name: 'Kopi Lokal Robusta Premium 250g',
        slug: 'kopi-lokal-robusta-premium-250g',
        description: null,
        price: '45000.00',
        cost_price: null,
        stock: 20,
        weight: 250,
        status: 'active',
        verified_at: null,
        rejection_reason: null,
        created_at: '',
        images: [
          { id: 2, url: 'https://primefaces.org/cdn/primevue/images/galleria/galleria1.jpg' }
        ]
      }
    }
  ]
})

// Biaya Layanan Tetap
const serviceFee = 2000

// Cek Metode Pengiriman (Mode COD vs Kurir)
const isCod = computed(() => order.value.shipping_method === 'cod')

// Total Jumlah Barang
const totalItemsCount = computed(() => {
  return order.value.items?.reduce((sum, item) => sum + item.quantity, 0) || 0
})

// Format Currency
const formatCurrency = (val: string | number) => {
  const num = typeof val === 'string' ? parseFloat(val) : val
  return 'Rp ' + num.toLocaleString('id-ID')
}

// Format Date Time
const formatDateTime = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  }) + `, ${date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} WIB`
}

// Timeline Steps Status Pesanan
const timelineSteps = computed(() => {
  const status = order.value.status

  if (isCod.value) {
    return [
      {
        key: 'pending',
        title: 'Pesanan Dibuat',
        desc: formatDateTime(order.value.created_at),
        isDone: true
      },
      {
        key: 'processing',
        title: 'Pesanan Diproses',
        desc: `${formatDateTime(order.value.updated_at)} - Penjual sedang menyiapkan pesanan Anda.`,
        isDone: ['processing', 'shipped', 'completed'].includes(status)
      },
      {
        key: 'shipped',
        title: 'Siap Ketemuan',
        desc: 'Menunggu Anda dan penjual bertemu di lokasi yang disepakati.',
        isDone: ['shipped', 'completed'].includes(status)
      },
      {
        key: 'completed',
        title: 'Selesai',
        desc: 'Pesanan telah diterima dan pembayaran COD diselesaikan.',
        isDone: status === 'completed'
      }
    ]
  } else {
    // Mode Kurir Ekspedisi
    return [
      {
        key: 'pending',
        title: 'Pesanan Dibuat',
        desc: formatDateTime(order.value.created_at),
        isDone: true
      },
      {
        key: 'processing',
        title: 'Pesanan Diproses',
        desc: 'Penjual sedang mengemas barang.',
        isDone: ['processing', 'shipped', 'delivered', 'completed'].includes(status)
      },
      {
        key: 'shipped',
        title: 'Dalam Pengiriman',
        desc: order.value.tracking_number
          ? `Resi: ${order.value.tracking_number} (${order.value.shipping_method})`
          : 'Barang telah diserahkan ke kurir.',
        isDone: ['shipped', 'delivered', 'completed'].includes(status)
      },
      {
        key: 'completed',
        title: 'Selesai',
        desc: 'Pesanan telah sampai dan diterima oleh pembeli.',
        isDone: status === 'completed'
      }
    ]
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Top Bar Navigation -->
        <div class="flex items-center justify-between text-xs text-slate-600">
          <router-link to="/profile/orders"
            class="flex items-center gap-2 hover:text-blue-600 transition-colors font-medium">
            <i class="pi pi-arrow-left text-xs"></i>
            <span>Kembali</span>
          </router-link>
          <div class="flex items-center gap-1.5 text-slate-500 font-medium">
            <i class="pi pi-lock text-xs"></i>
            <span>Checkout Keamanan SSL</span>
          </div>
        </div>

        <!-- Section Header Detail Pesanan -->
          <div class="space-y-1">
            <h1 class="text-xl font-bold text-slate-800">Detail Pesanan</h1>
            <p class="text-xs text-slate-500">
              Dibuat pada {{ formatDateTime(order.created_at) }} • <span class="font-semibold text-slate-700">Order #{{
                order.order_number }}</span>
            </p>
          </div>

          <!-- Main Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

              <!-- KOLOM KIRI (Detail Status, Lokasi & Rincian Produk) -->
                <div class="lg:col-span-8 space-y-6">

                  <!-- Section 1: Timeline Status Pesanan -->
                    <div class="bg-white rounded p-6 shadow-sm border border-slate-100 space-y-6">
                      <h2 class="font-bold text-slate-800 text-sm border-b border-slate-100 pb-4">Status Pesanan</h2>

                      <!-- Timeline Vertical -->
                        <div
                          class="relative pl-6 space-y-8 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
                          <div v-for="(step, idx) in timelineSteps" :key="step.key"
                            class="relative flex items-start gap-4">
                            <!-- Indicator Circle Icon -->
                              <div :class="[
                                'absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] ring-4 ring-white z-10 transition-colors',
                                step.isDone
                                  ? 'bg-blue-600 text-white'
                                  : 'bg-slate-100 text-slate-400 border border-slate-200'
                              ]">
                                <i v-if="step.isDone" class="pi pi-check"></i>
                                <i v-else-if="step.key === 'shipped' && isCod" class="pi pi-map-marker"></i>
                                <i v-else class="pi pi-circle-fill text-[6px]"></i>
                              </div>

                              <!-- Text Status -->
                                <div class="space-y-0.5">
                                  <h3 :class="['text-xs font-bold', step.isDone ? 'text-slate-800' : 'text-slate-400']">
                                    {{ step.title }}
                                  </h3>
                                  <p
                                    :class="['text-[11px] leading-relaxed', step.isDone ? 'text-slate-500' : 'text-slate-400']">
                                    {{ step.desc }}
                                  </p>
                                </div>
                          </div>
                        </div>
                    </div>

                    <!-- Section 2: Detail Ketemuan (COD) atau Alamat Pengiriman (Kurir) -->
                      <div class="bg-white rounded p-6 shadow-sm border border-slate-100 space-y-4">
                        <div
                          class="flex items-center gap-2 font-bold text-slate-800 text-sm border-b border-slate-100 pb-4">
                          <i :class="[isCod ? 'pi pi-map-marker text-blue-600' : 'pi pi-truck text-blue-600']"></i>
                          <h2>{{ isCod ? 'Detail Ketemuan (COD)' : 'Detail Pengiriman' }}</h2>
                        </div>

                        <!-- Grid Meta Informasi -->
                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                              <span class="text-slate-400 block mb-0.5">Metode</span>
                              <span class="font-semibold text-slate-800">{{ order.payment_method }}</span>
                            </div>
                            <div>
                              <span class="text-slate-400 block mb-0.5">Nama Penerima</span>
                              <span class="font-semibold text-slate-800">{{ order.buyer?.name || 'Budi Santoso'
                              }}</span>
                            </div>
                          </div>

                          <!-- Info Lokasi -->
                            <div class="space-y-1 text-xs pt-2">
                              <span class="text-slate-400 block">{{ isCod ? 'Lokasi Ketemuan' : 'Alamat Pengiriman'
                              }}</span>
                              <p v-if="isCod && order.notes" class="font-bold text-slate-800 text-sm">{{ order.notes }}
                              </p>
                              <p class="text-slate-600 leading-relaxed">{{ order.shipping_address }}</p>
                            </div>

                            <!-- Map Visual Mockup (Hanya untuk COD) -->
                              <div v-if="isCod" class="pt-2">
                                <div
                                  class="w-full h-40 bg-slate-100 rounded border border-slate-200 overflow-hidden relative flex items-center justify-center">
                                  <!-- Image Mockup Map -->
                                    <div
                                      class="absolute inset-0 bg-slate-200 opacity-60 bg-[radial-gradient(#linear-gradient,#000_1px,transparent_1px)] bg-size-[16px_16px]">
                                    </div>
                                    <div
                                      class="relative bg-white px-4 py-2 rounded shadow-md border border-slate-100 flex items-center gap-2">
                                      <i class="pi pi-map-marker text-rose-500 text-base"></i>
                                      <span class="text-xs font-bold text-slate-800">{{ order.notes || 'Lokasi Ketemuan'
                                      }}</span>
                                    </div>
                                </div>
                              </div>
                      </div>

                      <!-- Section 3: Rincian Produk -->
                        <div class="bg-white rounded p-6 shadow-sm border border-slate-100 space-y-4">
                          <div
                            class="flex items-center gap-2 font-bold text-slate-800 text-sm border-b border-slate-100 pb-4">
                            <i class="pi pi-box text-blue-600"></i>
                            <h2>Rincian Produk</h2>
                          </div>

                          <!-- List Item Barang -->
                            <div class="divide-y divide-slate-100">
                              <div v-for="item in order.items" :key="item.id"
                                class="py-4 first:pt-0 last:pb-0 flex items-center gap-4">
                                <img
                                  :src="item.product?.images?.[0]?.url || 'https://primefaces.org/cdn/primevue/images/galleria/galleria1.jpg'"
                                  :alt="item.product?.name"
                                  class="w-16 h-16 rounded object-cover border border-slate-100 shrink-0" />
                                <div class="flex-1 min-w-0">
                                  <h3 class="text-xs font-bold text-slate-800 truncate">{{ item.product?.name }}</h3>
                                  <p class="text-[11px] text-slate-400 mt-0.5">
                                    Varian: {{ item.id === 1 ? 'Coklat Natural' : 'Biji Kopi' }}
                                  </p>
                                  <p class="text-xs text-slate-500 mt-1">
                                    {{ item.quantity }} x {{ formatCurrency(item.price_snapshot) }}
                                  </p>
                                </div>
                                <div class="text-right font-bold text-xs text-slate-800">
                                  {{ formatCurrency(parseFloat(item.price_snapshot) * item.quantity) }}
                                </div>
                              </div>
                            </div>
                        </div>

                </div>

                <!-- KOLOM KANAN (Ringkasan Belanja, Aksinya, & Info Toko) -->
                  <div class="lg:col-span-4 space-y-6">

                    <!-- Card 1: Ringkasan Belanja -->
                      <div class="bg-white rounded p-6 shadow-sm border border-slate-100 space-y-4">
                        <h2 class="font-bold text-slate-800 text-sm">Ringkasan Belanja</h2>

                        <div class="space-y-2 text-xs border-b border-slate-100 pb-4">
                          <div class="flex items-center justify-between text-slate-600">
                            <span>Total Harga ({{ totalItemsCount }} Barang)</span>
                            <span class="font-medium text-slate-800">{{ formatCurrency(order.subtotal) }}</span>
                          </div>
                          <div class="flex items-center justify-between text-slate-600">
                            <span>Ongkos Kirim {{ isCod ? '(Ketemuan)' : '' }}</span>
                            <span class="font-medium text-slate-800">{{ formatCurrency(order.shipping_cost) }}</span>
                          </div>
                          <div class="flex items-center justify-between text-slate-600">
                            <span>Biaya Layanan</span>
                            <span class="font-medium text-slate-800">{{ formatCurrency(serviceFee) }}</span>
                          </div>
                        </div>

                        <!-- Total Tagihan -->
                          <div class="flex items-center justify-between pt-1">
                            <span class="text-xs font-bold text-slate-800">Total Tagihan</span>
                            <span class="text-lg font-bold text-blue-600">{{ formatCurrency(order.total_amount)
                            }}</span>
                          </div>

                          <!-- Alert Cash Notice (Khusus COD) -->
                            <div v-if="isCod"
                              class="bg-amber-50 rounded p-3 border border-amber-100 flex items-start gap-2 text-[11px] text-amber-800 leading-relaxed">
                              <i class="pi pi-info-circle text-amber-600 mt-0.5 text-xs shrink-0"></i>
                              <span>Harap siapkan uang tunai sejumlah total tagihan saat bertemu dengan penjual.</span>
                            </div>

                            <!-- Action Buttons -->
                              <div class="space-y-2 pt-2">
                                <Button label="Hubungi Penjual"
                                  class="w-full bg-blue-600! border-blue-600! py-2!.5 text-xs! font-bold! rounded! hover:bg-blue-700!" />
                                <Button label="Bantuan Pesanan" severity="secondary" outlined
                                  class="w-full text-xs! font-bold! py-2!.5 rounded! border-slate-300! text-slate-700!" />
                              </div>
                      </div>

                      <!-- Card 2: Informasi Toko Penjual -->
                        <div class="bg-white rounded p-5 shadow-sm border border-slate-100 flex items-center gap-3">
                          <img
                            :src="order.shop?.logo || 'https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png'"
                            :alt="order.shop?.name"
                            class="w-12 h-12 rounded-full object-cover border border-slate-100 shrink-0" />
                          <div class="min-w-0">
                            <h3 class="text-xs font-bold text-slate-800 truncate">{{ order.shop?.name }}</h3>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                              <span class="flex items-center gap-1 text-amber-500 font-semibold">
                                <i class="pi pi-star-fill text-[10px]"></i> 4.9
                              </span>
                              <span>•</span>
                              <span>Bandung</span>
                            </div>
                          </div>
                        </div>

                  </div>

            </div>

    </div>
  </div>
</template>