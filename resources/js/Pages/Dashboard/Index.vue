<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartTrendBulanan from '@/Components/Dashboard/ChartTrendBulanan.vue';
import ChartPerDesa from '@/Components/Dashboard/ChartPerDesa.vue';
import ChartCoverageKN from '@/Components/Dashboard/ChartCoverageKN.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Baby, ClipboardCheck, Percent, MapPin, Activity, FileInput, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    bulan: [Number, String], tahun: Number,
    namaBulan: Object,
    stats: Object,
    trend: Array,
    desaData: Array,
    lastImport: Object,
});

const selectedBulan = ref(props.bulan);
const selectedTahun = ref(props.tahun);

function filter() {
    router.get(route('dashboard'), { bulan: selectedBulan.value, tahun: selectedTahun.value });
}

const displayBulan = computed(() => props.bulan === 'semua' ? 'Sepanjang Tahun' : props.namaBulan[props.bulan]);

const coverageStatus = computed(() => {
    const pct = props.stats.coverage;
    if (pct >= 80) return { label: 'TERCAPAI ✓', cls: 'bg-success-light text-success', bar: 'bg-success' };
    if (pct >= 50) return { label: 'SEBAGIAN', cls: 'bg-warning-light text-warning', bar: 'bg-warning' };
    return { label: 'PERLU PERHATIAN', cls: 'bg-coral-light text-coral-dark', bar: 'bg-coral' };
});

const statCards = computed(() => [
    {
        title: 'Lahir Hidup',
        value: props.stats.total_lh,
        sub: `L: ${props.stats.total_lh_l} · P: ${props.stats.total_lh_p}`,
        icon: Baby,
        iconBg: 'bg-teal-light',
        iconColor: 'text-teal',
        accent: 'border-t-teal',
        badge: 'bg-teal-light text-teal',
        badgeText: props.bulan === 'semua' ? 'Sepanjang Tahun' : 'Bulan ini',
    },
    {
        title: 'KN Lengkap',
        value: props.stats.total_kn,
        sub: `L: ${props.stats.total_kn_l} · P: ${props.stats.total_kn_p}`,
        icon: ClipboardCheck,
        iconBg: 'bg-steel-light',
        iconColor: 'text-steel',
        accent: 'border-t-steel',
        badge: 'bg-steel-light text-steel',
        badgeText: 'Terverifikasi',
    },
    {
        title: 'Coverage KN',
        value: props.stats.coverage + '%',
        sub: 'Target: ≥ 80%',
        icon: Percent,
        iconBg: 'bg-teal',
        iconColor: 'text-white',
        accent: 'border-t-teal',
        badge: coverageStatus.value.cls,
        badgeText: coverageStatus.value.label,
        highlight: true,
    },
    {
        title: 'Desa Terendah',
        value: props.stats.desa_terendah?.nama ?? '—',
        sub: props.stats.desa_terendah ? `Coverage: ${props.stats.desa_terendah.pct}%` : 'Belum ada data',
        icon: MapPin,
        iconBg: 'bg-warning-light',
        iconColor: 'text-warning',
        accent: 'border-t-warning',
        badge: 'bg-warning-light text-warning',
        badgeText: 'Perhatikan',
    },
]);
</script>

