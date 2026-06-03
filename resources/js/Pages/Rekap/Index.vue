<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Download, FileInput, CalendarDays, Search, Check, Edit2, AlertCircle, History, Trash2, ChevronDown, CheckSquare, PencilLine, Printer } from 'lucide-vue-next';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    rows: Array,
    bulan: Number,
    tahun: Number,
    namaBulan: Object,
    lastImport: Object,
    yearlyTotals: Object,
});

const selectedBulan = ref(props.bulan);
const selectedTahun = ref(props.tahun);
const selectedIds = ref([]);

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

function filter() {
    router.get(route('rekap.index'), { bulan: selectedBulan.value, tahun: selectedTahun.value });
}

const showDeleteModal = ref(false);
const deleteTargetId = ref(null);
const deleteType = ref('single'); // single, selected, month, year
const deleteMessage = ref('');

function hapus(rekapId) {
    deleteTargetId.value = rekapId;
    deleteType.value = 'single';
    deleteMessage.value = 'Tindakan ini tidak dapat dibatalkan. Data ini akan dihapus secara permanen dari sistem.';
    showDeleteModal.value = true;
}

function editPeriod() {
    router.get(route('rekap.edit'), { bulan: selectedBulan.value, tahun: selectedTahun.value });
}

const selectableRows = computed(() => props.rows.filter(r => r.rekap_id));
const allSelected = computed({
    get: () => selectableRows.value.length > 0 && selectedIds.value.length === selectableRows.value.length,
    set: (val) => {
        selectedIds.value = val ? selectableRows.value.map(r => r.rekap_id) : [];
    }
});

function bulkDelete(action) {
    deleteType.value = action;
    if (action === 'selected') deleteMessage.value = `Tindakan ini tidak dapat dibatalkan. <b>${selectedIds.value.length} data terpilih</b> akan dihapus secara permanen.`;
    else if (action === 'month') deleteMessage.value = `Tindakan ini tidak dapat dibatalkan. <b>SEMUA data di bulan ini</b> akan dihapus secara permanen.`;
    else if (action === 'year') deleteMessage.value = `Tindakan ini tidak dapat dibatalkan. <b>SEMUA data di tahun ${selectedTahun.value}</b> akan dihapus secara permanen.`;

    showDeleteModal.value = true;
}

function confirmDelete() {
    if (deleteType.value === 'single') {
        router.delete(route('rekap.destroy', deleteTargetId.value), {
            onSuccess: () => { showDeleteModal.value = false; }
        });
    } else {
        router.delete(route('rekap.bulk-destroy'), {
            data: { action: deleteType.value, ids: selectedIds.value, bulan: selectedBulan.value, tahun: selectedTahun.value },
            onSuccess: () => {
                selectedIds.value = [];
                showBulkDelete.value = false;
                showDeleteModal.value = false;
            }
        });
    }
}

function pctChip(pct, hasData) {
    if (!hasData) return { label: '—', cls: 'bg-cream text-text-muted' };
    if (pct >= 100) return { label: '100%', cls: 'bg-success-light text-success' };
    if (pct >= 80)  return { label: pct + '%', cls: 'bg-success-light text-success' };
    if (pct >= 50)  return { label: pct + '%', cls: 'bg-warning-light text-warning' };
    if (pct > 0)    return { label: pct + '%', cls: 'bg-coral-light text-coral-dark' };
    return { label: pct + '%', cls: 'bg-cream text-text-muted' };
}

function fmtPct(pct) {
    return pct > 0 ? pct + '%' : '0%';
}

