<script setup lang="ts">
import Button from 'primevue/button'
import { ref } from 'vue'

interface Notification {
  id: number
  title: string
  message: string
  time: string
  type: 'promo' | 'order' | 'system'
  isRead: boolean
}

const notifications = ref<Notification[]>([
  {
    id: 1,
    title: 'Pesanan Telah Dikirim!',
    message: 'Pesanan KBT-20260801-001 sedang dalam perjalanan oleh kurir ekspedisi.',
    time: '2 jam yang lalu',
    type: 'order',
    isRead: false
  },
  {
    id: 2,
    title: 'Promo Spesial UMKM Lokal!',
    message: 'Gunakan kode promo KABITAGLOBAL untuk cashback hingga 20% khusus produk lokal.',
    time: '1 hari yang lalu',
    type: 'promo',
    isRead: true
  },
  {
    id: 3,
    title: 'Pembaruan Keamanan Akun',
    message: 'Kata sandi Anda berhasil diperbarui. Jika bukan Anda, segera hubungi Pusat Bantuan.',
    time: '3 hari yang lalu',
    type: 'system',
    isRead: true
  }
])

const markAllAsRead = () => {
  notifications.value.forEach(n => (n.isRead = true))
}

const getIcon = (type: Notification['type']) => {
  switch (type) {
    case 'order':
      return 'pi pi-box text-blue-600 bg-blue-50'
    case 'promo':
      return 'pi pi-percentage text-emerald-600 bg-emerald-50'
    default:
      return 'pi pi-shield text-amber-600 bg-amber-50'
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm p-6 lg:p-8 border border-slate-100">
    <!-- Header -->
      <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100">
        <div>
          <h1 class="text-lg font-bold text-slate-800">Notifikasi</h1>
          <p class="text-xs text-slate-500 mt-1">Informasi promo, status pesanan, dan aktivitas akun Anda.</p>
        </div>
        <Button label="Tandai Semua Dibaca" severity="secondary" text class="text-xs! text-blue-600! hover:bg-blue-50!"
          @click="markAllAsRead" />
      </div>

      <!-- Notification List -->
        <div class="divide-y divide-slate-100">
          <div v-for="notif in notifications" :key="notif.id" :class="[
            'py-4 px-3 flex items-start gap-4 rounded-xl transition-colors',
            notif!.isRead ? 'bg-slate-50/80' : 'hover:bg-slate-50/40'
          ]">
            <!-- Icon -->
              <div
                :class="['w-10 h-10 rounded-full flex items-center justify-center shrink-0 text-base', getIcon(notif.type)]">
              </div>

              <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <h3 class="text-xs font-bold text-slate-800">{{ notif.title }}</h3>
                    <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ notif.time }}</span>
                  </div>
                  <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ notif.message }}</p>
                </div>

                <!-- Unread Indicator Dot -->
                  <div v-if="notif!.isRead" class="w-2 h-2 rounded-full bg-blue-600 mt-1 shrink-0"></div>
          </div>
        </div>
  </div>
</template>