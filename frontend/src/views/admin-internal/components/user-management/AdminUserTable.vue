<script setup lang="ts">
import type { User } from '@/types/entities';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';

const props = defineProps<{
  users: Partial<User>[]
  currentPage: number
  perPage: number
  totalUsers: number
  lastPage: number
}>()

const emit = defineEmits<{
  (e: 'view', user: Partial<User>): void
  (e: 'edit', user: Partial<User>): void
  (e: 'ban', user: Partial<User>): void
  (e: 'page', page: number): void
  (e: 'page-size', perPage: number): void
}>()

const pageSizes = [
  { label: '15 / halaman', value: 15 },
  { label: '30 / halaman', value: 30 },
  { label: '50 / halaman', value: 50 },
  { label: '100 / halaman', value: 100 },
]

const initials = (name?: string) => (name || '?')
  .trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase()

const statusLabel = (status?: string) => ({
  active: 'Aktif',
  inactive: 'Nonaktif',
  suspended: 'Ditangguhkan',
}[status || ''] || status || '-')

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(date).replace('.', ':')
}

const changePageSize = (value: number) => emit('page-size', value)
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr
            class="border-b border-slate-100 text-slate-500 text-xs font-bold uppercase tracking-wider bg-slate-50/50">
            <th class="py-4 px-5 w-12">
              <Checkbox :binary="true" />
            </th>
            <th class="py-4 px-4">USER</th>
            <th class="py-4 px-4">ROLE</th>
            <th class="py-4 px-4">PHONE</th>
            <th class="py-4 px-4">STATUS</th>
            <th class="py-4 px-4">REGISTERED AT</th>
            <th class="py-4 px-5 text-right">ACTION</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/80 transition-colors">
            <td class="py-3.5 px-5">
              <Checkbox :binary="true" />
            </td>
            <td class="py-3.5 px-4">
              <div class="flex items-center gap-3">
                <img v-if="user.proof_image" :src="user.proof_image"
                  class="w-10 h-10 rounded-full object-cover border border-slate-200" alt="" />
                <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-xs font-bold text-blue-600">
                  {{ initials(user.name) }}
                </div>
                <div class="flex flex-col">
                  <span class="font-bold text-slate-800">{{ user.name }}</span>
                  <span class="text-xs text-slate-500">{{ user.email }}</span>
                </div>
              </div>
            </td>
            <td class="py-3.5 px-4">
              <span class="px-2.5 py-1 rounded-full text-[11px] font-bold capitalize"
                :class="user.role === 'buyer' ? 'bg-blue-50 text-blue-600' : (user.role === 'seller' ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600')">
                {{ user.role }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-slate-600 font-medium text-xs">{{ user.phone || '-' }}</td>
            <td class="py-3.5 px-4">
              <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold capitalize"
                :class="{
                  'bg-emerald-50 border-emerald-100 text-emerald-700': user.status === 'active',
                  'bg-slate-100 border-slate-200 text-slate-600': user.status === 'inactive',
                }">
                <span class="w-1.5 h-1.5 rounded-full"
                  :class="user.status === 'active' ? 'bg-emerald-500' : (user.status === 'inactive' ? 'bg-slate-400' : 'bg-amber-500')"></span>
                {{ statusLabel(user.status) }}
              </div>
            </td>
            <td class="py-3.5 px-4 text-slate-500 text-xs">{{ formatDate(user.created_at) }}</td>
            <td class="py-3.5 px-5 text-right">
              <div class="flex items-center justify-end gap-1">
                <Button icon="pi pi-eye" text rounded severity="secondary"
                  class="w-8! h-8! p-0! text-slate-500! hover:text-blue-600!" @click="emit('view', user)" />
                <Button icon="pi pi-pencil" text rounded severity="secondary"
                  class="w-8! h-8! p-0! text-slate-500! hover:text-blue-600!" @click="emit('edit', user)" />
                <Button icon="pi pi-ban" text rounded severity="danger"
                  class="w-8! h-8! p-0! text-slate-500! hover:text-red-500!" @click="emit('ban', user)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div
        class="flex items-center justify-between p-4 border-t border-slate-100 text-sm text-slate-500 bg-slate-50/30">
        <div class="flex flex-wrap items-center gap-3">
          <span>Menampilkan {{ users.length ? ((props.currentPage - 1) * props.perPage) + 1 : 0 }}-{{ Math.min(props.currentPage * props.perPage, props.totalUsers) }} dari {{ props.totalUsers.toLocaleString('id-ID') }} user</span>
          <Select :model-value="props.perPage" :options="pageSizes" option-label="label" option-value="value"
            class="w-36" aria-label="Jumlah user per halaman" @update:model-value="changePageSize" />
        </div>
        <div class="flex items-center gap-1">
          <Button label="Sebelumnya" text size="small" :disabled="props.currentPage <= 1" class="text-slate-500! font-semibold! px-3!" @click="emit('page', props.currentPage - 1)" />
          <span class="px-2 text-xs font-semibold text-slate-600">{{ props.currentPage }} / {{ props.lastPage }}</span>
          <Button label="Berikutnya" text size="small" :disabled="props.currentPage >= props.lastPage" class="text-slate-500! font-semibold! px-3!" @click="emit('page', props.currentPage + 1)" />
        </div>
      </div>
    </div>
  </div>
</template>
