<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { CheckCircle, AlertCircle, Info } from 'lucide-vue-next';

const props = defineProps({
    rows: Array,
    bulan: Number,
    tahun: Number,
    namaBulan: Object,
    existingCount: Number,
});

const selectedBulan = ref(props.bulan);
const selectedTahun = ref(props.tahun);
const tahunOptions = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i);

// Local reactive copy of rows
const form = ref(props.rows.map(r => ({
    ...r,
    screening_hipotiroid_l: r.screening_hipotiroid_l ?? 0,
    screening_hipotiroid_p: r.screening_hipotiroid_p ?? 0,
})));

// Live computed totals per row
function rowTotals(row) {
    const lhTotal = (row.lahir_hidup_l || 0) + (row.lahir_hidup_p || 0);
    const knTotal = (row.kn_lengkap_l || 0) + (row.kn_lengkap_p || 0);
    const pct = lhTotal > 0 ? Math.round(knTotal / lhTotal * 1000) / 10 : 0;
    return { lhTotal, knTotal, pct };
}

function rowStatus(row) {
    const { lhTotal, knTotal } = rowTotals(row);
    const knL = row.kn_lengkap_l || 0;
    const knP = row.kn_lengkap_p || 0;
    const lhL = row.lahir_hidup_l || 0;
    const lhP = row.lahir_hidup_p || 0;
    if (knL > lhL || knP > lhP) return { label: 'KN > LH ⚠️', cls: 'bg-coral-light text-coral-dark' };
    if (lhTotal === 0) return { label: 'Kosong', cls: 'bg-cream text-text-muted' };
    if (knTotal === lhTotal) return { label: '✓ Lengkap', cls: 'bg-success-light text-success' };
    return { label: 'Sebagian', cls: 'bg-warning-light text-warning' };
}

const overallTotals = computed(() => {
    const lhL = form.value.reduce((s, r) => s + (r.lahir_hidup_l || 0), 0);
    const lhP = form.value.reduce((s, r) => s + (r.lahir_hidup_p || 0), 0);
    const knL = form.value.reduce((s, r) => s + (r.kn_lengkap_l || 0), 0);
    const knP = form.value.reduce((s, r) => s + (r.kn_lengkap_p || 0), 0);
    const lhT = lhL + lhP;
    const knT = knL + knP;
    return { lhL, lhP, lhT, knL, knP, knT, pct: lhT > 0 ? Math.round(knT / lhT * 1000) / 10 : 0 };
});

const hasErrors = computed(() =>
    form.value.some(r => (r.kn_lengkap_l || 0) > (r.lahir_hidup_l || 0) || (r.kn_lengkap_p || 0) > (r.lahir_hidup_p || 0))
);

function changePeriod() {
    router.get(route('rekap.create'), { bulan: selectedBulan.value, tahun: selectedTahun.value });
}

function submit() {
    router.post(route('rekap.store'), {
        bulan: selectedBulan.value,
        tahun: selectedTahun.value,
        rows: form.value,
    });
}
</script>

