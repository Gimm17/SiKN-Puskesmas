<script setup>
import { computed } from 'vue';

const props = defineProps({
    coverage: Number,   // e.g. 84.2
    totalKN: Number,
    totalLH: Number,
});

const remaining = computed(() => Math.max(0, 100 - (props.coverage || 0)));

const options = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: "'Plus Jakarta Sans', sans-serif",
        animations: { enabled: true, speed: 600, animateGradually: { enabled: true, delay: 150 } },
    },
    colors: ['#44A194', '#EDEAE0'],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        offsetY: 25,
                        style: { fontSize: '12px', fontFamily: "'Plus Jakarta Sans', sans-serif", color: '#6B7A8D' },
                    },
                    value: {
                        show: true,
                        fontSize: '32px',
                        fontFamily: "'DM Mono', monospace",
                        fontWeight: '700',
                        color: '#1E2A35',
                        offsetY: -15,
                        formatter: () => (props.coverage || 0) + '%',
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Coverage KN',
                        fontSize: '12px',
                        fontFamily: "'Plus Jakarta Sans', sans-serif",
                        color: '#6B7A8D',
                        formatter: () => (props.coverage || 0) + '%',
                    },
                },
            },
        },
    },
    legend: { show: false },
    tooltip: {
        theme: 'light',
        style: { fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: '13px' },
        y: {
            formatter: (val, { seriesIndex }) =>
                seriesIndex === 0 ? `${props.totalKN} bayi ter-cover` : `${props.totalLH - props.totalKN} bayi belum`,
        },
    },
}));

const series = computed(() => [props.coverage || 0, remaining.value]);
</script>

<template>
    <div class="relative">
        <apexchart type="donut" height="220" :options="options" :series="series" :key="coverage" />
        <!-- Legend -->
        <div class="flex justify-center gap-6 mt-2">
            <div class="flex items-center gap-2 text-[12px] text-text-muted">
                <div class="w-3 h-3 rounded bg-teal"></div>
                KN Lengkap ({{ totalKN }})
            </div>
            <div class="flex items-center gap-2 text-[12px] text-text-muted">
                <div class="w-3 h-3 rounded bg-surface-alt"></div>
                Belum ({{ totalLH - totalKN }})
            </div>
        </div>
    </div>
</template>