// Grand totals
const totals = computed(() => {
    const lhL = props.rows.reduce((s, r) => s + r.lahir_hidup_l, 0);
    const lhP = props.rows.reduce((s, r) => s + r.lahir_hidup_p, 0);
    const lhT = lhL + lhP;
    const knL = props.rows.reduce((s, r) => s + r.kn_lengkap_l, 0);
    const knP = props.rows.reduce((s, r) => s + r.kn_lengkap_p, 0);
    const knT = knL + knP;
    const scL = props.rows.reduce((s, r) => s + r.screening_l, 0);
    const scP = props.rows.reduce((s, r) => s + r.screening_p, 0);
    const scT = scL + scP;

    const pct  = (num, den) => den > 0 ? +(num / den * 100).toFixed(1) : 0;

    return {
        lhL, lhP, lhT,
        knL, knP, knT,
        kn1PctL: pct(knL, lhL), kn1PctP: pct(knP, lhP), kn1PctT: pct(knT, lhT),
        knPctL: pct(knL, lhL), knPctP: pct(knP, lhP), knPctT: pct(knT, lhT),
        scL, scP, scT,
        scPctL: pct(scL, lhL), scPctP: pct(scP, lhP), scPctT: pct(scT, lhT),
    };
});

const showExport = ref(false);
const showBulkDelete = ref(false);
</script>