<template>
    <AppLayout title="Dashboard Analitik" breadcrumb="Beranda">

        <!-- FILTER BAR -->
        <div class="bg-white border border-border rounded-xl px-5 py-3 mb-5 flex items-center gap-3 shadow-sm">
            <span class="text-[14px] font-semibold text-text-primary">Periode:</span>
            <select v-model="selectedBulan" class="bg-white border border-teal text-teal text-sm font-semibold rounded-lg px-3 pr-8 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal/20">
                <option value="semua">Tahunan (Semua Bulan)</option>
                <option v-for="(nm, val) in namaBulan" :key="val" :value="Number(val)">{{ nm }}</option>
            </select>
            <select v-model="selectedTahun" class="bg-white border border-teal text-teal text-sm font-semibold rounded-lg px-3 pr-8 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal/20">
                <option v-for="y in Array.from({length:10},(_,i)=>new Date().getFullYear()-i)" :key="y" :value="y">{{ y }}</option>
            </select>
            <button @click="filter" class="bg-teal hover:bg-teal-dark text-white text-[13px] font-semibold px-4 py-1.5 rounded-lg transition-colors">
                Tampilkan
            </button>
            <div class="ml-auto flex items-center gap-3">
                <span class="text-[13px] text-text-muted">Puskesmas Mapane · 10 desa</span>
                <button @click="router.get(route('rekap.import'))" class="flex items-center gap-1.5 text-[13px] text-steel hover:text-steel-dark transition-colors">
                    <FileInput class="w-4 h-4" />
                    Import Excel
                </button>
                <button @click="router.get(route('rekap.index', {bulan: bulan, tahun: tahun}))"
                    class="flex items-center gap-1.5 text-[13px] text-teal hover:text-teal-dark transition-colors">
                    <Activity class="w-4 h-4" />
                    Lihat Tabel
                </button>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div v-for="card in statCards" :key="card.title"
                class="bg-white border border-border border-t-[3px] rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow"
                :class="card.accent">
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                        :class="card.iconBg">
                        <component :is="card.icon" class="w-5 h-5" :class="card.iconColor" />
                    </div>
                    <span class="text-[11px] font-semibold px-2 py-1 rounded-full" :class="card.badge">
                        {{ card.badgeText }}
                    </span>
                </div>
                <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-text-muted mt-3">{{ card.title }}</p>
                <p class="text-[30px] font-bold font-mono text-text-primary leading-none mt-1">{{ card.value }}</p>
                <p class="text-[12px] text-text-muted mt-1.5">{{ card.sub }}</p>
                <div v-if="card.highlight" class="mt-2.5 h-1.5 bg-cream rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700"
                        :class="coverageStatus.bar"
                        :style="{ width: stats.coverage + '%' }"></div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 1: Trend + Coverage Donut -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <!-- Trend Chart (col-span-2) -->
            <div class="col-span-2 bg-white border border-border rounded-xl p-6 shadow-sm">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-[16px] font-semibold text-text-primary">Tren Bulanan {{ tahun }}</h3>
                        <p class="text-[13px] text-text-muted">Lahir Hidup vs KN Lengkap sepanjang tahun</p>
                    </div>
                    <div class="flex gap-1">
                        <button v-for="y in [tahun-2, tahun-1, tahun]" :key="y"
                            @click="selectedTahun = y; filter()"
                            class="text-[12px] px-2.5 py-1 rounded-full transition-colors font-medium"
                            :class="y === tahun ? 'bg-teal text-white shadow-sm' : 'bg-cream text-text-muted hover:bg-teal-light hover:text-teal'">
                            {{ y }}
                        </button>
                    </div>
                </div>
                <ChartTrendBulanan :trend="trend" :tahun="tahun" />
            </div>

            <!-- Coverage Donut -->
            <div class="bg-white border border-border rounded-xl p-6 shadow-sm flex flex-col">
                <div class="mb-3">
                    <h3 class="text-[16px] font-semibold text-text-primary">Coverage KN</h3>
                    <p class="text-[13px] text-text-muted"><span v-if="bulan !== 'semua'">{{ displayBulan }} </span>{{ tahun }}</p>
                </div>
                <div class="flex-1 flex items-center justify-center">
                    <ChartCoverageKN
                        :coverage="stats.coverage"
                        :total-kn="stats.total_kn"
                        :total-lh="stats.total_lh"
                    />
                </div>
                <div class="mt-3 text-center">
                    <span class="inline-block text-[12px] font-semibold px-3 py-1.5 rounded-full"
                        :class="coverageStatus.cls">
                        {{ coverageStatus.label }}
                    </span>
                    <p class="text-[12px] text-text-muted mt-1.5">Target nasional: ≥ 80%</p>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 2: Per Desa + Gender + Activity -->
        <div class="grid grid-cols-3 gap-4">
            <!-- Per Desa Bar Chart -->
            <div class="col-span-1 bg-white border border-border rounded-xl p-6 shadow-sm">
                <h3 class="text-[16px] font-semibold text-text-primary">Coverage per Desa</h3>
                <p class="text-[13px] text-text-muted mb-3"><span v-if="bulan !== 'semua'">{{ displayBulan }} </span>{{ tahun }} · KN Lengkap %</p>
                <ChartPerDesa :desa-data="desaData" :key="bulan + '-' + tahun" />
            </div>

            <!-- Gender Breakdown -->
            <div class="col-span-1 bg-white border border-border rounded-xl p-6 shadow-sm">
                <h3 class="text-[16px] font-semibold text-text-primary">Distribusi Gender</h3>
                <p class="text-[13px] text-text-muted mb-4">Lahir Hidup · <span v-if="bulan !== 'semua'">{{ displayBulan }} </span>{{ tahun }}</p>

                <div class="space-y-3">
                    <div v-for="desa in desaData.slice(0, 7)" :key="desa.nama" class="flex items-center gap-2.5">
                        <span class="text-[11px] font-medium text-text-primary w-20 truncate flex-shrink-0">{{ desa.nama }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden flex">
                            <div class="h-full bg-steel transition-all"
                                :style="{ width: desa.lh > 0 ? (desa.lh_l / desa.lh * 100) + '%' : '50%' }"></div>
                            <div class="h-full bg-coral transition-all"
                                :style="{ width: desa.lh > 0 ? (desa.lh_p / desa.lh * 100) + '%' : '50%' }"></div>
                        </div>
                        <span class="text-[11px] font-mono text-text-muted w-8 text-right">{{ desa.lh }}</span>
                    </div>
                </div>

                <!-- Total bar -->
                <div class="mt-4 pt-4 border-t border-border flex items-center gap-4">
                    <div class="text-center flex-1">
                        <p class="text-[22px] font-bold font-mono text-steel leading-none">{{ stats.total_lh_l }}</p>
                        <p class="text-[11px] text-text-muted flex items-center gap-1 justify-center mt-1">
                            <span class="w-2 h-2 bg-steel rounded inline-block"></span> Laki-laki
                        </p>
                    </div>
                    <div class="text-center flex-1">
                        <p class="text-[22px] font-bold font-mono text-coral leading-none">{{ stats.total_lh_p }}</p>
                        <p class="text-[11px] text-text-muted flex items-center gap-1 justify-center mt-1">
                            <span class="w-2 h-2 bg-coral rounded inline-block"></span> Perempuan
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-2">
                    <div class="flex items-center gap-2 text-[12px] text-text-muted">
                        <div class="w-3 h-3 rounded bg-steel"></div> L
                    </div>
                    <div class="flex items-center gap-2 text-[12px] text-text-muted">
                        <div class="w-3 h-3 rounded bg-coral"></div> P
                    </div>
                </div>
            </div>

            <!-- Activity & Quick Links -->
            <div class="col-span-1 bg-white border border-border rounded-xl p-6 shadow-sm flex flex-col">
                <h3 class="text-[16px] font-semibold text-text-primary">Aktivitas Terbaru</h3>
                <p class="text-[13px] text-text-muted mb-4">Input & Import terakhir</p>

                <div class="flex-1">
                    <div v-if="lastImport" class="flex items-start gap-3 p-3 bg-cream rounded-xl">
                        <div class="w-9 h-9 bg-teal-light rounded-full flex items-center justify-center text-teal font-bold text-[13px] flex-shrink-0">
                            {{ lastImport.user?.charAt(0) || 'A' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-medium text-text-primary">Data <span v-if="bulan !== 'semua'">{{ displayBulan }} </span>{{ tahun }} diimport</p>
                            <p class="text-[12px] text-text-muted truncate">{{ lastImport.filename }}</p>
                            <p class="text-[11px] text-text-muted mt-0.5">{{ lastImport.created_at }}</p>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-[13px] text-text-muted">
                        <Activity class="w-9 h-9 mx-auto mb-2 opacity-20" />
                        Belum ada aktivitas untuk periode ini.
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-4 pt-4 border-t border-border flex flex-col gap-2">
                    <button @click="router.get(route('rekap.create', {bulan, tahun}))"
                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg bg-cream hover:bg-teal-light hover:text-teal transition-colors text-[13px] font-medium text-text-primary">
                        <TrendingUp class="w-4 h-4" />
                        Input Manual Data
                    </button>
                    <button @click="router.get(route('rekap.import'))"
                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg bg-cream hover:bg-steel-light hover:text-steel transition-colors text-[13px] font-medium text-text-primary">
                        <FileInput class="w-4 h-4" />
                        Import dari Excel
                    </button>
                    <button @click="router.get(route('rekap.index', {bulan, tahun}))"
                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg bg-cream hover:bg-teal-light hover:text-teal transition-colors text-[13px] font-medium text-text-primary">
                        <Activity class="w-4 h-4" />
                        Lihat Tabel Rekap
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
