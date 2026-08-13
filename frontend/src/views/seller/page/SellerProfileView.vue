<script setup lang="ts">
import { getApiErrorMessage } from '@/services/apiError'
import { sellerShopService } from '@/services/sellerShopService'
import type { Shop } from '@/types/entities'
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue'

const toast = useToast()

const isLoadingGet = ref(true)
const isSubmitting = ref(false)
const isError = ref(false)
const errorMessage = ref('')
const form = ref<Partial<Shop>>({
  name: '',
  slug: '',
  description: '',
  logo: null,
  banner: null,
  phone: '',
  address: '',
})
const logoInputRef = ref<HTMLInputElement | null>(null)
const bannerInputRef = ref<HTMLInputElement | null>(null)
const selectedLogo = ref<File | null>(null)
const selectedBanner = ref<File | null>(null)

const fetchShopProfile = async () => {
  isLoadingGet.value = true
  isError.value = false
  try {
    const shop = await sellerShopService.getMyShop()
    form.value = { ...shop }
    selectedLogo.value = null
    selectedBanner.value = null
  } catch (error) {
    isError.value = true
    errorMessage.value = getApiErrorMessage(error, 'Profil toko belum dapat dimuat.')
  } finally {
    isLoadingGet.value = false
  }
}

onMounted(() => {
  fetchShopProfile()
})

const triggerLogoSelect = () => logoInputRef.value?.click()
const triggerBannerSelect = () => bannerInputRef.value?.click()

const handleLogoUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedLogo.value = target.files[0]
    form.value.logo = URL.createObjectURL(target.files[0])
  }
}

const handleBannerUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedBanner.value = target.files[0]
    form.value.banner = URL.createObjectURL(target.files[0])
  }
}

