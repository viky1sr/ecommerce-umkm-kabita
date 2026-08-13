<script setup lang="ts">
import type { Product } from '@/types/entities';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

defineProps<{
  visible: boolean
  product: Partial<Product> | null
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'confirm'): void
}>()
</script>

<template>
  <Dialog :visible="visible" modal :showHeader="false" :style="{ width: '28rem' }"
    @update:visible="emit('update:visible', $event)">
    <div v-if="product" class="bg-white rounded-2xl relative">
      <div class="flex items-center justify-between p-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-800 text-lg">Konfirmasi Produk</h3>
        <button @click="emit('update:visible', false)" class="text-slate-400 hover:text-slate-600">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="p-8 text-center flex flex-col items-center">
        <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-3xl mb-4">
          <i class="pi pi-check-circle font-bold"></i>
        </div>
        <p class="text-slate-600 text-sm leading-relaxed">
          Apakah Anda yakin ingin menyetujui verifikasi untuk produk <br />
          <span class="font-bold text-slate-800 text-base">{{ product.name }}</span>?
        </p>
      </div>

      <div class="p-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50">
        <Button label="Batal" outlined class="border-slate-300! text-slate-600! px-5! bg-white!"
          @click="emit('update:visible', false)" />
        <Button label="Ya, Setujui" :loading="loading" :disabled="loading" class="bg-blue-600! border-blue-600! px-5!" @click="emit('confirm')" />
      </div>
    </div>
  </Dialog>
</template>
