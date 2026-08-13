<script setup lang="ts">
import { getApiErrorMessage } from '@/services/apiError'
import { adminProductService } from '@/services/adminProductService'
import { adminPaymentService } from '@/services/adminPaymentService'
import type { Payment, Product } from '@/types/entities'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { useToast } from 'primevue/usetoast'
import { computed, onMounted, ref } from 'vue'
import AdminProductApproveModal from '../components/product-verification/AdminProductApproveModal.vue'
import AdminProductDetailModal from '../components/product-verification/AdminProductDetailModal.vue'
import AdminProductFilter from '../components/product-verification/AdminProductFilter.vue'
import AdminProductRejectModal from '../components/product-verification/AdminProductRejectModal.vue'
import AdminProductTable from '../components/product-verification/AdminProductTable.vue'

const toast = useToast()

const isLoading = ref(true)
const isError = ref(false)
const errorMessage = ref('')
const activeTab = ref('pending')
const verificationTab = ref<'payments' | 'products'>('payments')
const payments = ref<Payment[]>([])
const selectedProof = ref<string | null>(null)
const proofDialogVisible = ref(false)
const products = ref<Partial<Product>[]>([])
const selectedProduct = ref<Partial<Product> | null>(null)

const showDetailModal = ref(false)
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const isSubmitting = ref(false)

const pendingCount = computed(() => products.value.filter((p) => p.status === 'pending').length)
const pendingPaymentCount = computed(() => payments.value.length)
const formatCurrency = (value: string | number) => new Intl.NumberFormat('id-ID', {
  style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(value) || 0)
const filteredProducts = computed(() => products.value.filter((p) => p.status === activeTab.value))

const fetchProducts = async () => {
  isLoading.value = true
  isError.value = false
  errorMessage.value = ''

  try {
    const response = await adminProductService.listPending({ per_page: 100 })
    products.value = response.data
  } catch (error) {
    isError.value = true
    errorMessage.value = getApiErrorMessage(error, 'Gagal memuat data verifikasi produk.')
  } finally {
    isLoading.value = false
  }
}

const fetchPayments = async () => {
  try {
    const response = await adminPaymentService.listPending({ per_page: 100 })
    payments.value = response.data
  } catch (error) {
    isError.value = true
    errorMessage.value = getApiErrorMessage(error, 'Gagal memuat pembayaran pending.')
  }
}

const verifyPayment = async (payment: Payment) => {
  try {
    await adminPaymentService.verify(payment.id)
    payments.value = payments.value.filter((item) => item.id !== payment.id)
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Pembayaran berhasil diverifikasi.', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: getApiErrorMessage(error, 'Pembayaran gagal diverifikasi.'), life: 4000 })
  }
}

const rejectPayment = async (payment: Payment) => {
  try {
    await adminPaymentService.reject(payment.id, { rejection_reason: 'Bukti pembayaran tidak valid.' })
    payments.value = payments.value.filter((item) => item.id !== payment.id)
    toast.add({ severity: 'warn', summary: 'Ditolak', detail: 'Pembayaran ditolak.', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: getApiErrorMessage(error, 'Pembayaran gagal ditolak.'), life: 4000 })
  }
}

const openDetail = (product: Partial<Product>) => {
  selectedProduct.value = product
  showDetailModal.value = true
}

const openApprove = (product: Partial<Product>) => {
  selectedProduct.value = product
  showApproveModal.value = true
}

const openReject = (product: Partial<Product>) => {
  selectedProduct.value = product
  showRejectModal.value = true
}

const executeApprove = async () => {
  if (!selectedProduct.value?.id || selectedProduct.value.status !== 'pending' || isSubmitting.value) return

  isSubmitting.value = true
  try {
    const approvedProduct = await adminProductService.approve(selectedProduct.value.id)
    products.value = products.value.filter((product) => product.id !== approvedProduct.id)

    showApproveModal.value = false
    showDetailModal.value = false
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Produk disetujui untuk ditayangkan.', life: 3000 })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: getApiErrorMessage(error, 'Gagal menyetujui produk.'),
      life: 3000,
    })
  } finally {
    isSubmitting.value = false
  }
}

