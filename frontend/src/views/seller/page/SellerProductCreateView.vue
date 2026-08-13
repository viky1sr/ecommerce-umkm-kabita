<script setup lang="ts">
import Accordion from 'primevue/accordion'
import AccordionContent from 'primevue/accordioncontent'
import AccordionHeader from 'primevue/accordionheader'
import AccordionPanel from 'primevue/accordionpanel'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { categoryService } from '@/services/categoryService'
import { sellerProductService } from '@/services/sellerProductService'
import { getApiErrorMessage } from '@/services/apiError'

const router = useRouter()
const toast = useToast()

const isSubmitting = ref(false)

// Form State
const form = ref({
  name: '',
  category_id: null as number | null,
  price: null as number | null,
  cost_price: null as number | null,
  stock: null as number | null,
  weight: null as number | null,
  description: '',
  sku: '',
  images: [] as string[]
})
const imageFiles = ref<File[]>([])

const categories = ref<{ label: string; value: number }[]>([])

// Drag & Drop / Upload Image Mock Handler
const handleImageUpload = (e: Event, index?: number) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    const imageUri = URL.createObjectURL(file)
    if (typeof index === 'number' && index < form.value.images.length) {
      form.value.images[index] = imageUri
      imageFiles.value[index] = file
    } else {
      if (form.value.images.length < 5) {
        form.value.images.push(imageUri)
        imageFiles.value.push(file)
      }
    }
  }
}

const removeImage = (index: number) => {
  form.value.images.splice(index, 1)
  imageFiles.value.splice(index, 1)
}

const handleSave = async () => {
  if (!form.value.name || !form.value.category_id || !form.value.price || form.value.stock === null) {
    toast.add({
      severity: 'warn',
      summary: 'Peringatan',
      detail: 'Mohon lengkapi semua kolom wajib (*)!',
      life: 3000
    })
    return
  }

  isSubmitting.value = true
  try {
    const payload = new FormData()
    payload.append('name', form.value.name)
    payload.append('category_id', String(form.value.category_id))
    payload.append('price', String(form.value.price))
    payload.append('stock', String(form.value.stock))
    if (form.value.cost_price !== null) payload.append('cost_price', String(form.value.cost_price))
    if (form.value.weight !== null) payload.append('weight', String(form.value.weight))
    payload.append('description', form.value.description)
    imageFiles.value.forEach((file) => payload.append('images[]', file))
    await sellerProductService.create(payload)
    isSubmitting.value = false
    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: 'Produk baru berhasil ditambahkan!',
      life: 3000
    })
    router.push('/seller/produk')
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal menyimpan', detail: getApiErrorMessage(error), life: 4000 })
    isSubmitting.value = false
  }
}

const goBack = () => {
  router.push('/seller/produk')
}

