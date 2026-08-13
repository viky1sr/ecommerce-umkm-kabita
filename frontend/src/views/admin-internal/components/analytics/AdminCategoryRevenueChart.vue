<script setup lang="ts">
import Chart from 'primevue/chart';
import { ref, watch } from 'vue';

const props = defineProps<{ rows: Array<{ name: string; revenue: string }> }>();

const chartData = ref();
const chartOptions = ref();

watch(() => props.rows, () => {
  chartData.value = {
    labels: props.rows.map((row) => row.name),
    datasets: [
      {
        label: 'Revenue (Juta Rp)',
        data: props.rows.map((row) => Number(row.revenue)),
        backgroundColor: [
          '#2563eb', // Fashion (Blue)
          '#10b981', // Makanan (Green)
          '#c2410c', // Elektronik (Orange)
          '#a855f7', // Kecantikan (Purple)
          '#ec4899', // Kesehatan (Pink)
          '#eab308'  // Lainnya (Yellow)
        ],
        borderRadius: 6,
        barThickness: 28
      }
    ]
  };

  chartOptions.value = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (context: any) => ` Rp ${context.raw} Juta`
        }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { size: 11 }, color: '#64748b' }
      },
      y: {
        beginAtZero: true,
        grid: { color: '#f1f5f9' },
        ticks: {
          stepSize: 5,
          font: { size: 11 },
          color: '#64748b',
          callback: (value: number) => `${value.toLocaleString('id-ID')}`
        }
      }
    }
  };
}, { immediate: true });
</script>

<template>
  <div class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col">
    <h3 class="text-base font-bold text-slate-800 mb-4">Revenue per Kategori</h3>
    <div class="h-72 w-full relative">
      <Chart type="bar" :data="chartData" :options="chartOptions" class="h-full w-full" />
    </div>
  </div>
</template>
