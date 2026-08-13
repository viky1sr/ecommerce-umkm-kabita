<script setup lang="ts">
import Button from 'primevue/button';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import type { Order } from '@/types';
import { getApiErrorMessage } from '@/services/apiError';
import { sellerOrderService } from '@/services/sellerOrderService';

// Sub Components
import OrderBuyerCard from '../components/order-detail/OrderBuyerCard.vue';
import OrderPaymentSummaryCard from '../components/order-detail/OrderPaymentSummaryCard.vue';
import OrderProductList from '../components/order-detail/OrderProductList.vue';
import OrderShippingCard from '../components/order-detail/OrderShippingCard.vue';
import OrderStatusAlert from '../components/order-detail/OrderStatusAlert.vue';
import OrderStepper from '../components/order-detail/OrderStepper.vue';
import OrderTimeline from '../components/order-detail/OrderTimeline.vue';
import OrderTransferProof from '../components/order-detail/OrderTransferProof.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isLoading = ref(true);
const isActionLoading = ref(false);
const order = ref<Order | null>(null);
const errorMessage = ref('');

const fetchOrderDetail = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    order.value = await sellerOrderService.getDetail(Number(route.params.id));
  } catch (error) {
    errorMessage.value = getApiErrorMessage(error, 'Detail pesanan gagal dimuat.');
  } finally {
    isLoading.value = false;
  }
};

const printInvoice = () => window.print();

const handleShipOrder = async () => {
  if (!order.value) return;
  isActionLoading.value = true;
  try {
    order.value = await sellerOrderService.ship(order.value.id, { tracking_number: order.value.tracking_number || undefined });
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Pesanan berhasil ditandai sebagai dikirim.', life: 3000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: getApiErrorMessage(error, 'Status pesanan gagal diperbarui.'), life: 4000 });
  } finally {
    isActionLoading.value = false;
  }
};

onMounted(() => {
  fetchOrderDetail();
});
</script>

<template>
  <div class="p-6 relative min-h-screen bg-slate-50">

    <Transition name="fade">
      <div v-if="isLoading"
        class="fixed inset-0 z-50 bg-white/90 backdrop-blur-sm flex flex-col items-center justify-center gap-3">
        <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" animationDuration=".8s" />
        <span class="text-sm font-semibold text-gray-600">Memuat Detail Pesanan...</span>
      </div>
    </Transition>

    <Message v-if="errorMessage" severity="error" class="mx-auto max-w-6xl">{{ errorMessage }}</Message>

    <div v-if="order && !errorMessage" class="max-w-6xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="router.back()" />
          <div>
            <h1 class="text-xl font-bold text-gray-800">Detail {{ order.order_number }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">{{ order.created_at }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Button icon="pi pi-print" label="Cetak Invoice" outlined severity="secondary" size="small" @click="printInvoice" />
          <Button v-if="order.status === 'processing'" label="Konfirmasi Pengiriman" icon="pi pi-send" size="small"
            :loading="isActionLoading" @click="handleShipOrder" />
        </div>
      </div>

      <OrderStepper :status="order.status" :shippingMethod="order.shipping_method"
        :isVerified="order.payment?.status === 'verified'" />

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8">
          <OrderStatusAlert :status="order.status" :notes="order.notes" :shippingMethod="order.shipping_method" />
          <OrderTransferProof :proofImage="order.payment?.proof_image || null" />
          <OrderProductList :items="order.items" :notes="order.notes" />
          <OrderTimeline :status="order.status" :createdAt="order.created_at" :updatedAt="order.updated_at" />
        </div>

        <div class="lg:col-span-4">
          <OrderBuyerCard :buyer="order.buyer" :shippingAddress="order.shipping_address" />
          <OrderShippingCard :shippingMethod="order.shipping_method" :trackingNumber="order.tracking_number"
            :shippingAddress="order.shipping_address" />
          <OrderPaymentSummaryCard :subtotal="order.subtotal" :shippingCost="order.shipping_cost"
            :totalAmount="order.total_amount" :paymentMethod="order.payment_method" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
