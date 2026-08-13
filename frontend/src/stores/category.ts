import type { Category } from '@/types'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryService } from '@/services/categoryService'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref<Category[]>([])
  const isLoading = ref(false)
  const load = async () => {
    isLoading.value = true
    try { categories.value = await categoryService.list() }
    finally { isLoading.value = false }
  }

  return { categories, isLoading, load }
})