const handleNameInput = () => {
  if (form.value.name) {
    form.value.slug = form.value.name
      .toLowerCase()
      .replace(/[^a-z0-9 -]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
  }
}

const handleSaveProfile = async () => {
  if (!form.value.id || !form.value.name?.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Peringatan',
      detail: 'Nama Toko wajib diisi',
      life: 3000
    })
    return
  }

  isSubmitting.value = true
  try {
    form.value = await sellerShopService.update(form.value.id, {
      name: form.value.name.trim(),
      slug: form.value.slug ?? '',
      description: form.value.description ?? '',
      phone: form.value.phone ?? '',
      address: form.value.address ?? '',
      logo: selectedLogo.value,
      banner: selectedBanner.value,
    })
    selectedLogo.value = null
    selectedBanner.value = null
    toast.add({
      severity: 'success',
      summary: 'Berhasil disimpan',
      detail: 'Profil toko berhasil diperbarui.',
      life: 3000,
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal menyimpan',
      detail: getApiErrorMessage(error, 'Perubahan profil toko gagal disimpan.'),
      life: 4000,
    })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="relative min-h-[80vh]">
    <Transition name="fade">
      <div v-if="isLoadingGet"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm">
        <ProgressSpinner style="width: 56px; height: 56px" strokeWidth="4" fill="transparent" animationDuration=".8s"
          aria-label="Loading Profile" />
        <p class="mt-4 font-medium text-slate-600 text-sm animate-pulse">
          Memuat Pengaturan Profil Toko...
        </p>
      </div>
    </Transition>

    <Message v-if="isError" severity="error" class="mx-auto mb-4 max-w-5xl">{{ errorMessage }}</Message>

    <div v-if="!isLoadingGet && !isError" class="mx-auto max-w-5xl space-y-6 pb-12">
      <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-slate-800">Profil Toko</h1>
            <span v-if="form.status === 'verified'"
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">
              <i class="pi pi-verified text-emerald-500"></i> Terverifikasi
            </span>
            <span v-else
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200">
              <i class="pi pi-clock"></i> Menunggu Verifikasi
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Kelola informasi publik, branding logo, banner, dan alamat toko Anda.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Button label="Batal" severity="secondary" outlined class="rounded-xl! text-sm! px-5!"
            @click="fetchShopProfile" :disabled="isSubmitting" />
          <Button label="Simpan Perubahan" icon="pi pi-check" :loading="isSubmitting"
            class="bg-blue-600! hover:bg-blue-700! border-blue-600! rounded-xl! text-sm! px-5!"
            @click="handleSaveProfile" />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <div class="lg:col-span-5 space-y-6">
          <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm space-y-6">
            <h2 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="pi pi-image text-blue-600"></i> Visual & Branding
            </h2>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-2">Banner Sampul Toko</label>
              <div
                class="relative group rounded-xl overflow-hidden border-2 border-dashed border-slate-200 bg-slate-50 hover:border-blue-400 transition-colors">
                <div v-if="!form.banner" class="flex h-28 w-full items-center justify-center bg-gradient-to-br from-blue-50 to-slate-100 text-slate-400">
                  <i class="pi pi-image text-3xl"></i>
                </div>
                <img v-else :src="form.banner" alt="Banner Toko"
                  class="w-full h-28 object-cover group-hover:opacity-85 transition-opacity" />
                <button type="button" @click="triggerBannerSelect"
                  class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-medium transition-opacity gap-1.5">
                  <i class="pi pi-camera text-base"></i> Ganti Banner
                </button>
              </div>
              <p class="text-[11px] text-slate-400 mt-1.5">Rekomendasi rasio 1200x400 px, Maks. 2MB (JPG/PNG)</p>

              <input ref="bannerInputRef" type="file" accept="image/*" class="hidden" @change="handleBannerUpload" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-2">Logo Toko (Avatar)</label>
              <div class="flex items-center gap-4">
                <div
                  class="relative group w-20 h-20 rounded-2xl overflow-hidden border-2 border-slate-200 bg-slate-100 shrink-0">
                  <img v-if="form.logo" :src="form.logo" alt="Logo Toko" class="w-full h-full object-cover" />
                  <span v-else class="text-xl font-bold text-blue-600">{{ (form.name || 'T').slice(0, 1).toUpperCase() }}</span>

                    <button type="button" @click="triggerLogoSelect"
                    class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs transition-opacity">
                    <i class="pi pi-pencil"></i>
                  </button>
                </div>

                <div class="space-y-2">
                  <Button label="Unggah Logo Baru" icon="pi pi-upload" severity="secondary" outlined size="small"
                    class="rounded-xl! text-xs!" @click="triggerLogoSelect" />
                  <p class="text-[11px] text-slate-400">Format gambar persegi, rasio 1:1. Maksimal 1 MB.</p>
                </div>

                <input ref="logoInputRef" type="file" accept="image/*" class="hidden" @change="handleLogoUpload" />
              </div>
            </div>

            <div class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 flex items-center justify-between">
              <div class="overflow-hidden pr-2">
                <span class="block text-[10px] text-blue-600 font-bold uppercase tracking-wider">URL Toko Anda</span>
                <span class="text-xs font-mono text-slate-700 truncate block">
                  kabita.com/store/{{ form.slug || 'nama-toko' }}
                </span>
              </div>
              <i class="pi pi-link text-blue-500 text-sm shrink-0"></i>
            </div>
          </div>
        </div>

        <div class="lg:col-span-7 space-y-6">
          <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm space-y-5">
            <h2 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="pi pi-store text-blue-600"></i> Informasi Utama Toko
            </h2>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Nama Toko <span class="text-red-500">*</span>
              </label>
              <InputText v-model="form.name" placeholder="Masukkan nama resmi toko Anda"
                class="w-full! rounded-xl! text-sm! py-2!.5" @input="handleNameInput" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Username Toko / Custom Slug
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-mono">
                  @
                </span>
                <InputText v-model="form.slug" placeholder="nama-toko-anda"
                  class="w-full! pl-8! rounded-xl! text-sm! py-2!.5 font-mono" />
              </div>
              <p class="text-[11px] text-slate-400 mt-1">Hanya huruf kecil, angka, dan tanda hubung (-).</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Nomor Telepon / WhatsApp Operasional Toko
              </label>
              <div class="relative">
                <i class="pi pi-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <InputText v-model="form.phone" placeholder="081234567890"
                  class="w-full! pl-9! rounded-xl! text-sm! py-2!.5" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Lokasi / Kabupaten / Kota Asal Pengiriman
              </label>
              <div class="relative">
                <i class="pi pi-map-marker absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <InputText v-model="form.address" placeholder="Contoh: Kab. Garut, Jawa Barat"
                  class="w-full! pl-9! rounded-xl! text-sm! py-2!.5" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Deskripsi Toko
              </label>
              <Textarea v-model="form.description" rows="4"
                placeholder="Jelaskan mengenai produk unggulan atau komitmen toko Anda..."
                class="w-full! rounded-xl! text-sm! p-3!" autoResize />
              <p class="text-[11px] text-slate-400 mt-1">
                Tulis deskripsi singkat yang menarik minat pembeli (maksimal 500 karakter).
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
