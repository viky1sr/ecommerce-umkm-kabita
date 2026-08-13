<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { ref } from 'vue';

defineProps<{
  proofImage: string | null;
}>();

const visible = ref(false);
</script>

<template>
  <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm mb-6">
    <h3 class="text-sm font-bold text-gray-800 mb-3">Bukti Transfer</h3>
    <div v-if="proofImage" class="flex items-center gap-4">
      <img :src="proofImage" alt="Bukti Transfer"
        class="w-16 h-20 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition"
        @click="visible = true" />
      <div>
        <p class="text-xs text-gray-600 mb-2">Pembeli mengunggah bukti transfer. Silakan periksa apakah dana sudah masuk
          ke rekening Anda.</p>
        <Button label="Lihat Bukti Transfer" icon="pi pi-search" outlined size="small" class="text-xs! py-1.5!"
          @click="visible = true" />
      </div>
    </div>
    <p v-else class="text-xs text-slate-500">Bukti transfer belum diunggah oleh pembeli.</p>

    <Dialog v-model:visible="visible" modal header="Detail Bukti Transfer" :style="{ width: '400px' }">
      <div class="p-2 text-center">
        <img :src="proofImage" alt="Preview Proof" class="w-full h-auto rounded-lg border border-gray-200 shadow" />
      </div>
    </Dialog>
  </div>
</template>
