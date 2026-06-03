<script setup>
import { computed } from 'vue';

const props = defineProps({
    desaData: Array,  // [{nama, lh, kn, pct}]
});

const sorted = computed(() => [...props.desaData].sort((a, b) => b.pct - a.pct));

function coverageColor(pct) {
    if (pct >= 80) return '#44A194';
    if (pct >= 50) return '#537D96';
    if (pct > 0)   return '#EC8F8D';
    return '#D9D3C5';
}

const options = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: "'Plus Jakarta Sans', sans-serif",
        animations: { enabled: true, speed: 600 },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            distributed: true,
            barHeight: '60%',
            dataLabels: { position: 'right' },
        },
    },
    colors: sorted.value.map(d => coverageColor(d.pct)),
    dataLabels: {
        enabled: true,
        textAnchor: 'start',
        offsetX: 4,
        style: {
            fontSize: '12px',
            fontFamily: "'DM Mono', monospace",
            fontWeight: '500',
            colors: ['#1E2A35'],
        },
        formatter: (val) => val + '%',
    },
    xaxis: {
        max: 100,
        labels: {
            formatter: (val) => val + '%',
            style: { colors: '#6B7A8D', fontSize: '11px' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#1E2A35', fontSize: '12px', fontFamily: "'Plus Jakarta Sans', sans-serif" },
        },
    },
    grid: { borderColor: '#F0EDE5', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    legend: { show: false },
    tooltip: {
        theme: 'light',
        style: { fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: '13px' },
        y: { formatter: (val, { dataPointIndex }) => `${val}% (${sorted.value[dataPointIndex]?.kn}/${sorted.value[dataPointIndex]?.lh} bayi)` },
    },
}));

const series = computed(() => [{
    name: 'Coverage KN',
    data: sorted.value.map(d => d.pct),
}]);

const categories = computed(() => sorted.value.map(d => d.nama));
</script>

<template>
    <apexchart type="bar" height="280"
        :options="{ ...options, xaxis: { ...options.categories, ...options.xaxis }, yaxis: { categories: categories, ...options.yaxis } }"
        :series="series"
        :key="JSON.stringify(desaData)"
    />
</template>
