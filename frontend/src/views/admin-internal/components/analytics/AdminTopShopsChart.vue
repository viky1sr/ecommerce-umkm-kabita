<script setup lang="ts">
import Chart from 'primevue/chart';
import { ref, watch } from 'vue';

const props = defineProps<{ rows: Array<{ name: string; total_revenue: string }> }>();

const chartData = ref();
const chartOptions = ref();

watch(() => props.rows, () => {
  chartData.value = {
    labels: props.rows.map((row) => row.name),
    datasets: [
      {
        label: 'Revenue',
        data: props.rows.map((row) => Number(row.total_revenue)),
        backgroundColor: '#3b82f6',
        borderRadius: 6,
        barThickness: 12
      }
    ]
  };

  chartOptions.value = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      x: {
        display: false,
        beginAtZero: true
      },
      y: {
        grid: { display: false },
        ticks: { font: { size: 12 }, color: '#475569' }
      }
    }
  };
}, { immediate: true });
</script>

<template>
  <div class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col">
    <h3 class="text-base font-bold text-slate-800 mb-4">Top 10 Toko (Revenue)</h3>
    <div class="h-72 w-full relative">
      <Chart type="bar" :data="chartData" :options="chartOptions" class="h-full w-full" />
    </div>
  </div>
</template>
