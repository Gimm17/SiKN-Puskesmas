<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Download, CalendarDays, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    rows: Array,
    totals: Object,
    tahun: Number,
    namaBulan: Object,
});

const selectedTahun = ref(props.tahun);
const tahunOptions = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i);

function gantiTahun() {
    router.get(route('rekap.yearly'), {
        tahun: selectedTahun.value
    }, { preserveState: true });
}

function exportExcel() {
    window.location.href = route('export.yearly', { tahun: selectedTahun.value });
}

function getKnClass(kn, lh) {
    if (kn > lh) return 'bg-[#FFF9C4] text-[#8B6A00] font-bold'; // Kuning (Warning)
    if (kn !== lh) return 'bg-coral-light/70 text-coral-dark font-bold'; // Merah muda (Gap)
    return '';
}
</script>

<template>
    <AppLayout title="Preview Tahunan" breadcrumb="Beranda / Preview Tahunan">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-6">
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-[22px] font-bold text-text-primary tracking-tight">Data Lahir Hidup & KN Lengkap</h1>
                    <p class="text-[13px] text-text-muted mt-1">
                        Preview pencapaian per bulan untuk tahun {{ tahun }}
                    </p>
                </div>
                
                <div class="relative w-40">
                    <CalendarDays class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                    <select v-model="selectedTahun" @change="gantiTahun"
                        class="w-full pl-9 pr-8 py-2.5 bg-white border border-border rounded-lg text-[13px] font-medium focus:outline-none focus:border-teal focus:ring-2 focus:ring-teal/20 appearance-none shadow-sm cursor-pointer hover:border-teal/50 transition-colors">
                        <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
            
            <button @click="exportExcel"
                class="bg-success text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold hover:bg-success/90 shadow-[0_4px_12px_rgba(39,174,96,0.25)] flex items-center gap-2 flex-shrink-0 transition-transform active:scale-95">
                <Download class="w-4 h-4" />
                Export Excel
            </button>
        </div>

        <div class="bg-steel-light border border-steel/20 rounded-xl p-4 mb-6 flex items-start gap-3">
            <AlertCircle class="w-5 h-5 text-steel flex-shrink-0 mt-0.5" />
            <div class="text-[13px] text-steel-dark space-y-1">
                <p><strong>Panduan Membaca Data:</strong></p>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Cell KN Lengkap berwarna <span class="bg-coral-light text-coral-dark px-1 rounded font-medium">merah muda</span> menunjukkan adanya perbedaan (gap) yaitu jumlah KN lebih sedikit dari Lahir Hidup.</li>
                    <li>Cell KN Lengkap berwarna <span class="bg-[#FFF9C4] text-[#8B6A00] px-1 rounded font-medium">kuning</span> menunjukkan peringatan (warning) yaitu jumlah KN melebihi Lahir Hidup.</li>
                    <li>Gunakan <i>scroll horizontal</i> (geser ke kanan/kiri) untuk melihat seluruh bulan.</li>
                </ul>
            </div>
        </div>

        <div class="bg-white border border-border rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse min-w-[2500px]">
                    <thead>
                        <!-- Header Row 1: Bulan -->
                        <tr class="bg-teal text-white text-[13px] font-semibold tracking-wide">
                            <th rowspan="4" class="px-4 py-3 text-center border-r border-white/20 w-12 sticky left-0 bg-teal z-10 shadow-[2px_0_5px_rgba(0,0,0,0.1)]">No</th>
                            <th rowspan="4" class="px-5 py-3 text-left border-r border-white/20 w-48 sticky left-12 bg-teal z-10 shadow-[2px_0_5px_rgba(0,0,0,0.1)]">Desa</th>
                            <th colspan="48" class="px-4 py-3 text-center border-b border-white/20">Bulan</th>
                            <th colspan="4" class="px-4 py-3 text-center border-l-2 border-l-white">Jumlah</th>
                        </tr>
                        
                        <!-- Header Row 2: Nama Bulan -->
                        <tr class="bg-teal text-white text-[12px] font-medium border-b border-white/20">
                            <th v-for="m in 12" :key="`h2-${m}`" colspan="4" class="px-4 py-2 text-center border-r border-white/20">
                                {{ namaBulan[m] }}
                            </th>
                            <th colspan="4" class="px-4 py-2 text-center border-l-2 border-l-white bg-teal-dark">TOTAL</th>
                        </tr>
                        
                        <!-- Header Row 3: LH & KN -->
                        <tr class="bg-steel text-white text-[11px] font-semibold uppercase tracking-wider border-b border-white/20">
                            <template v-for="m in 12" :key="`h3-${m}`">
                                <th colspan="2" class="px-2 py-2 text-center border-r border-white/20">Lahir Hidup</th>
                                <th colspan="2" class="px-2 py-2 text-center border-r border-white/20 bg-steel-dark">KN Lengkap</th>
                            </template>
                            <th colspan="2" class="px-2 py-2 text-center border-r border-white/20 border-l-2 border-l-white bg-steel">Lahir Hidup</th>
                            <th colspan="2" class="px-2 py-2 text-center bg-steel-dark">KN Lengkap</th>
                        </tr>

                        <!-- Header Row 4: L P L P -->
                        <tr class="bg-steel-dark text-white/90 text-[11px] font-medium uppercase border-b-2 border-border">
                            <template v-for="m in 12" :key="`h4-${m}`">
                                <th class="px-2 py-2 w-12 text-center border-r border-white/10 bg-steel">L</th>
                                <th class="px-2 py-2 w-12 text-center border-r border-white/20 bg-steel">P</th>
                                <th class="px-2 py-2 w-12 text-center border-r border-white/10 bg-steel-dark">L</th>
                                <th class="px-2 py-2 w-12 text-center border-r border-white/20 bg-steel-dark">P</th>
                            </template>
                            <th class="px-2 py-2 w-12 text-center border-r border-white/10 border-l-2 border-l-white bg-steel">L</th>
                            <th class="px-2 py-2 w-12 text-center border-r border-white/20 bg-steel">P</th>
                            <th class="px-2 py-2 w-12 text-center border-r border-white/10 bg-steel-dark">L</th>
                            <th class="px-2 py-2 w-12 text-center bg-steel-dark">P</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <tr v-for="(row, i) in rows" :key="row.desa_id" class="border-b border-border hover:bg-cream/50 transition-colors bg-white">
                            <td class="px-4 py-3 text-center text-[13px] text-text-muted sticky left-0 bg-white z-10 shadow-[2px_0_5px_rgba(0,0,0,0.02)] group-hover:bg-cream/50">
                                {{ i + 1 }}
                            </td>
                            <td class="px-5 py-3 text-left font-semibold text-[13px] text-text-primary sticky left-12 bg-white z-10 shadow-[2px_0_5px_rgba(0,0,0,0.02)] border-r border-border group-hover:bg-cream/50">
                                {{ row.nama_desa }}
                            </td>
                            
                            <!-- Month Data -->
                            <template v-for="m in 12" :key="`d-${m}-${row.desa_id}`">
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border/50">{{ row.months[m].lh_l }}</td>
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border/50">{{ row.months[m].lh_p }}</td>
                                
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border/50"
                                    :class="getKnClass(row.months[m].kn_l, row.months[m].lh_l)">
                                    {{ row.months[m].kn_l }}
                                </td>
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border"
                                    :class="getKnClass(row.months[m].kn_p, row.months[m].lh_p)">
                                    {{ row.months[m].kn_p }}
                                </td>
                            </template>

                            <!-- Village Totals -->
                            <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border/50 border-l-2 border-l-steel/20 font-bold bg-[#F8F6F0]">{{ Object.values(row.months).reduce((sum, m) => sum + m.lh_l, 0) }}</td>
                            <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border/50 font-bold bg-[#F8F6F0]">{{ Object.values(row.months).reduce((sum, m) => sum + m.lh_p, 0) }}</td>
                            <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-border/50 font-bold bg-[#F0ECE4]">{{ Object.values(row.months).reduce((sum, m) => sum + m.kn_l, 0) }}</td>
                            <td class="px-2 py-3 text-center text-[13px] font-mono font-bold bg-[#F0ECE4]">{{ Object.values(row.months).reduce((sum, m) => sum + m.kn_p, 0) }}</td>
                        </tr>
                    </tbody>
                    
                    <tfoot>
                        <tr class="bg-steel text-white font-semibold">
                            <td colspan="2" class="px-5 py-3 text-right uppercase text-[12px] tracking-wider sticky left-0 bg-steel z-10 border-r border-white/20">
                                JUMLAH
                            </td>
                            
                            <!-- Grand Totals per month -->
                            <template v-for="m in 12" :key="`t-${m}`">
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20">{{ totals[m].lh_l }}</td>
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20">{{ totals[m].lh_p }}</td>
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20 bg-steel-dark">{{ totals[m].kn_l }}</td>
                                <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20 bg-steel-dark">{{ totals[m].kn_p }}</td>
                            </template>
                            
                            <!-- Absolute Grand Totals -->
                            <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20 border-l-2 border-l-white bg-teal-dark">{{ Object.values(totals).reduce((sum, m) => sum + m.lh_l, 0) }}</td>
                            <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20 bg-teal-dark">{{ Object.values(totals).reduce((sum, m) => sum + m.lh_p, 0) }}</td>
                            <td class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20 bg-teal-deeper">{{ Object.values(totals).reduce((sum, m) => sum + m.kn_l, 0) }}</td>
                            <td class="px-2 py-3 text-center text-[13px] font-mono bg-teal-deeper">{{ Object.values(totals).reduce((sum, m) => sum + m.kn_p, 0) }}</td>
                        </tr>

                        <!-- GAP L -->
                        <tr class="bg-[#FFF8F8] text-coral-dark font-semibold">
                            <td colspan="2" class="px-5 py-2.5 text-right text-[12px] sticky left-0 bg-[#FFF8F8] z-10 border-r border-coral/20">
                                GAP L
                            </td>
                            <template v-for="m in 12" :key="`gapL-${m}`">
                                <td colspan="4" class="px-2 py-2.5 text-center text-[13px] font-mono border-r border-coral/20">
                                    {{ totals[m].lh_l - totals[m].kn_l }}
                                </td>
                            </template>
                            <td colspan="4" class="px-2 py-2.5 text-center text-[13px] font-mono border-l-2 border-l-coral/20">
                                {{ Object.values(totals).reduce((sum, m) => sum + (m.lh_l - m.kn_l), 0) }}
                            </td>
                        </tr>

                        <!-- GAP P -->
                        <tr class="bg-[#FFF8F8] text-coral-dark font-semibold border-t border-coral/10">
                            <td colspan="2" class="px-5 py-2.5 text-right text-[12px] sticky left-0 bg-[#FFF8F8] z-10 border-r border-coral/20">
                                GAP P
                            </td>
                            <template v-for="m in 12" :key="`gapP-${m}`">
                                <td colspan="4" class="px-2 py-2.5 text-center text-[13px] font-mono border-r border-coral/20">
                                    {{ totals[m].lh_p - totals[m].kn_p }}
                                </td>
                            </template>
                            <td colspan="4" class="px-2 py-2.5 text-center text-[13px] font-mono border-l-2 border-l-coral/20">
                                {{ Object.values(totals).reduce((sum, m) => sum + (m.lh_p - m.kn_p), 0) }}
                            </td>
                        </tr>

                        <!-- TOTAL GAP L+P -->
                        <tr class="bg-coral text-white font-bold">
                            <td colspan="2" class="px-5 py-3 text-right text-[12px] sticky left-0 bg-coral z-10 border-r border-white/20">
                                TOTAL GAP L+P
                            </td>
                            <template v-for="m in 12" :key="`gapTotal-${m}`">
                                <td colspan="4" class="px-2 py-3 text-center text-[13px] font-mono border-r border-white/20">
                                    {{ (totals[m].lh_l - totals[m].kn_l) + (totals[m].lh_p - totals[m].kn_p) }}
                                </td>
                            </template>
                            <td colspan="4" class="px-2 py-3 text-center text-[13px] font-mono border-l-2 border-l-white">
                                {{ Object.values(totals).reduce((sum, m) => sum + (m.lh_l - m.kn_l) + (m.lh_p - m.kn_p), 0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </AppLayout>
</template>
