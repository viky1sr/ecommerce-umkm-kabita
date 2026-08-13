<script setup lang="ts">
import type { Category } from '@/types/entities';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import OverlayPanel from 'primevue/overlaypanel';
import Textarea from 'primevue/textarea';
import { ref, watch } from 'vue';

type CategoryFormPayload = Partial<Category> & {
  description?: string;
};

const props = defineProps<{
  visible: boolean;
  categoryToEdit?: Category | null;
}>();

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void;
  (e: 'save', payload: CategoryFormPayload): void;
}>();

const name = ref('');
const slug = ref('');
const icon = ref('🍕');
const description = ref('');
const isSubmitting = ref(false);
const opEmoji = ref();

// Daftar Preset Emoji untuk Pilihan Cepat
const availableEmojis = [
  '🍕', '👕', '💻', '🏠', '🧸', '💄', '👟', '📚',
  '🍏', '🍔', '🎧', '🎮', '⚽', '🚗', '💍', '🎨',
  '⌚', '📷', '🛵', '🕯️', '🧴', '🎁', '⚡', '☕'
];

// Opsi pembuat Slug Otomatis
const generateSlug = (text: string) => {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-');
};

watch(name, (newName) => {
  if (!props.categoryToEdit) {
    slug.value = generateSlug(newName);
  }
});

watch(
  () => props.categoryToEdit,
  (newVal) => {
    if (newVal) {
      name.value = newVal.name;
      slug.value = newVal.slug;
      icon.value = newVal.icon || '🍕';
      description.value = newVal.description || '';
    } else {
      name.value = '';
      slug.value = '';
      icon.value = '🍕';
      description.value = '';
    }
  },
  { immediate: true }
);

const handleClose = () => {
  emit('update:visible', false);
};

const toggleEmojiPicker = (event: Event) => {
  opEmoji.value.toggle(event);
};

const selectEmoji = (selectedEmoji: string) => {
  icon.value = selectedEmoji;
  opEmoji.value.hide();
};

const handleSubmit = () => {
  if (!name.value.trim()) return;
  isSubmitting.value = true;
  emit('save', {
    id: props.categoryToEdit?.id,
    name: name.value.trim(),
    slug: slug.value || generateSlug(name.value),
    icon: icon.value,
    description: description.value,
  });
  isSubmitting.value = false;
  handleClose();
};
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
    :header="categoryToEdit ? 'Edit Kategori' : 'Tambah Kategori Baru'" :style="{ width: '520px' }"
    :breakpoints="{ '960px': '75vw', '641px': '90vw' }" class="p-dialog-custom rounded-2xl" :pt="{
      root: { class: 'rounded-2xl! overflow-hidden!' },
      header: { class: 'pb-2! pt-6! px-6!' },
      content: { class: 'px-6! pb-6!' }
    }">
    <form @submit.prevent="handleSubmit" class="space-y-5 pt-2">
      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-800">
          Nama Kategori <span class="text-red-500">*</span>
        </label>
        <InputText v-model="name" placeholder="Masukkan nama kategori"
          class="w-full rounded-xl! py-3! px-4! border-gray-200! focus:border-blue-500! focus:ring-2! focus:ring-blue-100!"
          required />
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-800">Slug</label>
        <div class="relative">
          <InputText v-model="slug" disabled placeholder="otomatis-dibuat"
            class="w-full rounded-xl! py-3! pl-4! pr-10! bg-slate-50! border-gray-200! text-gray-500! font-mono text-sm cursor-not-allowed" />
          <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400">
            <i class="pi pi-lock text-sm"></i>
          </div>
        </div>
        <p class="text-xs text-gray-400">
          Dihasilkan secara otomatis berdasarkan nama kategori.
        </p>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-800">
          Icon/Emoji (Ukuran 24px)
        </label>
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-xl bg-purple-50/70 border border-purple-100 flex items-center justify-center text-2xl">
            {{ icon }}
          </div>
          <Button type="button" label="Pilih Emoji" severity="secondary" outlined
            class="rounded-xl! border-gray-200! text-gray-700! hover:bg-gray-50! font-semibold! px-4! py-2!.5"
            @click="toggleEmojiPicker" />
        </div>

        <OverlayPanel ref="opEmoji" class="rounded-2xl! p-2!">
          <div class="grid grid-cols-6 gap-2 w-64 p-1">
            <button v-for="e in availableEmojis" :key="e" type="button" @click="selectEmoji(e)"
              class="w-9 h-9 flex items-center justify-center text-xl rounded-lg hover:bg-gray-100 transition-colors">
              {{ e }}
            </button>
          </div>
        </OverlayPanel>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-semibold text-gray-800">Deskripsi</label>
        <Textarea v-model="description" rows="3" placeholder="Deskripsi singkat mengenai kategori ini..."
          class="w-full rounded-xl! p-4! border-gray-200! focus:border-blue-500! focus:ring-2! focus:ring-blue-100! resize-none" />
      </div>

      <div class="flex items-center justify-end gap-3 pt-4">
        <Button type="button" label="Batal" severity="secondary" outlined
          class="rounded-xl! px-6! py-2!.5 font-semibold! border-gray-200! text-blue-600! hover:bg-blue-50!/50"
          @click="handleClose" />
        <Button type="submit" label="Simpan" :loading="isSubmitting"
          class="rounded-xl! px-6! py-2!.5 font-semibold! bg-blue-600! hover:bg-blue-700! border-blue-600!" />
      </div>
    </form>
  </Dialog>
</template>
