<script setup lang="ts">
import type { OrderItem } from '@/types';

defineProps<{
  items?: OrderItem[];
  notes?: string | null;
}>();

const formatRupiah = (val: string | number) => {
  const num = typeof val === 'string' ? parseFloat(val) : val;
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num || 0);
};
</script>

<template>
  <div class="space-y-6 mb-6">
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
      <h3 class="text-sm font-bold text-gray-800 mb-4">Rincian Produk</h3>

      <div class="grid grid-cols-12 text-xs font-semibold text-gray-400 border-b border-gray-100 pb-2 mb-3">
        <div class="col-span-6">PRODUK</div>
        <div class="col-span-2 text-right">HARGA</div>
        <div class="col-span-2 text-center">QTY</div>
        <div class="col-span-2 text-right">TOTAL</div>
      </div>

      <div v-for="item in items" :key="item.id"
        class="grid grid-cols-12 items-center text-xs border-b border-gray-50 py-3 last:border-0">
        <div class="col-span-6 flex items-center gap-3">
          <div v-if="!item.product?.images?.[0]?.url" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-100 bg-slate-50 text-slate-400"><i class="pi pi-box"></i></div>
          <img v-else :src="item.product.images[0].url"
            class="w-12 h-12 rounded-lg border border-gray-100 object-cover" />
          <div>
            <h4 class="font-bold text-gray-800 line-clamp-1">{{ item.product?.name || 'Produk' }}</h4>
            <p class="text-[11px] text-gray-400">SKU: {{ item.product?.id || '-' }}</p>
          </div>
        </div>
        <div class="col-span-2 text-right text-gray-600 font-medium">{{ formatRupiah(item.price_snapshot) }}</div>
        <div class="col-span-2 text-center text-gray-800 font-bold">{{ item.quantity }}</div>
        <div class="col-span-2 text-right text-gray-900 font-bold">{{ formatRupiah(parseFloat(item.price_snapshot) *
          item.quantity) }}</div>
      </div>
    </div>

    <div v-if="notes" class="bg-slate-50 p-4 rounded-xl border border-slate-200/60 flex items-start gap-3">
      <i class="pi pi-comment text-slate-500 mt-0.5"></i>
      <div>
        <h5 class="text-xs font-bold text-slate-700">Catatan Pembeli</h5>
        <p class="text-xs text-slate-600 italic mt-0.5">"{{ notes }}"</p>
      </div>
    </div>
  </div>
</template>
