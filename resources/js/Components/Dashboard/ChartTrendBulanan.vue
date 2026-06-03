<script setup>
import { computed } from 'vue';

const props = defineProps({
    trend: Array,   // [{bulan, lh_total, kn_total, coverage}]
    tahun: Number,
});

const bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];

const options = computed(() => ({
    chart: {
        type: 'line',
        toolbar: { show: false },
        fontFamily: "'Plus Jakarta Sans', sans-serif",
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    colors: ['#537D96', '#44A194'],
    stroke: {
        width: [2.5, 2.5],
        curve: 'smooth',
        dashArray: [0, 5],
    },
    markers: {
        size: 5,
        fillOpacity: 1,
        strokeWidth: 2,
        hover: { size: 7 },
    },
    xaxis: {
        categories: bulanLabels,
        labels: {
            style: { colors: '#6B7A8D', fontSize: '12px', fontFamily: "'Plus Jakarta Sans', sans-serif" },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        min: 0,
        labels: {
            style: { colors: '#6B7A8D', fontSize: '12px' },
        },
    },
    grid: {
        borderColor: '#F0EDE5',
        strokeDashArray: 4,
        padding: { left: 8, right: 8 },
    },
    legend: {
        position: 'bottom',
        horizontalAlign: 'left',
        fontFamily: "'Plus Jakarta Sans', sans-serif",
        fontSize: '13px',
        markers: { radius: 4 },
        itemMargin: { horizontal: 16 },
    },
    fill: {
        type: ['gradient', 'gradient'],
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.15,
            opacityTo: 0.02,
            stops: [0, 100],
        },
    },
    tooltip: {
        shared: true,
        intersect: false,
        theme: 'light',
        style: { fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: '13px' },
        y: { formatter: (val) => val + ' bayi' },
    },
}));

const series = computed(() => [
    { name: 'Lahir Hidup', data: props.trend.map(t => t.lh_total) },
    { name: 'KN Lengkap', data: props.trend.map(t => t.kn_total) },
]);
</script>

<template>
    <apexchart type="area" height="230" :options="options" :series="series" />
</template>
