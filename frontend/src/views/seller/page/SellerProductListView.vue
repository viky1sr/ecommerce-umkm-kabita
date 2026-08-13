<script setup lang="ts">
import type { Product } from '@/types/entities'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Paginator from 'primevue/paginator'
import Tag from 'primevue/tag'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { sellerProductService } from '@/services/sellerProductService'
import { getApiErrorMessage } from '@/services/apiError'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()
const isLoading = ref(true)
const searchQuery = ref('')
const selectedStatus = ref<string>('all')

const statusOptions = [
  { label: 'Semua Status', value: 'all' },
  { label: 'Aktif / Approved', value: 'approved' },
  { label: 'Menunggu / Pending', value: 'pending' },
  { label: 'Ditolak / Rejected', value: 'rejected' }
]

const products = ref<Partial<Product>[]>([])

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const result = await sellerProductService.list({ search: searchQuery.value, status: selectedStatus.value })
    products.value = result.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal memuat produk', detail: getApiErrorMessage(error), life: 3500 })
  } finally { isLoading.value = false }
}

const filteredProducts = computed(() => {
  return products.value.filter((p) => {
    const matchSearch = p.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchStatus = selectedStatus.value === 'all' || p.status === selectedStatus.value
    return matchSearch && matchStatus
  })
})

const getStatusSeverity = (status?: string) => {
  switch (status) {
    case 'approved': return 'success'
    case 'pending': return 'warn'
    case 'rejected': return 'danger'
    default: return 'info'
  }
}

const formatRupiah = (val?: string | number) => {
  const num = Number(val || 0)
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
}

const navigateToAdd = () => {
  router.push('/seller/produk/tambah')
}

const navigateToEdit = (id: number) => {
  const product = products.value.find((item) => item.id === id)
  if (product?.slug) router.push(`/seller/produk/edit/${product.slug}`)
}

const removeProduct = async (product: Partial<Product>) => {
  if (!product.slug || !window.confirm(`Hapus produk ${product.name}?`)) return
  try {
    await sellerProductService.remove(product.slug)
    products.value = products.value.filter((item) => item.id !== product.id)
    toast.add({ severity: 'success', summary: 'Produk dihapus', detail: 'Produk berhasil dihapus dari toko.', life: 2500 })
  } catch (error) { toast.add({ severity: 'error', summary: 'Gagal menghapus', detail: getApiErrorMessage(error), life: 3500 }) }
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="space-y-6">
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="flex flex-wrap items-center gap-3 flex-1">
        <div class="relative flex-1 min-w-55">
          <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <InputText v-model="searchQuery" placeholder="Cari nama produk..."
            class="w-full! pl-10! pr-4! py-2!.5 bg-slate-50! border-slate-200! rounded-xl! text-sm! focus:bg-white!" />
        </div>
        <Dropdown v-model="selectedStatus" :options="statusOptions" optionLabel="label" optionValue="value"
          placeholder="Status" class="w-48! bg-slate-50! border-slate-200! rounded-xl! text-sm!" />
      </div>

      <Button label="Tambah Produk" icon="pi pi-plus"
        class="bg-blue-600! hover:bg-blue-700! border-none! px-5! py-2!.5 rounded-xl! text-sm! font-semibold! shadow-sm! shrink-0"
        @click="navigateToAdd" />
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <DataTable :value="filteredProducts" :loading="isLoading" class="p-datatable-sm">
        <template #empty>
          <div class="p-8 text-center text-slate-500 text-sm">Tidak ada produk ditemukan.</div>
        </template>

        <Column header="Produk">
          <template #body="{ data }">
            <div class="flex items-center gap-3 py-1">
              <img :src="data.images?.[0]?.url || 'https://via.placeholder.com/60'" alt="Produk"
                class="w-12 h-12 rounded-lg object-cover border border-slate-100 shrink-0" />
              <div>
                <p class="text-sm font-semibold text-slate-800 line-clamp-1">{{ data.name }}</p>
                <p class="text-xs text-slate-400">{{ data.category?.name || 'Tanpa Kategori' }}</p>
              </div>
            </div>
          </template>
        </Column>

        <Column header="Harga" style="width: 160px">
          <template #body="{ data }">
            <span class="text-sm font-bold text-slate-700">{{ formatRupiah(data.price) }}</span>
          </template>
        </Column>

        <Column header="Stok" style="width: 100px">
          <template #body="{ data }">
            <span class="text-sm font-medium text-slate-600">{{ data.stock }} pcs</span>
          </template>
        </Column>

        <Column header="Status" style="width: 140px">
          <template #body="{ data }">
            <Tag :severity="getStatusSeverity(data.status)" class="text-xs! px-2!.5 py-1! rounded-md!">
              {{ data.status?.toUpperCase() }}
            </Tag>
          </template>
        </Column>

        <Column header="Aksi" style="width: 120px" class="text-center">
          <template #body="{ data }">
            <div class="flex items-center justify-center gap-2">
              <Button icon="pi pi-pencil" severity="secondary" text rounded class="w-8! h-8!"
                @click="navigateToEdit(data.id)" />
              <Button icon="pi pi-trash" severity="danger" text rounded class="w-8! h-8!" @click="removeProduct(data)" />
            </div>
          </template>
        </Column>
      </DataTable>

      <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <span>Menampilkan {{ filteredProducts.length }} dari {{ products.length }} produk</span>
        <Paginator :rows="10" :totalRecords="filteredProducts.length" />
      </div>
    </div>
  </div>
</template>