<template>
    <AppLayout title="Data Rekap" breadcrumb="Beranda / Data Rekap">
        <!-- Section 1: Top Action Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <!-- Filter Left -->
            <div class="flex items-center gap-2 flex-wrap">
                <select v-model="selectedBulan" class="bg-white border border-border text-sm rounded-lg pl-3 pr-8 py-2 focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/20 text-text-primary font-medium">
                    <option v-for="(nm, val) in namaBulan" :key="val" :value="Number(val)">{{ nm }}</option>
                </select>
                <select v-model="selectedTahun" class="bg-white border border-border text-sm rounded-lg pl-3 pr-8 py-2 focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/20 text-text-primary font-medium">
                    <option v-for="y in Array.from({length:10},(_,i)=>new Date().getFullYear()-i)" :key="y" :value="y">{{ y }}</option>
                </select>
                <button @click="filter" class="bg-teal hover:bg-teal-dark text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <Search class="w-4 h-4" />
                    Tampilkan
                </button>
                <span class="bg-teal-light text-teal-dark text-[13px] font-medium px-3 py-1.5 rounded-full ml-1">
                    {{ rows.length }} desa ditemukan
                </span>
            </div>
            <!-- Action Right -->
            <div class="flex items-center gap-2">
                <button @click="router.get(route('rekap.create'))" class="bg-white border-[1.5px] border-steel text-steel text-[14px] font-semibold px-4 py-2 rounded-lg hover:bg-steel-light transition-colors flex items-center gap-2">
                    <PencilLine class="w-4 h-4" />
                    Input Manual
                </button>
                <button @click="router.get(route('rekap.import'))" class="bg-white border-[1.5px] border-teal text-teal text-[14px] font-semibold px-4 py-2 rounded-lg hover:bg-teal-light transition-colors flex items-center gap-2">
                    <FileInput class="w-4 h-4" />
                    Import Excel
                </button>
                <div class="relative ml-2">
                    <button @click="showExport = !showExport" class="bg-teal hover:bg-teal-dark text-white text-[14px] font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <Download class="w-4 h-4" />
                        Export
                        <ChevronDown class="w-4 h-4" />
                    </button>
                    <div v-if="showExport" class="absolute right-0 top-full mt-1 bg-white border border-border rounded-lg shadow-lg z-20 min-w-[180px] py-1">
                        <a :href="route('export.xlsx', {bulan: bulan, tahun: tahun})" class="flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-cream transition-colors">📊 Export XLSX</a>
                        <a :href="route('export.csv', {bulan: bulan, tahun: tahun})" class="flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-cream transition-colors">📄 Export CSV</a>
                        <a :href="route('export.pdf', {bulan: bulan, tahun: tahun})" class="flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-cream transition-colors">🖨️ Export PDF</a>
                        <div class="border-t border-border my-1"></div>
                        <button @click="router.get(route('print.preview', {bulan: bulan, tahun: tahun}))" class="w-full flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-teal-light hover:text-teal transition-colors">
                            <Printer class="w-4 h-4" />
                            Preview Cetak
                        </button>
                        <button onclick="window.print()" class="w-full flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-cream transition-colors">🖨️ Cetak Langsung</button>
                    </div>
                </div>
                
                <!-- Bulk Delete Dropdown -->
                <div class="relative">
                    <button @click="showBulkDelete = !showBulkDelete" class="bg-coral-light hover:bg-coral border-[1.5px] border-coral-dark/50 text-coral-dark hover:text-white text-[14px] font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <Trash2 class="w-4 h-4" />
                        Hapus Massal
                        <ChevronDown class="w-4 h-4" />
                    </button>
                    <div v-if="showBulkDelete" class="absolute right-0 top-full mt-1 bg-white border border-border rounded-lg shadow-lg z-20 min-w-[220px] py-1">
                        <button @click="bulkDelete('selected')" :disabled="!selectedIds.length" class="w-full flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-coral-light hover:text-coral-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Hapus Terpilih ({{ selectedIds.length }})
                        </button>
                        <button @click="bulkDelete('month')" class="w-full flex items-center gap-2 px-4 py-2.5 text-[14px] text-text-primary hover:bg-coral-light hover:text-coral-dark transition-colors">
                            Hapus Semua (Bulan Ini)
                        </button>
                        <div class="border-t border-border my-1"></div>
                        <button @click="bulkDelete('year')" class="w-full flex items-center gap-2 px-4 py-2.5 text-[14px] text-coral-dark hover:bg-coral hover:text-white transition-colors">
                            ⚠️ Hapus Semua (Tahun Ini)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Detailed Data Table (Scrollable) -->
        <div class="bg-white rounded-xl border border-border shadow-sm overflow-x-auto">
            <table class="border-collapse text-[12px]" style="min-width: 1400px; width: 100%;">
                <thead>
                    <!-- Row 1: Group headers -->
                    <tr class="bg-steel text-white font-semibold uppercase tracking-[0.04em]">
                        <th rowspan="3" class="px-2 py-3 text-center border border-white/20 w-8">
                            <input type="checkbox" v-model="allSelected" class="rounded border-white/40 bg-white/10 text-teal focus:ring-teal/50 cursor-pointer">
                        </th>
                        <th rowspan="3" class="px-2 py-3 text-center border border-white/20 w-8">NO</th>
                        <th rowspan="3" class="px-2 py-3 text-center border border-white/20 w-24">Puskesmas</th>
                        <th rowspan="3" class="px-2 py-3 text-left border border-white/20 w-24">Desa</th>
                        <th colspan="3" class="px-2 py-3 text-center border border-white/20">JUMLAH LAHIR HIDUP</th>
                        <th colspan="6" class="px-2 py-3 text-center border border-white/20">KUNJUNGAN NEONATAL 1 KALI (KN1)</th>
                        <th colspan="6" class="px-2 py-3 text-center border border-white/20">KUNJUNGAN NEONATAL 3 KALI (KN LENGKAP)</th>
                        <th colspan="6" class="px-2 py-3 text-center border border-white/20">BAYI BARU LAHIR SCREENING HIPOTIROID KONGENITAL</th>
                        <th rowspan="3" class="px-2 py-3 text-center border border-white/20 w-20">AKSI</th>
                    </tr>
                    <!-- Row 2: L / P / L+P sub-headers -->
                    <tr class="bg-steel text-white font-semibold">
                        <!-- LH -->
                        <th rowspan="2" class="px-2 py-2 text-center border border-white/20 w-10">L</th>
                        <th rowspan="2" class="px-2 py-2 text-center border border-white/20 w-10">P</th>
                        <th rowspan="2" class="px-2 py-2 text-center border border-white/20 w-10">L+P</th>
                        <!-- KN1 -->
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">L</th>
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">P</th>
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">L+P</th>
                        <!-- KN Lengkap -->
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">L</th>
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">P</th>
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">L+P</th>
                        <!-- Screening -->
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">L</th>
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">P</th>
                        <th colspan="2" class="px-2 py-2 text-center border border-white/20">L+P</th>
                    </tr>
                    <!-- Row 3: Jml / % -->
                    <tr class="bg-steel-dark text-white/90 text-[11px]">
                        <!-- KN1 -->
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <!-- KN Lengkap -->
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <!-- Screening -->
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">Jml</th>
                        <th class="px-1 py-2 text-center border border-white/20 w-10">%</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in rows" :key="row.desa_id"
                        class="border-b border-border transition-colors duration-150 hover:bg-teal-light/30"
                        :class="i % 2 === 0 ? 'bg-white' : 'bg-[#F8F6F0]'"
                    >
                        <td class="px-2 py-2 text-center border border-border">
                            <input v-if="row.rekap_id" type="checkbox" :value="row.rekap_id" v-model="selectedIds" class="rounded border-border text-teal focus:ring-teal/50 cursor-pointer">
                        </td>
                        <td class="px-2 py-2 text-center border border-border font-mono text-[12px] text-text-muted">{{ row.urutan }}</td>
                        <td class="px-2 py-2 text-center border border-border text-[12px]">Mapane</td>
                        <td class="px-2 py-2 text-left border border-border text-[13px] font-semibold text-text-primary">{{ row.nama_desa }}</td>

                        <!-- LAHIR HIDUP -->
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.lahir_hidup_l }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.lahir_hidup_p }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-semibold">{{ row.lahir_hidup_total }}</td>

                        <!-- KN1 (Jml L, % L, Jml P, % P, Jml L+P, % L+P) -->
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.kn1_l }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px]"
                            :class="row.lahir_hidup_l > 0 ? 'text-success' : 'text-text-muted'">
                            {{ row.lahir_hidup_l > 0 ? fmtPct(row.kn1_pct_l) : '—' }}
                        </td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.kn1_p }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px]"
                            :class="row.lahir_hidup_p > 0 ? 'text-success' : 'text-text-muted'">
                            {{ row.lahir_hidup_p > 0 ? fmtPct(row.kn1_pct_p) : '—' }}
                        </td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-semibold">{{ row.kn1_total }}</td>
                        <td class="px-1 py-2 text-center border border-border">
                            <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-mono font-semibold"
                                :class="pctChip(row.kn1_pct_total, row.has_data).cls">
                                {{ row.has_data ? fmtPct(row.kn1_pct_total) : '—' }}
                            </span>
                        </td>

                        <!-- KN LENGKAP (Jml L, % L, Jml P, % P, Jml L+P, % L+P) -->
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.kn_lengkap_l }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px]"
                            :class="row.lahir_hidup_l > 0 ? 'text-success' : 'text-text-muted'">
                            {{ row.lahir_hidup_l > 0 ? fmtPct(row.kn_pct_l) : '—' }}
                        </td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.kn_lengkap_p }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px]"
                            :class="row.lahir_hidup_p > 0 ? 'text-success' : 'text-text-muted'">
                            {{ row.lahir_hidup_p > 0 ? fmtPct(row.kn_pct_p) : '—' }}
                        </td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-semibold">{{ row.kn_lengkap_total }}</td>
                        <td class="px-1 py-2 text-center border border-border">
                            <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-mono font-semibold"
                                :class="pctChip(row.kn_pct, row.has_data).cls">
                                {{ row.has_data ? fmtPct(row.kn_pct) : '—' }}
                            </span>
                        </td>

                        <!-- SCREENING HIPOTIROID -->
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.screening_l }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px] text-text-muted">
                            {{ row.lahir_hidup_l > 0 ? fmtPct(row.screening_pct_l) : '—' }}
                        </td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-medium">{{ row.screening_p }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px] text-text-muted">
                            {{ row.lahir_hidup_p > 0 ? fmtPct(row.screening_pct_p) : '—' }}
                        </td>
                        <td class="px-1 py-2 text-center border border-border font-mono font-semibold">{{ row.screening_total }}</td>
                        <td class="px-1 py-2 text-center border border-border font-mono text-[11px] text-text-muted">
                            {{ row.lahir_hidup_total > 0 ? fmtPct(row.screening_pct_total) : '—' }}
                        </td>

                        <!-- AKSI -->
                        <td class="px-2 py-2 text-center border border-border">
                            <div class="flex items-center justify-center gap-1">
                                <button @click="editPeriod()" class="w-7 h-7 bg-steel-light text-steel rounded flex items-center justify-center hover:bg-steel hover:text-white transition-colors" title="Edit">
                                    <PencilLine class="w-3 h-3" />
                                </button>
                                <button v-if="row.rekap_id" @click="hapus(row.rekap_id)" class="w-7 h-7 bg-coral-light text-coral-dark rounded flex items-center justify-center hover:bg-coral-dark hover:text-white transition-colors" title="Hapus">
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <!-- Footer Totals -->
                <tfoot>
                    <tr class="bg-[#F0ECE4] border-t-2 border-steel font-bold text-[12px]">
                        <td colspan="4" class="px-3 py-3 text-center border border-border text-text-primary uppercase">JUMLAH</td>
                        <!-- LH -->
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.lhL }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.lhP }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.lhT }}</td>
                        <!-- KN1 -->
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.knL }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-success">{{ fmtPct(totals.kn1PctL) }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.knP }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-success">{{ fmtPct(totals.kn1PctP) }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.knT }}</td>
                        <td class="px-1 py-3 text-center border border-border">
                            <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-mono font-semibold"
                                :class="pctChip(totals.kn1PctT, totals.lhT > 0).cls">
                                {{ fmtPct(totals.kn1PctT) }}
                            </span>
                        </td>
                        <!-- KN Lengkap -->
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.knL }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-success">{{ fmtPct(totals.knPctL) }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.knP }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-success">{{ fmtPct(totals.knPctP) }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.knT }}</td>
                        <td class="px-1 py-3 text-center border border-border">
                            <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-mono font-semibold"
                                :class="pctChip(totals.knPctT, totals.lhT > 0).cls">
                                {{ fmtPct(totals.knPctT) }}
                            </span>
                        </td>
                        <!-- Screening -->
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.scL }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-text-muted">{{ fmtPct(totals.scPctL) }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.scP }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-text-muted">{{ fmtPct(totals.scPctP) }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono">{{ totals.scT }}</td>
                        <td class="px-1 py-3 text-center border border-border font-mono text-[11px] text-text-muted">{{ fmtPct(totals.scPctT) }}</td>
                        <td class="border border-border"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Section 2.5: Summaries (Tahunan & Gap Bulanan) -->
        <div class="mt-5 mb-2 flex flex-col xl:flex-row gap-4 justify-start">
            <!-- Yearly Summary (Akumulasi Jan-Des) -->
            <div class="bg-white border-2 border-steel rounded-lg shadow-sm overflow-hidden flex flex-col md:flex-row">
                <div class="bg-blue-50/50 flex items-center px-5 py-4 border-b md:border-b-0 md:border-r border-steel text-text-primary font-bold text-[14px]">
                    Akumulasi<br>(Jan-Des):
                </div>
                <div class="flex flex-wrap md:flex-nowrap divide-y md:divide-y-0 md:divide-x divide-steel">
                    <div class="flex flex-col items-center justify-center p-3 min-w-[90px] bg-blue-50/30">
                        <span class="text-[12px] font-bold text-blue-700 text-center leading-tight mb-2">Lahir<br>Hidup<br>(L)</span>
                        <span class="text-[16px] font-mono font-bold text-blue-800">{{ yearlyTotals.lh_l }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[90px] bg-blue-50/30">
                        <span class="text-[12px] font-bold text-coral-dark text-center leading-tight mb-2">Lahir<br>Hidup<br>(P)</span>
                        <span class="text-[16px] font-mono font-bold text-coral-dark">{{ yearlyTotals.lh_p }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[100px] bg-yellow-100/50">
                        <span class="text-[12px] font-bold text-yellow-900 text-center leading-tight mb-2">Total<br>L+P<br>Lahir Hidup</span>
                        <span class="text-[16px] font-mono font-bold text-yellow-950">{{ yearlyTotals.lh_t }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[90px] bg-blue-50/30">
                        <span class="text-[12px] font-bold text-blue-700 text-center leading-tight mb-2">KN<br>Lengkap<br>(L)</span>
                        <span class="text-[16px] font-mono font-bold text-blue-800">{{ yearlyTotals.kn_l }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[90px] bg-blue-50/30">
                        <span class="text-[12px] font-bold text-coral-dark text-center leading-tight mb-2">KN<br>Lengkap<br>(P)</span>
                        <span class="text-[16px] font-mono font-bold text-coral-dark">{{ yearlyTotals.kn_p }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[100px] bg-yellow-100/50">
                        <span class="text-[12px] font-bold text-yellow-900 text-center leading-tight mb-2">Total<br>L+P<br>KN Lengkap</span>
                        <span class="text-[16px] font-mono font-bold text-yellow-950">{{ yearlyTotals.kn_t }}</span>
                    </div>
                </div>
            </div>

            <!-- Gap Bulanan -->
            <div class="bg-white border-2 border-coral-dark/60 rounded-lg shadow-sm overflow-hidden flex flex-col md:flex-row">
                <div class="bg-coral-light flex items-center px-5 py-4 border-b md:border-b-0 md:border-r border-coral-dark/60 text-coral-dark font-bold text-[14px]">
                    Kesenjangan / Gap<br>(Bulan Ini):
                </div>
                <div class="flex flex-wrap md:flex-nowrap divide-y md:divide-y-0 md:divide-x divide-coral-dark/60">
                    <div class="flex flex-col items-center justify-center p-3 min-w-[90px] bg-white">
                        <span class="text-[12px] font-bold text-coral-dark text-center leading-tight mb-2">Gap<br>(L)</span>
                        <span class="text-[16px] font-mono font-bold text-coral-dark">{{ Math.max(0, totals.lhL - totals.knL) }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[90px] bg-white">
                        <span class="text-[12px] font-bold text-coral-dark text-center leading-tight mb-2">Gap<br>(P)</span>
                        <span class="text-[16px] font-mono font-bold text-coral-dark">{{ Math.max(0, totals.lhP - totals.knP) }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center p-3 min-w-[100px] bg-coral-light/50">
                        <span class="text-[12px] font-bold text-coral-dark text-center leading-tight mb-2">Total Gap<br>L+P</span>
                        <span class="text-[16px] font-mono font-bold text-coral-dark">{{ Math.max(0, totals.lhT - totals.knT) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Last Import Info -->
        <div v-if="lastImport" class="mt-4 bg-white border border-border rounded-xl p-4 flex items-center gap-4 shadow-sm">
            <div class="w-10 h-10 bg-teal-light rounded-full flex items-center justify-center text-teal flex-shrink-0">
                <FileInput class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[14px] font-medium text-text-primary truncate">Terakhir diimport dari: {{ lastImport.filename }}</p>
                <p class="text-[12px] text-text-muted mt-0.5">oleh {{ lastImport.user }} &middot; {{ lastImport.created_at }} &middot; {{ lastImport.total_rows }} baris berhasil</p>
            </div>
            <span class="bg-success-light text-success text-[12px] font-semibold px-3 py-1.5 rounded-full flex-shrink-0">✅ Semua data lengkap</span>
        </div>

        <ConfirmationModal
            :show="showDeleteModal"
            variant="danger"
            title="Hapus Data Rekap?"
            :message="deleteMessage"
            confirmText="Hapus Permanen"
            cancelText="Batal"
            @confirm="confirmDelete"
            @close="showDeleteModal = false"
        />

    </AppLayout>
</template>