onMounted(async () => {
  try {
    const response = await categoryService.list()
    categories.value = response.map((category) => ({ label: category.name, value: category.id }))
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Kategori gagal dimuat', detail: getApiErrorMessage(error), life: 3500 })
  }
})
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-12">
    <div class="flex items-center gap-2 text-xs text-slate-500">
      <span class="hover:underline cursor-pointer" @click="goBack">Produk</span>
      <i class="pi pi-chevron-right text-[10px]"></i>
      <span class="font-semibold text-slate-800">Tambah Produk Baru</span>
    </div>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Tambah Produk Baru</h1>
        <p class="text-xs text-slate-500 mt-0.5">Lengkapi detail produk untuk ditambahkan ke toko Anda</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <div class="lg:col-span-7 space-y-5">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
              Nama Produk <span class="text-red-500">*</span>
            </label>
            <InputText v-model="form.name" placeholder="Masukkan nama produk..." maxlength="100"
              class="w-full! rounded-xl! text-xs! py-2.5!" />
            <p class="text-[10px] text-slate-400 mt-1">Maksimal 100 karakter</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
              Kategori <span class="text-red-500">*</span>
            </label>
            <Dropdown v-model="form.category_id" :options="categories" optionLabel="label" optionValue="value"
              placeholder="Pilih kategori produk..." class="w-full! rounded-xl! text-xs! bg-slate-50/50!" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Harga Jual (Rp) <span class="text-red-500">*</span>
              </label>
              <InputNumber v-model="form.price" placeholder="0" class="w-full!"
                inputClass="!w-full !rounded-xl !text-xs !py-2.5" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Modal (Rp)</label>
              <InputNumber v-model="form.cost_price" placeholder="0" class="w-full!"
                inputClass="!w-full !rounded-xl !text-xs !py-2.5" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Stok <span class="text-red-500">*</span>
              </label>
              <InputNumber v-model="form.stock" :min="0" placeholder="0" class="w-full!"
                inputClass="!w-full !rounded-xl !text-xs !py-2.5" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Berat (gram) <span class="text-red-500">*</span>
              </label>
              <InputNumber v-model="form.weight" :min="0" placeholder="0" class="w-full!"
                inputClass="!w-full !rounded-xl !text-xs !py-2.5" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
              Deskripsi Produk <span class="text-red-500">*</span>
            </label>
            <Textarea v-model="form.description" rows="5" placeholder="Tuliskan deskripsi lengkap produk Anda..."
              class="w-full! rounded-xl! text-xs! p-3!" />
          </div>
        </div>

        <Accordion value="0" class="rounded-2xl! border border-slate-100 bg-white overflow-hidden shadow-sm">
          <AccordionPanel value="0">
            <AccordionHeader class="py-3! px-5! text-xs! font-bold! text-slate-800!">
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle text-blue-600"></i>
                <span>Informasi Tambahan</span>
              </div>
            </AccordionHeader>
            <AccordionContent class="px-5! pb-5!">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kode SKU Produk</label>
                <InputText v-model="form.sku" placeholder="Contoh: SKU-PRD-001"
                  class="w-full! rounded-xl! text-xs! py-2.5!" />
              </div>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </div>

      <div class="lg:col-span-5 space-y-5">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
          <label class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
            <i class="pi pi-image text-blue-600"></i> Foto Produk
          </label>

          <div
            class="relative border-2 border-dashed border-slate-200 hover:border-blue-500 bg-slate-50/50 rounded-2xl p-6 text-center transition-colors group cursor-pointer flex flex-col items-center justify-center min-h-50">
            <template v-if="form.images[0]">
              <img :src="form.images[0]" class="w-full h-44 object-cover rounded-xl" />
              <button type="button" @click.stop="removeImage(0)"
                class="absolute top-2 right-2 w-7 h-7 bg-red-600 text-white rounded-full flex items-center justify-center text-xs shadow-md">
                <i class="pi pi-times"></i>
              </button>
            </template>
            <template v-else>
              <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                <i class="pi pi-cloud-upload text-xl"></i>
              </div>
              <p class="text-xs font-bold text-slate-800">Klik atau drag foto ke sini</p>
              <p class="text-[10px] text-slate-400 mt-1">Maks. 5MB, format JPG/PNG</p>
            </template>
            <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer"
              @change="handleImageUpload($event, 0)" />
          </div>

          <div class="grid grid-cols-4 gap-2.5 pt-2">
            <div v-for="i in 4" :key="i"
              class="relative aspect-square border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50/50 rounded-xl flex items-center justify-center cursor-pointer transition-colors overflow-hidden group">
              <template v-if="form.images[i]">
                <img :src="form.images[i]" class="w-full h-full object-cover" />
                <button type="button" @click.stop="removeImage(i)"
                  class="absolute top-1 right-1 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-[10px]">
                  <i class="pi pi-times"></i>
                </button>
              </template>
              <template v-else>
                <i class="pi pi-plus text-slate-400 text-sm"></i>
              </template>
              <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer"
                @change="handleImageUpload($event, i)" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-200/80 flex items-center justify-end gap-3">
      <Button label="Batal" severity="secondary" outlined class="rounded-xl! text-xs! px-6! py-2.5!" @click="goBack" />
      <Button label="Simpan dan Review Produk" :loading="isSubmitting"
        class="bg-blue-600! hover:bg-blue-700! border-blue-600! rounded-xl! text-xs! font-semibold! px-6! py-2.5!"
        @click="handleSave" />
    </div>
  </div>
</template>
