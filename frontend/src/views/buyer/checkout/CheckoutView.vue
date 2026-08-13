<script setup lang="ts">
import CODAddressModal from '@/components/cod/CODAddressModal.vue'
import { buyerPaymentService } from '@/services/buyerPaymentService'
import { checkoutService } from '@/services/checkoutService'
import { locationService } from '@/services/locationService'
import { useCartStore } from '@/stores/cart'
import type { Cart, CodLocation } from '@/types'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const cartStore = useCartStore()
const checkoutItems = ref<Cart[]>([])
const shippingByItem = ref<Record<number, string>>({})

const loadCheckoutItems = () => {
  const raw = localStorage.getItem('checkoutItems')
  if (raw) {
    checkoutItems.value = JSON.parse(raw) as Cart[]
  }
}

const flatItems = computed(() => {
  return checkoutItems.value.flatMap((cart) =>
    cart.groups_by_shop.flatMap((group) =>
      group.items.map((item) => ({
        ...item,
        shop: group.shop,
        selectedShipping: shippingByItem.value[item.id] ?? 'reguler_15',
      }))
    )
  )
})

const setShipping = (itemId: number, value: string) => {
  shippingByItem.value[itemId] = value
}

// --- STATE LOADING SIMULASI API GET ---
const isLoadingPage = ref(true)

// --- STATE SIMULASI POST PESANAN ---
const isSubmittingOrder = ref(false)
const isSuccessModalOpen = ref(false)
const createdOrderData = ref({
  orderNumber: '',
  totalAmount: 0,
  paymentMethod: ''
})

// --- DATA STATE CHECKOUT ---
// interface AddressItem {
//   id: number
//   name: string
//   phone: string
//   fullAddress: string
//   isPrimary: boolean
// }

const listAddress = ref<CodLocation[]>([])
const selectedAddressId = ref<number | null>(null)
const selectedAddress = ref<CodLocation | null>(null)

const activeAddress = computed(() => {
  return selectedAddress.value ?? listAddress.value[0] ?? null
})

// Modal / Dialog States Alamat
const isAddressModalOpen = ref(false)
const isAddNewFormOpen = ref(false)
const showEditModal = ref(false)
const addressModalMode = ref<'add' | 'edit'>('edit')

const newAddress = ref<CodLocation>({
  name: "",
  address: "",
  phone: "",
  latitude: "",
  longitude: "",
  is_default: false,
  id: 0
})

const selectAddress = (id: number) => {
  selectedAddressId.value = id
  selectedAddress.value = listAddress.value.find((addr) => addr.id === id) ?? null
  isAddressModalOpen.value = false
}

const openAddAddress = () => {
  addressModalMode.value = 'add'
  isAddressModalOpen.value = false
  showEditModal.value = true
}

const openEditAddress = () => {
  if (!selectedAddress.value) return openAddAddress()
  addressModalMode.value = 'edit'
  isAddressModalOpen.value = false
  showEditModal.value = true
}

const saveNewAddress = async () => {
  if (!newAddress.value.name || !newAddress.value.phone || !newAddress.value.address) {
    alert('Harap isi semua kolom alamat!')
    return
  }


  await locationService.create({
    name: newAddress.value.name,
    address: newAddress.value.address,
    phone: newAddress.value.phone,
    latitude: newAddress.value.latitude,
    longitude: newAddress.value.longitude,
    is_default: newAddress.value.is_default
  })

  newAddress.value = {
    id: 0,
    name: "",
    address: "",
    phone: "",
    latitude: "",
    longitude: "",
    is_default: false,
  }
  isAddNewFormOpen.value = false
  isAddressModalOpen.value = false
}

// Data Pengiriman & Pesanan
const shippingOptions = [
  { label: 'Reguler (2-3 hari) - Rp 15.000', value: 'reguler_15' },
  { label: 'Reguler (2-3 hari) - Rp 20.000', value: 'reguler_20' },
  { label: 'Kargo (3-5 hari) - Rp 30.000', value: 'cargo_30' }
]

const paymentOptions = [
  { label: 'Transfer Bank Manual', value: 'transfer' },
  { label: 'COD', value: 'cod' }
]

const selectedShippingValue = computed(() => {
  const first = flatItems.value[0]?.selectedShipping
  return typeof first === 'string' ? first : 'reguler_15'
})

