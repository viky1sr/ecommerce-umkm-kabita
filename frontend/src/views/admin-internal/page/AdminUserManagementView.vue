<script setup lang="ts">
import { getApiErrorMessage } from '@/services/apiError'
import { adminUserService } from '@/services/adminUserService'
import type { User } from '@/types/entities'
import Button from 'primevue/button'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast'
import { computed, onMounted, ref, watch } from 'vue'

import AdminUserDetailModal from '../components/user-management/AdminUserDetailModal.vue'
import AdminUserFilter from '../components/user-management/AdminUserFilter.vue'
import AdminUserTable from '../components/user-management/AdminUserTable.vue'

const toast = useToast()

const isLoading = ref(true)
const isError = ref(false)
const errorMessage = ref('')
const activeTab = ref('all')
const status = ref('')
const search = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const totalUsers = ref(0)
const lastPage = ref(1)
const users = ref<Partial<User>[]>([])
const isModalVisible = ref(false)
const selectedUser = ref<Partial<User> | null>(null)

const filteredUsers = computed(() => users.value)

const fetchUsers = async () => {
  isLoading.value = true
  isError.value = false
  errorMessage.value = ''

  try {
    const response = await adminUserService.list({
      role: activeTab.value === 'all' ? undefined : activeTab.value,
      status: status.value || undefined,
      search: search.value || undefined,
      page: currentPage.value,
      per_page: perPage.value,
    })

    users.value = response.data
    totalUsers.value = response.meta.total
    lastPage.value = response.meta.last_page
  } catch (error) {
    isError.value = true
    errorMessage.value = getApiErrorMessage(error, 'Gagal memuat data pengguna.')
  } finally {
    isLoading.value = false
  }
}

const openUserDetail = (user: Partial<User>) => {
  selectedUser.value = user
  isModalVisible.value = true
}

const handleSuspendToggle = async (user: Partial<User>) => {
  if (!user.id) return

  try {
    const updatedUser =
      user.status === 'suspended'
        ? await adminUserService.activate(user.id)
        : await adminUserService.suspend(user.id)

    users.value = users.value.map((existingUser) =>
      existingUser.id === updatedUser.id ? updatedUser : existingUser,
    )

    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: user.status === 'suspended' ? 'Pengguna berhasil diaktifkan.' : 'Pengguna berhasil ditangguhkan.',
      life: 3000,
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: getApiErrorMessage(error, 'Gagal memperbarui status pengguna.'),
      life: 3000,
    })
  }
}

const changePage = (page: number) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  fetchUsers()
}

const changePageSize = (size: number) => {
  perPage.value = size
  currentPage.value = 1
  fetchUsers()
}

let searchTimer: ReturnType<typeof setTimeout> | undefined
watch([activeTab, status], () => {
  currentPage.value = 1
  fetchUsers()
})
watch(search, () => {
  currentPage.value = 1
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchUsers, 350)
})
onMounted(fetchUsers)
</script>

<template>
  <div class="relative min-h-[80vh]">
    <div v-if="isLoading"
      class="fixed inset-0 z-100 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm transition-all duration-300">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
      <span class="mt-4 text-sm font-semibold text-slate-600 animate-pulse">Memuat data pengguna...</span>
    </div>

    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Manajemen User</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola semua pengguna platform Kabita</p>
      </div>
      <Button label="Export Data" icon="pi pi-download" outlined
        class="text-blue-600! border-blue-300! hover:bg-blue-50! rounded-xl! px-4!" />
    </div>

    <Message v-if="isError" severity="error" class="mb-4">{{ errorMessage }}</Message>

    <AdminUserFilter v-model:activeTab="activeTab" v-model:status="status" />
    <div class="mb-5 flex w-full items-center rounded-2xl border border-slate-200/80 bg-white p-3 shadow-sm sm:w-96">
      <i class="pi pi-search mr-2 text-slate-400"></i>
      <InputText v-model="search" placeholder="Cari nama atau email user..." class="w-full border-0! p-1! text-sm! shadow-none!" />
    </div>

    <AdminUserTable :users="filteredUsers" :current-page="currentPage" :per-page="perPage" :total-users="totalUsers" :last-page="lastPage"
      @page="changePage" @page-size="changePageSize" @view="openUserDetail" @edit="openUserDetail" @ban="handleSuspendToggle" />

    <AdminUserDetailModal v-model:visible="isModalVisible" :user="selectedUser" />
  </div>
</template>
