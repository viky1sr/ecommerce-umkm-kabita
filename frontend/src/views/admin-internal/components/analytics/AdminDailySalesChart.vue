<script setup lang="ts">
import Chart from 'primevue/chart';
import { ref, watch } from 'vue';

const props = defineProps<{ rows: Array<{ date: string; revenue: string }> }>();

const chartData = ref();
const chartOptions = ref();

watch(() => props.rows, () => {
  chartData.value = {
    labels: props.rows.map((row) => row.date),
    datasets: [
      {
        label: 'Revenue',
        data: props.rows.map((row) => Number(row.revenue)),
        borderColor: '#2563eb',
        borderWidth: 4,
        tension: 0.45,
        pointRadius: 0,
        fill: false
      },
      {
        label: 'Profit',
        data: props.rows.map(() => 0),
        borderColor: '#10b981',
        borderWidth: 4,
        borderDash: [6, 6],
        tension: 0.45,
        pointRadius: 0,
        fill: false
      }
    ]
  };

  chartOptions.value = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { size: 11 }, color: '#64748b' }
      },
      y: {
        beginAtZero: true,
        max: 2,
        grid: { color: '#f1f5f9' },
        ticks: {
          stepSize: 0.5,
          font: { size: 11 },
          color: '#64748b',
          callback: (value: number) => {
            if (value === 0) return '0';
            if (value === 0.5) return '500k';
            return `${value}jt`;
          }
        }
      }
    }
  };
}, { immediate: true });
</script>

<template>
  <div class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-bold text-slate-800">Tren Penjualan Harian</h3>

      <div class="flex items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-blue-500 text-white font-medium">
          <span class="w-2 h-2 rounded-full bg-white"></span>
          Revenue
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-slate-100 text-slate-600 font-medium">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          Profit
        </span>
      </div>
    </div>

    <div class="h-80 w-full relative">
      <Chart type="line" :data="chartData" :options="chartOptions" class="h-full w-full" />
    </div>

    <div class="flex items-center justify-center gap-6 mt-4 pt-3 border-t border-slate-50 text-xs">
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-blue-600"></span>
        <span class="text-slate-600 font-medium">Revenue</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
        <span class="text-slate-600 font-medium">Profit</span>
      </div>
    </div>
  </div>
</template>