const shippingLabel = computed(() => {
  const option = shippingOptions.find((item) => item.value === selectedShippingValue.value)
  return option?.label ?? 'Reguler'
})

const shippingMethod = computed<'kurir' | 'cod'>(() => (selectedShippingValue.value.startsWith('cargo') ? 'kurir' : 'kurir'))

const orderNotes = computed(() => {
  const labels = flatItems.value.map((item) => item.selectedShipping).filter(Boolean)
  const unique = Array.from(new Set(labels))
  return unique.length ? `Ongkir: ${unique.join(', ')}` : null
})

const selectedPaymentMethod = ref<'transfer' | 'cod'>('transfer')

const paymentMethodLabel = computed(() => (selectedPaymentMethod.value === 'cod' ? 'COD' : 'Transfer Bank Manual'))

const paymentInfo = ref({
  bankName: 'Bank BCA',
  accountNumber: '123 456 7890',
  accountHolder: 'PT Kabita Indonesia'
})

const isCopied = ref(false)

const copyAccountNumber = () => {
  navigator.clipboard.writeText(paymentInfo.value.accountNumber.replace(/\s/g, ''))
  isCopied.value = true
  setTimeout(() => (isCopied.value = false), 2000)
}

const formatCurrency = (val: number) => {
  return 'Rp ' + val.toLocaleString('id-ID')
}

// Total Calc
const totalItemsPrice = computed(() => {
  return flatItems.value.reduce((sum, g) => sum + (g.product?.price as number) * g.quantity, 0)
})
const totalShippingCost = 35000
const serviceFee = 2500
const grandTotal = computed(() => totalItemsPrice.value + totalShippingCost + serviceFee)

// --- STATE & SIMULASI UPLOAD BUKTI TRANSFER ---
const fileInput = ref<HTMLInputElement | null>(null)
const previewImage = ref<string | null>(null)
const isUploading = ref(false)
const uploadProgress = ref(0)
const isSuccess = ref(false)

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    processUpload(target.files[0])
  }
}

const handleDrop = (event: DragEvent) => {
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    processUpload(event.dataTransfer.files[0])
  }
}

const processUpload = (file: File) => {
  if (!file.type.startsWith('image/')) {
    alert('Harap unggah file gambar (.PNG, .JPG, .JPEG)')
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    previewImage.value = e.target?.result as string
  }
  reader.readAsDataURL(file)

  isUploading.value = true
  isSuccess.value = false
  uploadProgress.value = 0

  const interval = setInterval(() => {
    uploadProgress.value += 20
    if (uploadProgress.value >= 100) {
      clearInterval(interval)
      isUploading.value = false
      isSuccess.value = true
    }
  }, 200)
}