const executeReject = async (payload: { reason: string }) => {
  if (!selectedProduct.value?.id || selectedProduct.value.status !== 'pending' || isSubmitting.value) return

  isSubmitting.value = true
  try {
    const rejectedProduct = await adminProductService.reject(selectedProduct.value.id, {
      rejection_reason: payload.reason,
    })

    products.value = products.value.filter((product) => product.id !== rejectedProduct.id)

    showRejectModal.value = false
    showDetailModal.value = false
    toast.add({ severity: 'warn', summary: 'Ditolak', detail: 'Pengajuan produk telah ditolak.', life: 3000 })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: getApiErrorMessage(error, 'Gagal menolak produk.'),
      life: 3000,
    })
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchProducts()
  fetchPayments()
})
</script>

<template>
  <div class="relative min-h-[80vh]">

    <div v-if="isLoading"
      class="fixed inset-0 z-100 flex flex-col items-center justify-center bg-white/90 backdrop-blur-xs transition-all duration-300">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
      <span class="mt-4 text-sm font-semibold text-slate-600 animate-pulse">Memuat pengajuan produk...</span>
    </div>

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Pusat Verifikasi</h1>
      <p class="text-sm text-slate-500 mt-1">Periksa pembayaran buyer dan pengajuan produk seller.</p>
    </div>

    <Message v-if="isError" severity="error" class="mb-4">{{ errorMessage }}</Message>

    <div class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-sm">
      <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold transition-colors"
        :class="verificationTab === 'payments' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-50'"
        @click="verificationTab = 'payments'">
        <i class="pi pi-wallet mr-2"></i>Pembayaran
        <span class="ml-1">({{ pendingPaymentCount }})</span>
      </button>
      <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold transition-colors"
        :class="verificationTab === 'products' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-50'"
        @click="verificationTab = 'products'">
        <i class="pi pi-box mr-2"></i>Produk
        <span class="ml-1">({{ pendingCount }})</span>
      </button>
    </div>

    <div v-if="verificationTab === 'payments'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
      <div class="border-b border-slate-100 p-5">
        <h2 class="font-bold text-slate-800">Pembayaran Menunggu Verifikasi</h2>
        <p class="mt-1 text-xs text-slate-500">Pastikan bukti transfer sesuai sebelum pesanan diproses seller.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr><th class="px-5 py-3">Pesanan</th><th class="px-4 py-3">Buyer</th><th class="px-4 py-3">Toko</th><th class="px-4 py-3">Nominal</th><th class="px-4 py-3">Bukti</th><th class="px-5 py-3 text-right">Aksi</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="payment in payments" :key="payment.id" class="hover:bg-slate-50/70">
              <td class="px-5 py-4 font-semibold text-slate-800">{{ payment.order?.order_number || `Order #${payment.order_id}` }}</td>
              <td class="px-4 py-4 text-slate-600">{{ payment.order?.buyer?.name || '—' }}</td>
              <td class="px-4 py-4 text-slate-600">{{ payment.order?.shop?.name || '—' }}</td>
              <td class="px-4 py-4 font-semibold text-blue-600">{{ formatCurrency(payment.amount) }}</td>
              <td class="px-4 py-4">
                <Button v-if="payment.proof_image" label="Lihat bukti" icon="pi pi-image" text size="small" @click="selectedProof = payment.proof_image; proofDialogVisible = true" />
                <span v-else class="text-xs text-slate-400">Belum ada</span>
              </td>
              <td class="px-5 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <Button label="Tolak" severity="danger" outlined size="small" @click="rejectPayment(payment)" />
                  <Button label="Verifikasi" icon="pi pi-check" size="small" @click="verifyPayment(payment)" />
                </div>
              </td>
            </tr>
            <tr v-if="!payments.length"><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Tidak ada pembayaran yang menunggu verifikasi.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="verificationTab === 'products'">
      <AdminProductFilter v-model:activeTab="activeTab" :pendingCount="pendingCount" />

      <AdminProductTable :products="filteredProducts" @viewDetail="openDetail" @approve="openApprove" @reject="openReject" />
    </div>

    <AdminProductDetailModal v-model:visible="showDetailModal" :product="selectedProduct"
      @approve="openApprove(selectedProduct!)" @reject="openReject(selectedProduct!)" />

    <AdminProductApproveModal v-model:visible="showApproveModal" :product="selectedProduct" :loading="isSubmitting" @confirm="executeApprove" />

    <AdminProductRejectModal v-model:visible="showRejectModal" :product="selectedProduct" @confirm="executeReject" />

    <Dialog v-model:visible="proofDialogVisible" modal header="Bukti Pembayaran" :style="{ width: 'min(560px, 92vw)' }">
      <img v-if="selectedProof" :src="selectedProof" alt="Bukti pembayaran" class="max-h-[70vh] w-full rounded-xl object-contain" />
    </Dialog>
  </div>
</template>
