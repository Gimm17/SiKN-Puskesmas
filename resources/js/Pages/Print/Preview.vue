<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, Download, FileText, Printer, FileSpreadsheet } from 'lucide-vue-next';

const props = defineProps({
    rows: Array,
    totals: Object,
    bulan: Number,
    tahun: Number,
    bulanNama: String,
    namaBulan: Object,
});

const today = computed(() => {
    const d = new Date();
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
});

function formatPct(val) {
    return val > 0 ? val + '%' : '—';
}
</script>

<template>
    <Head :title="`Preview KN Lengkap — ${bulanNama} ${tahun}`" />

    <div class="min-h-screen bg-[#EDEAE0] font-sans">
        
        <!-- ═══ TOP ACTION BAR (screen only, no-print) ═══ -->
        <div class="print:hidden sticky top-0 z-10 bg-white border-b border-border px-6 py-3.5 flex items-center justify-between shadow-sm">
            <!-- Left -->
            <div class="flex items-center gap-4">
                <button @click="router.get(route('rekap.index', {bulan, tahun}))"
                    class="flex items-center gap-2 text-[14px] text-teal hover:text-teal-dark transition-colors font-medium">
                    <ArrowLeft class="w-4 h-4" />
                    Kembali
                </button>
                <div class="w-px h-5 bg-border"></div>
                <span class="text-[15px] font-semibold text-text-primary">
                    Preview Export &middot; {{ bulanNama }} {{ tahun }}
                </span>
            </div>
            
            <!-- Right: Actions -->
            <div class="flex items-center gap-2">
                <a :href="route('export.xlsx', {bulan, tahun})"
                    class="flex items-center gap-2 bg-teal hover:bg-teal-dark text-white px-4 py-2 rounded-lg text-[13px] font-semibold transition-colors shadow-sm">
                    <FileSpreadsheet class="w-4 h-4" />
                    Unduh XLSX
                </a>
                <a :href="route('export.csv', {bulan, tahun})"
                    class="flex items-center gap-2 bg-white border-[1.5px] border-steel text-steel px-4 py-2 rounded-lg text-[13px] font-semibold hover:bg-steel-light transition-colors">
                    <FileText class="w-4 h-4" />
                    Unduh CSV
                </a>
                <a :href="route('export.pdf', {bulan, tahun})"
                    class="flex items-center gap-2 bg-white border-[1.5px] border-coral text-coral-dark px-4 py-2 rounded-lg text-[13px] font-semibold hover:bg-coral-light transition-colors">
                    <Download class="w-4 h-4" />
                    Unduh PDF
                </a>
                <div class="w-px h-6 bg-border mx-1"></div>
                <button onclick="window.print()"
                    class="flex items-center gap-2 bg-steel hover:bg-steel-dark text-white px-4 py-2 rounded-lg text-[13px] font-semibold transition-colors">
                    <Printer class="w-4 h-4" />
                    Cetak
                </button>
            </div>
        </div>
        
        <!-- ═══ A4 DOCUMENT PAPER ═══ -->
        <div class="mx-auto my-8 print:my-0 max-w-[900px] bg-white shadow-xl print:shadow-none p-12 print:p-8">
            
            <!-- DOCUMENT HEADER -->
            <div class="text-center mb-8">
                <p class="text-[14px] font-bold uppercase tracking-[0.08em] text-text-primary leading-snug">
                    CAKUPAN KUNJUNGAN NEONATAL MENURUT JENIS KELAMIN
                </p>
                <div class="flex justify-center gap-20 mt-3 text-[14px] text-text-primary">
                    <span><strong>BULAN</strong> : {{ bulanNama.toUpperCase() }}</span>
                    <span><strong>TAHUN</strong> : {{ tahun }}</span>
                </div>
                <div class="mt-4 border-b-2 border-text-primary"></div>
            </div>
            
            <!-- MAIN TABLE -->
            <table class="w-full border-collapse text-[12px]">
                <!-- Level 1 Header -->
                <thead>
                    <tr class="bg-steel text-white">
                        <th rowspan="2" class="border border-white/30 px-3 py-2.5 text-center font-semibold w-10">NO</th>
                        <th rowspan="2" class="border border-white/30 px-3 py-2.5 text-center font-semibold w-24">PUSKESMAS</th>
                        <th rowspan="2" class="border border-white/30 px-3 py-2.5 text-center font-semibold w-28">DESA</th>
                        <th colspan="3" class="border border-white/30 px-3 py-2 text-center font-semibold">JUMLAH LAHIR HIDUP</th>
                        <th colspan="6" class="border border-white/30 px-3 py-2 text-center font-semibold">KN LENGKAP</th>
                    </tr>
                    <!-- Level 2 Header -->
                    <tr class="bg-steel-dark text-white/90">
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">L</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">P</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">L+P</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">L</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">%</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">P</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">%</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">L+P</th>
                        <th class="border border-white/20 px-2.5 py-2 text-center font-medium">%</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in rows" :key="row.urutan"
                        class="border-b border-[#D9D3C5] transition-colors"
                        :class="i % 2 === 0 ? 'bg-white' : 'bg-[#F8F6F0]'"
                    >
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono">{{ row.urutan }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center text-[12px]">Mapane</td>
                        <td class="border border-[#D9D3C5] px-3 py-2 font-medium">{{ row.nama }}</td>
                        <!-- LH -->
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono">{{ row.lh_l }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono">{{ row.lh_p }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono font-semibold">{{ row.lh_total }}</td>
                        <!-- KN -->
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono">{{ row.kn_l }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono text-[11px]"
                            :class="row.pct_l >= 80 ? 'text-success font-semibold' : row.pct_l > 0 ? 'text-warning' : 'text-text-muted'">
                            {{ formatPct(row.pct_l) }}
                        </td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono">{{ row.kn_p }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono text-[11px]"
                            :class="row.pct_p >= 80 ? 'text-success font-semibold' : row.pct_p > 0 ? 'text-warning' : 'text-text-muted'">
                            {{ formatPct(row.pct_p) }}
                        </td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono font-semibold">{{ row.kn_total }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2 text-center font-mono font-semibold text-[11px]"
                            :class="row.pct_total >= 80 ? 'text-success' : row.pct_total > 0 ? 'text-warning' : 'text-text-muted'">
                            {{ formatPct(row.pct_total) }}
                        </td>
                    </tr>
                </tbody>
                <!-- JUMLAH Row -->
                <tfoot>
                    <tr class="bg-[#F0ECE4] font-bold">
                        <td colspan="3" class="border border-[#D9D3C5] px-3 py-2.5 text-center font-bold uppercase tracking-wide text-[12px]">JUMLAH</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ totals.lh_l }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ totals.lh_p }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ totals.lh_total }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ totals.kn_l }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ formatPct(totals.pct_l) }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ totals.kn_p }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ formatPct(totals.pct_p) }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ totals.kn_total }}</td>
                        <td class="border border-[#D9D3C5] px-2.5 py-2.5 text-center font-mono font-bold">{{ formatPct(totals.pct_total) }}</td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- DOCUMENT FOOTER -->
            <div class="mt-12 flex items-start justify-between">
                <!-- Left: Source -->
                <div class="text-[11px] text-text-muted">
                    <p>Sumber: SiKN v1.0 · Gimora Digital</p>
                    <p class="mt-1">Dicetak: {{ today }}</p>
                </div>
                
                <!-- Right: Signature Block -->
                <div class="text-right text-[12px] text-text-primary">
                    <p>Mapane, {{ bulanNama }} {{ tahun }}</p>
                    <p class="mt-1">Mengetahui,</p>
                    <p class="font-semibold mt-1">Kepala Puskesmas Mapane</p>
                    <!-- Signature space -->
                    <div class="h-20"></div>
                    <p class="font-semibold border-t border-text-primary pt-2 mt-2 pr-0">Bd. Marwa Tandjeng Dg Materru, S.T.Keb</p>
                    <p class="text-text-muted text-[11px]">Nip. 19750117 200212 2 004</p>
                </div>
            </div>
            
        </div>
        <!-- End Paper -->
        
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 1.5cm;
    }
    body {
        background: white !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    .print\:hidden {
        display: none !important;
    }
}
</style>
