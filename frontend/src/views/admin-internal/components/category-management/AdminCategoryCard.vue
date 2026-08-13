<script setup lang="ts">
import type { Category } from '@/types/entities';
import Button from 'primevue/button';

defineProps<{ category: Partial<Category> }>();
const emit = defineEmits<{
  (event: 'edit', category: Category): void;
  (event: 'delete', category: Category): void;
}>();
</script>

<template>
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex items-start justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-2xl">
          {{ category.icon || '🏷️' }}
        </div>
        <div class="min-w-0">
          <h2 class="truncate font-bold text-gray-900">{{ category.name }}</h2>
          <p class="truncate text-xs text-gray-400">/{{ category.slug }}</p>
        </div>
      </div>
      <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600">
        {{ category.product_count ?? 0 }} produk
      </span>
    </div>
    <p v-if="category.description" class="mt-4 line-clamp-2 text-sm text-gray-500">{{ category.description }}</p>
    <div class="mt-5 flex justify-end gap-2 border-t border-gray-100 pt-4">
      <Button label="Edit" icon="pi pi-pencil" text size="small" @click="emit('edit', category as Category)" />
      <Button label="Hapus" icon="pi pi-trash" text severity="danger" size="small" @click="emit('delete', category as Category)" />
    </div>
  </article>
</template>