<template>
    <AppLayout title="Input Data Manual" breadcrumb="Beranda / Data Rekap / Input Manual">

        <!-- Section 1: Header Card -->
        <div class="bg-white border border-border rounded-xl px-6 py-4 mb-5 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-full bg-teal-light flex items-center justify-center text-teal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                    <h2 class="text-[18px] font-bold text-text-primary">Input Data Manual</h2>
                    <p class="text-[13px] text-text-muted">Masukkan data per desa untuk satu periode bulan/tahun</p>
                </div>
            </div>
            <!-- Period Selector -->
            <div class="flex items-center gap-2">
                <span class="text-[13px] text-text-muted">Periode Data:</span>
                <select v-model="selectedBulan" @change="changePeriod" class="bg-white border border-teal text-teal text-sm font-medium rounded-lg pl-3 pr-8 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal/20 w-32">
                    <option v-for="(nm, val) in namaBulan" :key="val" :value="Number(val)">{{ nm }}</option>
                </select>
                <select v-model="selectedTahun" @change="changePeriod" class="bg-white border border-teal text-teal text-sm font-medium rounded-lg pl-3 pr-8 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal/20 w-24">
                    <option v-for="y in Array.from({length:10},(_,i)=>new Date().getFullYear()-i)" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
        </div>

        <!-- Duplicate Warning -->
        <div v-if="existingCount > 0" class="mb-4 bg-warning-light border border-warning/30 rounded-lg p-3 flex items-center gap-3 text-[13px] text-warning">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            ⚠️ Data periode ini sudah ada ({{ existingCount }} desa). Data akan <strong class="mx-1">ditimpa (overwrite)</strong> jika Anda menyimpan.
        </div>

        <!-- Section 2: Entry Table -->
        <form @submit.prevent="submit">
            <div class="bg-white border border-border rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-steel text-white text-[13px] font-semibold uppercase tracking-[0.04em]">
                            <th class="px-4 py-3 text-center w-12 border-r border-white/20">No</th>
                            <th class="px-4 py-3 text-left w-36 border-r border-white/20">Nama Desa</th>
                            <th colspan="3" class="px-4 py-3 text-center border-r border-white/20">— Lahir Hidup —</th>
                            <th colspan="3" class="px-4 py-3 text-center border-r border-white/20">— KN Lengkap —</th>
                            <th colspan="2" class="px-4 py-3 text-center border-r border-white/20">Screening Hipotiroid</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                        <tr class="bg-steel-dark text-white/85 text-[12px] uppercase">
                            <th colspan="2" class="border-r border-white/20"></th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium">L</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium">P</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium">Total</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium">L</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium">P</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium">Total</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium text-[10px]">L (Opsional)</th>
                            <th class="px-3 py-2 text-center border-r border-white/20 font-medium text-[10px]">P (Opsional)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in form" :key="row.desa_id"
                            class="border-b border-border transition-colors duration-100"
                            :class="i % 2 === 0 ? 'bg-white' : 'bg-[#F8F6F0]'"
                        >
                            <td class="px-4 py-2.5 text-center text-text-muted text-[13px]">{{ i + 1 }}</td>
                            <td class="px-4 py-2.5 text-left font-bold text-text-primary text-[14px]">{{ row.nama_desa }}</td>
                            
                            <!-- LH-L -->
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.lahir_hidup_l" min="0"
                                    class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white" />
                            </td>
                            <!-- LH-P -->
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.lahir_hidup_p" min="0"
                                    class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white" />
                            </td>
                            <!-- LH Total (readonly) -->
                            <td class="px-2 py-2.5 text-center">
                                <div class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md bg-cream text-steel font-semibold flex items-center justify-center mx-auto">
                                    {{ rowTotals(row).lhTotal }}
                                </div>
                            </td>
                            <!-- KN-L -->
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.kn_lengkap_l" min="0"
                                    class="w-16 h-9 text-center font-mono text-sm border rounded-md focus:outline-none focus:ring-2 outline-none bg-white"
                                    :class="(row.kn_lengkap_l||0) > (row.lahir_hidup_l||0) ? 'border-coral-dark focus:border-coral-dark focus:ring-coral-dark/20' : 'border-border focus:border-teal focus:ring-teal/20'" />
                            </td>
                            <!-- KN-P -->
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.kn_lengkap_p" min="0"
                                    class="w-16 h-9 text-center font-mono text-sm border rounded-md focus:outline-none focus:ring-2 outline-none bg-white"
                                    :class="(row.kn_lengkap_p||0) > (row.lahir_hidup_p||0) ? 'border-coral-dark focus:border-coral-dark focus:ring-coral-dark/20' : 'border-border focus:border-teal focus:ring-teal/20'" />
                            </td>
                            <!-- KN Total (readonly) -->
                            <td class="px-2 py-2.5 text-center">
                                <div class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md bg-cream text-steel font-semibold flex items-center justify-center mx-auto">
                                    {{ rowTotals(row).knTotal }}
                                </div>
                            </td>
                            <!-- SC-L -->
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.screening_hipotiroid_l" min="0"
                                    class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white" />
                            </td>
                            <!-- SC-P -->
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.screening_hipotiroid_p" min="0"
                                    class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white" />
                            </td>
                            <!-- Status -->
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold whitespace-nowrap"
                                    :class="rowStatus(row).cls">
                                    {{ rowStatus(row).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <!-- Footer Totals -->
                    <tfoot>
                        <tr class="bg-[#F0ECE4] border-t border-border">
                            <td colspan="2" class="px-4 py-3 font-bold text-text-primary text-[13px] uppercase">TOTAL</td>
                            <td class="px-3 py-3 text-center font-mono font-bold">{{ overallTotals.lhL }}</td>
                            <td class="px-3 py-3 text-center font-mono font-bold">{{ overallTotals.lhP }}</td>
                            <td class="px-3 py-3 text-center font-mono font-bold">{{ overallTotals.lhT }}</td>
                            <td class="px-3 py-3 text-center font-mono font-bold">{{ overallTotals.knL }}</td>
                            <td class="px-3 py-3 text-center font-mono font-bold">{{ overallTotals.knP }}</td>
                            <td class="px-3 py-3 text-center font-mono font-bold">{{ overallTotals.knT }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[12px] font-mono font-semibold bg-teal-light text-teal-dark">
                                    {{ overallTotals.pct }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Validation Error Summary -->
            <div v-if="hasErrors" class="mt-4 bg-coral-light border border-coral/30 rounded-xl p-4 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-coral-dark mt-0.5 flex-shrink-0" />
                <div>
                    <p class="text-[14px] font-semibold text-coral-dark">⚠️ Perhatikan hal berikut:</p>
                    <ul class="mt-2 space-y-1 text-[13px] text-coral-dark">
                        <li v-for="(row, i) in form" :key="i">
                            <span v-if="(row.kn_lengkap_l||0) > (row.lahir_hidup_l||0)">• {{ row.nama_desa }}: KN L ({{ row.kn_lengkap_l }}) melebihi LH L ({{ row.lahir_hidup_l }})</span>
                            <span v-if="(row.kn_lengkap_p||0) > (row.lahir_hidup_p||0)">• {{ row.nama_desa }}: KN P ({{ row.kn_lengkap_p }}) melebihi LH P ({{ row.lahir_hidup_p }})</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Section 3: Form Actions -->
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" @click="router.get(route('rekap.index'))"
                    class="bg-white border-[1.5px] border-border text-text-muted px-5 py-2.5 rounded-lg text-[14px] hover:bg-cream transition-colors">
                    Batal
                </button>
                <button type="submit" :disabled="hasErrors"
                    class="bg-teal text-white px-6 py-2.5 rounded-lg text-[14px] font-semibold shadow-[0_4px_12px_rgba(68,161,148,0.25)] hover:bg-teal-dark transition-all active:scale-[0.98] flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <CheckCircle class="w-4 h-4" />
                    Simpan Data
                </button>
            </div>
        </form>

        <!-- Helper Panel -->
        <div class="mt-5 bg-white border border-border rounded-xl p-4 shadow-sm">
            <p class="text-[14px] font-semibold text-text-primary mb-3">📌 Petunjuk</p>
            <ul class="space-y-2 text-[12px] text-text-muted">
                <li>• KN Lengkap tidak boleh melebihi Lahir Hidup</li>
                <li>• Masukkan 0 jika tidak ada kelahiran</li>
                <li>• Total dihitung otomatis secara real-time</li>
                <li>• Data kosong (semua 0) tetap akan disimpan</li>
            </ul>
        </div>
    </AppLayout>
</template>
