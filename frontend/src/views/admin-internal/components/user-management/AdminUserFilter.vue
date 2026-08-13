<script setup lang="ts">
import Select from 'primevue/select';

const props = defineProps<{
  activeTab: string
  status: string
}>()

const emit = defineEmits<{
  (e: 'update:activeTab', tab: string): void
  (e: 'update:status', status: string): void
}>()

const tabs = [
  { label: 'Semua', value: 'all' },
  { label: 'Buyer', value: 'buyer' },
  { label: 'Seller', value: 'seller' },
  { label: 'Admin', value: 'admin' }
]

const statuses = [
  { label: 'Semua status', value: '' },
  { label: 'Aktif', value: 'active' },
  { label: 'Nonaktif', value: 'inactive' },
  { label: 'Ditangguhkan', value: 'suspended' },
]
</script>

<template>
  <div
    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm mb-5">
    <div class="flex items-center gap-1.5">
      <button v-for="tab in tabs" :key="tab.value" @click="emit('update:activeTab', tab.value)"
        class="px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200" :class="props.activeTab === tab.value
          ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
          : 'bg-transparent text-slate-600 hover:bg-slate-100'
          ">
        {{ tab.label }}
      </button>
    </div>

    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
      <Select :model-value="props.status" :options="statuses" option-label="label" option-value="value"
        class="w-full sm:w-44" aria-label="Filter status pengguna"
        @update:model-value="emit('update:status', $event)" />
    </div>
  </div>
</template>
