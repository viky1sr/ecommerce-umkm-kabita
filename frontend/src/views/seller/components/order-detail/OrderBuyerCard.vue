<script setup lang="ts">
import type { User } from '@/types';

defineProps<{
  buyer?: User;
  shippingAddress: string;
}>();
</script>

<template>
  <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm mb-6">
    <h3 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">Informasi Pembeli</h3>

    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-xs font-bold text-blue-600">
          {{ (buyer?.name || '?').slice(0, 1).toUpperCase() }}
        </div>
        <div>
          <h4 class="text-xs font-bold text-gray-800">{{ buyer?.name || '—' }}</h4>
          <p class="text-[11px] text-gray-400">{{ buyer?.email || '—' }}</p>
        </div>
      </div>
      <i class="pi pi-comments text-blue-600 text-lg cursor-pointer hover:opacity-80"></i>
    </div>

    <div class="space-y-1.5 text-xs text-gray-600">
      <span class="font-semibold text-gray-400 text-[11px]">Alamat Pengiriman</span>
      <p class="leading-relaxed text-gray-700">{{ shippingAddress }}</p>
      <p class="font-medium text-gray-800 pt-1">{{ buyer?.phone || '—' }}</p>
    </div>
  </div>
</template>