const removeImage = () => {
  previewImage.value = null
  isSuccess.value = false
  uploadProgress.value = 0
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

// --- SIMULASI GET DATA DARI API ---
const fetchCheckoutData = () => {
  isLoadingPage.value = true
  loadLocations();
  setTimeout(() => {

    // orderGroups.value = [
    //   {
    //     storeName: 'Toko Kopi Lokal',
    //     productName: 'Biji Kopi Arabika Gayo - 250g',
    //     variant: 'Medium Roast',
    //     price: 85000,
    //     qty: 1,
    //     image: 'https://primefaces.org/cdn/primevue/images/galleria/galleria1.jpg',
    //     selectedShipping: 'reguler_15'
    //   },
    //   {
    //     storeName: 'Kerajinan Tangan Jabar',
    //     productName: 'Keranjang Anyaman Rotan Premium',
    //     variant: 'Ukuran: Sedang',
    //     price: 45000,
    //     qty: 2,
    //     image: 'https://primefaces.org/cdn/primevue/images/galleria/galleria2.jpg',
    //     selectedShipping: 'reguler_20'
    //   }
    // ]

    isLoadingPage.value = false
  }, 1000)
}


const loadLocations = async () => {
  try {
    const data = await locationService.list()
    if (data.data.length > 0) {
      listAddress.value = data.data
      selectedAddress.value = data.data.find(addr => addr.is_default) ?? null
    }
  } catch (error) {
    console.error('Gagal memuat alamat COD', error)
  }
}

// --- SIMULASI POST PESANAN KE API ---
const handleCreateOrder = async () => {
  if (!activeAddress.value) {
    alert('Pilih alamat pengiriman terlebih dahulu.')
    return
  }

  isSubmittingOrder.value = true

  try {
    const payload: Parameters<typeof checkoutService.checkout>[0] = {
      // Laravel checkout validates cart_items against cart_items.id,
      // not products.id.
      cart_items: flatItems.value.map((item) => item.id),
      shipping_method: shippingMethod.value,
      payment_method: selectedPaymentMethod.value,
      shipping_address: activeAddress.value.address ?? '',
      notes: orderNotes.value
    }

    const order = await checkoutService.checkout(payload)

    // The backend checkout consumes the selected cart items. Refresh the
    // shared cart state so the header badge and /cart page are immediately
    // consistent after a successful order.
    await cartStore.loadCart()
    localStorage.removeItem('checkoutItems')

    if (previewImage.value && order.id) {
      try {
        await buyerPaymentService.uploadProof(order.id, {
          proof_image: previewImage.value as any
        })
      } catch (uploadError) {
        console.error('Gagal mengunggah bukti transfer', uploadError)
      }
    }

    createdOrderData.value = {
      orderNumber: order.order_number ?? `#KBTA-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}`,
      totalAmount: Number(order.total_amount ?? grandTotal.value),
      paymentMethod: paymentMethodLabel.value
    }

    isSuccessModalOpen.value = true
  } catch (error) {
    console.error('Gagal membuat pesanan', error)
    alert('Gagal membuat pesanan. Coba lagi.')
  } finally {
    isSubmittingOrder.value = false
  }
}

const navigateToOrders = () => {
  isSuccessModalOpen.value = false
  router.push('/profile/orders')
}

const navigateToHome = () => {
  isSuccessModalOpen.value = false
  router.push('/')
}

onMounted(() => {
  loadCheckoutItems()
  fetchCheckoutData()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 py-6 px-4 sm:px-6 lg:px-8">

    <!-- LOADING SPINNER MINGGIR / BULAT UTAMA -->
    <div v-if="isLoadingPage" class="min-h-[70vh] flex flex-col items-center justify-center gap-3">
      <ProgressSpinner style="width: 48px; height: 48px" strokeWidth="4" animationDuration=".8s" />
      <span class="text-xs text-slate-500 font-medium">Memuat Data Checkout...</span>
    </div>

    <!-- KONTEN UTAMA DITAMPILKAN SETELAH LOADING -->
    <div v-else class="max-w-7xl mx-auto space-y-6">

      <!-- Top Bar Navigation -->
      <div class="flex items-center justify-between gap-3 text-xs text-slate-600">
        <router-link to="/cart" class="flex items-center gap-2 hover:text-blue-600 transition-colors font-medium">
          <i class="pi pi-arrow-left text-xs"></i>
          <span>Kembali</span>
        </router-link>
        <div class="flex items-center gap-1.5 text-slate-500 font-medium">
          <i class="pi pi-lock text-xs"></i>
          <span class="hidden sm:inline">Checkout Keamanan SSL</span>
        </div>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- KOLOM KIRI (Alamat + Ringkasan Pesanan) -->
        <div class="lg:col-span-7 space-y-6">

          <!-- Section 1: Alamat Pengiriman -->
          <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div class="flex items-center gap-2 font-bold text-slate-800 text-sm">
                <i class="pi pi-map-marker text-blue-600"></i>
                <h2>Alamat Pengiriman</h2>
              </div>
              <button @click="isAddressModalOpen = true" class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                Ubah Alamat
              </button>
            </div>

            <!-- Display Alamat Aktif -->
            <div class="space-y-1 text-xs text-slate-600">
              <p class="font-bold text-slate-800 text-sm">{{ activeAddress?.name }}</p>
              <p class="text-slate-500">{{ activeAddress?.phone }}</p>
              <p class="leading-relaxed mt-2 text-slate-600">{{ activeAddress?.address }}</p>
            </div>
          </div>

          <!-- Section 2: Ringkasan Pesanan -->
          <div class="space-y-5 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex items-center gap-2 font-bold text-slate-800 text-sm pb-4 border-b border-slate-100">
              <i class="pi pi-shopping-bag text-blue-600"></i>
              <h2>Ringkasan Pesanan</h2>
            </div>

            <!-- List Toko & Barang -->
            <div v-for="(group, idx) in flatItems" :key="idx"
              class="space-y-4 pb-6 border-b border-slate-100 last:border-0 last:pb-0">
              <div class="flex min-w-0 items-center gap-2 text-xs font-bold text-slate-700">
                <i class="pi pi-shop text-slate-400"></i>
                <span class="truncate">{{ group.shop?.name }}</span>
              </div>

              <div class="flex min-w-0 items-start gap-3 sm:gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50 text-[10px] text-slate-400">
                  <img v-if="group.product?.images?.[0]?.url" :src="group.product.images[0].url!" :alt="group.product?.name"
                    class="h-full w-full object-cover" />
                  <span v-else>Tanpa gambar</span>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-xs font-bold text-slate-800 truncate">{{ group.product?.name }}</h3>
                  <p class="text-xs text-slate-500 mt-2">
                    {{ group.quantity }} x {{ formatCurrency(group.product?.price as number) }}
                  </p>
                </div>
                <div class="shrink-0 text-right text-xs font-bold text-slate-800">
                  {{ formatCurrency(group.product?.price as number * group.quantity) }}
                </div>
              </div>

              <div class="min-w-0 space-y-1.5 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                <label class="text-[11px] text-slate-500 block">Pilih Pengiriman</label>
                <Select :modelValue="group.selectedShipping" :options="shippingOptions" optionLabel="label"
                  optionValue="value" class="w-full min-w-0! text-xs! bg-white! rounded-lg!"
                  @update:modelValue="setShipping(group.id, $event)" />
              </div>
            </div>
          </div>

        </div>

        <!-- KOLOM KANAN (Metode Pembayaran + Ringkasan Biaya) -->
        <div class="lg:col-span-5 space-y-6">

          <!-- Card 1: Metode Pembayaran -->
          <div class="space-y-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex items-center gap-2 font-bold text-slate-800 text-sm">
              <i class="pi pi-credit-card text-blue-600"></i>
              <h2>Metode Pembayaran</h2>
            </div>

            <div class="bg-blue-50/60 rounded p-4 border border-blue-100 space-y-3">
              <div class="flex items-center gap-2 text-xs font-bold text-blue-700">
                <i class="pi pi-info-circle text-blue-600"></i>
                <span>{{ paymentMethodLabel }}</span>
              </div>

              <div class="space-y-1.5">
                <label class="text-[11px] text-slate-500 block">Pilih Metode Pembayaran</label>
                <Select v-model="selectedPaymentMethod" :options="paymentOptions" optionLabel="label" optionValue="value"
                  class="w-full min-w-0! text-xs! bg-white! rounded-lg!" />
              </div>

              <p v-if="selectedPaymentMethod === 'transfer'" class="text-[11px] text-slate-600 leading-relaxed">
                Silakan transfer sesuai dengan total tagihan ke rekening berikut:
              </p>

              <div v-if="selectedPaymentMethod === 'transfer'" class="bg-white rounded p-3 border border-blue-100 flex items-center justify-between">
                <div>
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">{{ paymentInfo.bankName
                  }}</span>
                  <span class="text-sm font-bold text-slate-800 tracking-wide">{{ paymentInfo.accountNumber }}</span>
                  <span class="text-[11px] text-slate-500 block mt-0.5">a.n. {{ paymentInfo.accountHolder }}</span>
                </div>
                <button @click="copyAccountNumber"
                  class="text-xs text-blue-600 font-semibold flex items-center gap-1 hover:underline">
                  <i :class="[isCopied ? 'pi pi-check' : 'pi pi-copy', 'text-xs']"></i>
                  <span>{{ isCopied ? 'Tersalin' : 'Salin' }}</span>
                </button>
              </div>

              <p v-if="selectedPaymentMethod === 'cod'" class="text-[11px] text-slate-600 leading-relaxed">
                Pesanan akan diproses dengan pembayaran COD saat barang diterima.
              </p>

              <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleFileSelect" />

              <!-- Simulasi Upload Area -->
              <div v-if="!previewImage && !isUploading" @click="triggerFileInput" @dragover.prevent
                @drop.prevent="handleDrop"
                class="border-2 border-dashed border-blue-200 hover:border-blue-500 rounded p-5 bg-white flex flex-col items-center justify-center cursor-pointer transition-colors group">
                <div
                  class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                  <i class="pi pi-cloud-upload text-lg"></i>
                </div>
                <p class="text-xs font-semibold text-slate-700">Unggah Bukti Transfer</p>
                <p class="text-[10px] text-slate-400 mt-1">Klik atau seret gambar ke sini (.PNG, .JPG)</p>
              </div>

              <div v-else-if="isUploading" class="border border-blue-100 rounded p-4 bg-white space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-600 font-medium">
                  <span class="flex items-center gap-2">
                    <i class="pi pi-spin pi-spinner text-blue-600"></i>
                    Mengunggah gambar...
                  </span>
                  <span>{{ uploadProgress }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                  <div class="bg-blue-600 h-1.5 transition-all duration-200" :style="{ width: uploadProgress + '%' }">
                  </div>
                </div>
              </div>

              <div v-else-if="isSuccess && previewImage" class="space-y-2">
                <div class="relative border border-slate-200 rounded overflow-hidden bg-white group">
                  <img :src="previewImage" alt="Bukti Transfer" class="w-full h-36 object-cover" />
                  <div
                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button @click="triggerFileInput"
                      class="bg-white text-slate-800 text-xs px-3 py-1.5 rounded-lg font-semibold hover:bg-slate-100">
                      Ganti
                    </button>
                    <button @click="removeImage"
                      class="bg-rose-600 text-white text-xs px-3 py-1.5 rounded-lg font-semibold hover:bg-rose-700">
                      Hapus
                    </button>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                    <i class="pi pi-check-circle text-xs"></i>
                    <span>Berhasil diunggah</span>
                  </div>
                  <button @click="removeImage" class="text-[11px] text-rose-500 hover:underline">Hapus</button>
                </div>
              </div>

              <p class="text-[10px] text-slate-400 italic">
                *Pesanan akan diproses setelah bukti transfer dikonfirmasi oleh admin.
              </p>
            </div>
          </div>

          <!-- Card 2: Ringkasan Biaya -->
          <div class="bg-white rounded p-6 shadow-sm border border-slate-100 space-y-4">
            <h2 class="font-bold text-slate-800 text-sm">Ringkasan Biaya</h2>

            <div class="space-y-2 text-xs border-b border-slate-100 pb-4">
              <div class="flex items-center justify-between text-slate-600">
                <span>Total Harga ({{ flatItems.length }} barang)</span>
                <span class="font-medium text-slate-800">{{ formatCurrency(totalItemsPrice) }}</span>
              </div>
              <div class="flex items-center justify-between text-slate-600">
                <span>Total Ongkos Kirim</span>
                <span class="font-medium text-slate-800">{{ formatCurrency(totalShippingCost) }}</span>
              </div>
              <div class="flex items-center justify-between text-slate-600">
                <span>Biaya Layanan</span>
                <span class="font-medium text-slate-800">{{ formatCurrency(serviceFee) }}</span>
              </div>
              <div class="flex items-center justify-between text-slate-600">
                <span>Metode Pembayaran</span>
                <span class="font-medium text-slate-800">{{ paymentMethodLabel }}</span>
              </div>
              <div class="flex items-center justify-between text-slate-600">
                <span>Biaya Layanan</span>
                <span class="font-medium text-slate-800">{{ formatCurrency(serviceFee) }}</span>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1">
              <span class="text-xs font-bold text-slate-800">Total Tagihan</span>
              <span class="text-lg font-bold text-blue-600">{{ formatCurrency(grandTotal) }}</span>
            </div>

            <Button label="Buat Pesanan" :disabled="!isSuccess || isSubmittingOrder" :loading="isSubmittingOrder"
              @click="handleCreateOrder"
              class="w-full bg-blue-600! border-blue-600! py-3! text-xs! font-bold! rounded! shadow-sm! hover:bg-blue-700! disabled:opacity-50! disabled:cursor-not-allowed!" />
          </div>

        </div>

      </div>

    </div>

    <!-- DIALOG / MODAL PILIH & UBAH ALAMAT -->

    <Dialog v-model:visible="isAddressModalOpen" modal header="Pilih Alamat Pengiriman"
      :style="{ width: 'min(92vw, 560px)' }" class="rounded-2xl!">
      <div class="space-y-3">
        <p class="text-sm text-slate-500">Pilih alamat yang akan digunakan untuk pesanan ini.</p>
        <div v-if="listAddress.length" class="max-h-72 space-y-2 overflow-y-auto pr-1">
          <button v-for="address in listAddress" :key="address.id" type="button"
            class="flex w-full items-start gap-3 rounded-xl border p-4 text-left transition"
            :class="activeAddress?.id === address.id ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50'"
            @click="selectAddress(address.id!)">
            <i class="pi pi-map-marker mt-0.5 text-blue-600"></i>
            <span class="min-w-0 flex-1">
              <span class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-800">
                {{ address.name }}
                <span v-if="address.is_default" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Utama</span>
              </span>
              <span class="mt-1 block text-xs text-slate-500">{{ address.phone }}</span>
              <span class="mt-1 block break-words text-xs leading-5 text-slate-600">{{ address.address }}</span>
            </span>
            <i v-if="activeAddress?.id === address.id" class="pi pi-check-circle text-blue-600"></i>
          </button>
        </div>
        <div v-else class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">
          Belum ada alamat tersimpan.
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
          <Button label="Tutup" severity="secondary" outlined @click="isAddressModalOpen = false" />
          <Button v-if="selectedAddress" label="Edit alamat terpilih" icon="pi pi-pencil" outlined @click="openEditAddress" />
          <Button label="Tambah alamat baru" icon="pi pi-plus" @click="openAddAddress" />
        </div>
      </div>
    </Dialog>

    <CODAddressModal v-model:visible="showEditModal" :mode="addressModalMode" :codLocation="addressModalMode === 'edit' ? selectedAddress : null"
      @saved="loadLocations" />
    <!-- POPUP MODAL SUKSES PESANAN (MOCKUP GAMBAR) -->
    <Dialog v-model:visible="isSuccessModalOpen" modal :closable="false" :style="{ width: '90%', maxWidth: '480px' }"
      class="rounded-3xl! overflow-hidden">
      <div class="py-6 px-2 text-center space-y-6">

        <!-- Animated / Green Circle Check Icon -->
        <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto">
          <div class="w-14 h-14 rounded-full bg-emerald-400 text-white flex items-center justify-center shadow-sm">
            <i class="pi pi-check text-2xl font-bold"></i>
          </div>
        </div>

        <!-- Title & Subtitle -->
        <div class="space-y-2">
          <h2 class="text-lg font-extrabold text-slate-800">Pesanan Anda Telah Dibuat!</h2>
          <p class="text-xs text-slate-500 w-full mx-auto leading-relaxed">
            Terima kasih telah berbelanja di Kabita. Pesanan Anda sedang menunggu verifikasi pembayaran oleh admin.
          </p>
        </div>

        <!-- Info Card Banner -->
        <div class="bg-indigo-50/70 border border-indigo-100/80 rounded p-4 flex items-start gap-3 text-left">
          <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
            <i class="pi pi-info-circle text-xs"></i>
          </div>
          <p class="text-[11px] text-slate-600 leading-relaxed">
            Estimasi verifikasi 1×24 jam. Pastikan Anda telah mengunggah bukti bayar pada halaman detail pesanan.
          </p>
        </div>

        <!-- Summary Specs Card -->
        <div class="bg-slate-50/80 border border-slate-100 rounded p-4 grid grid-cols-3 gap-2 text-left">
          <div>
            <span class="text-[10px] text-slate-400 block mb-1">Nomor Pesanan</span>
            <span class="text-xs font-bold text-slate-800 tracking-tight">{{ createdOrderData.orderNumber }}</span>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 block mb-1">Total Pembayaran</span>
            <span class="text-xs font-bold text-blue-600">{{ formatCurrency(createdOrderData.totalAmount) }}</span>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 block mb-1">Metode Pembayaran</span>
            <span class="text-xs font-bold text-slate-800 leading-tight block">{{ createdOrderData.paymentMethod
            }}</span>
          </div>
        </div>

        <!-- Bottom Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 pt-2">
          <Button label="Lihat Detail Pesanan" icon="pi pi-file"
            class="flex-1 bg-blue-600! border-blue-600! text-xs! py-3! rounded! font-bold!" @click="navigateToOrders" />
          <Button label="Kembali ke Beranda" severity="secondary" outlined
            class="flex-1 text-xs! py-3! rounded! font-bold! border-slate-300! text-blue-600! hover:bg-slate-50!"
            @click="navigateToHome" />
        </div>

      </div>
    </Dialog>

  </div>
</template>
