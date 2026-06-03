<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { CheckCircle, AlertCircle, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    rows: Array,
    bulan: Number,
    tahun: Number,
    namaBulan: Object,
});

const form = ref(props.rows.map(r => ({ ...r })));

function rowTotals(row) {
    const lhTotal = (row.lahir_hidup_l || 0) + (row.lahir_hidup_p || 0);
    const knTotal = (row.kn_lengkap_l || 0) + (row.kn_lengkap_p || 0);
    return { lhTotal, knTotal };
}

function rowStatus(row) {
    const { lhTotal, knTotal } = rowTotals(row);
    const knL = row.kn_lengkap_l || 0, knP = row.kn_lengkap_p || 0;
    const lhL = row.lahir_hidup_l || 0, lhP = row.lahir_hidup_p || 0;
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
    const lhT = lhL + lhP, knT = knL + knP;
    return { lhL, lhP, lhT, knL, knP, knT, pct: lhT > 0 ? Math.round(knT / lhT * 1000) / 10 : 0 };
});

const hasErrors = computed(() =>
    form.value.some(r => (r.kn_lengkap_l || 0) > (r.lahir_hidup_l || 0) || (r.kn_lengkap_p || 0) > (r.lahir_hidup_p || 0))
);

const bulanNama = computed(() => props.namaBulan[props.bulan]);

function submit() {
    router.put(route('rekap.update'), { rows: form.value });
}
</script>

<template>
    <AppLayout :title="`Edit Data Rekap`" :breadcrumb="`Beranda / Data Rekap / Edit · ${namaBulan[bulan]} ${tahun}`">

        <!-- Top Info Bar -->
        <div class="bg-white border border-border rounded-xl px-5 py-3.5 mb-5 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-steel-light rounded-full flex items-center justify-center text-steel">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                    <span class="text-[13px] text-text-muted">Mengedit data periode:</span>
                    <span class="ml-2 bg-steel text-white text-[13px] font-semibold px-3 py-1 rounded-full">{{ namaBulan[bulan] }} {{ tahun }}</span>
                </div>
            </div>
            <div class="text-[12px] text-text-muted" v-if="rows[0]?.updated_by">
                Terakhir diubah oleh {{ rows[0].updated_by }} &middot; {{ rows[0].updated_at }}
            </div>
        </div>

        <!-- Edit Table -->
        <form @submit.prevent="submit">
            <div class="bg-white border border-border rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-steel text-white text-[13px] font-semibold uppercase tracking-[0.04em]">
                            <th class="px-4 py-3 text-center w-12 border-r border-white/20">No</th>
                            <th class="px-4 py-3 text-left w-36 border-r border-white/20">Nama Desa</th>
                            <th colspan="3" class="px-4 py-3 text-center border-r border-white/20">— Lahir Hidup —</th>
                            <th colspan="3" class="px-4 py-3 text-center border-r border-white/20">— KN Lengkap —</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                        <tr class="bg-steel-dark text-white/85 text-[12px] uppercase">
                            <th colspan="2" class="border-r border-white/20"></th>
                            <th class="px-3 py-2 text-center border-r border-white/20">L</th>
                            <th class="px-3 py-2 text-center border-r border-white/20">P</th>
                            <th class="px-3 py-2 text-center border-r border-white/20">Total</th>
                            <th class="px-3 py-2 text-center border-r border-white/20">L</th>
                            <th class="px-3 py-2 text-center border-r border-white/20">P</th>
                            <th class="px-3 py-2 text-center border-r border-white/20">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in form" :key="row.desa_id"
                            class="border-b border-border transition-colors"
                            :class="[i % 2 === 0 ? 'bg-white' : 'bg-[#F8F6F0]', row.id ? '' : 'opacity-60']"
                        >
                            <td class="px-4 py-2.5 text-center text-text-muted text-[13px]">{{ i + 1 }}</td>
                            <td class="px-4 py-2.5 text-left font-bold text-text-primary text-[14px]">{{ row.nama_desa }}</td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.lahir_hidup_l" min="0" :disabled="!row.id"
                                    class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white disabled:opacity-50" />
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.lahir_hidup_p" min="0" :disabled="!row.id"
                                    class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white disabled:opacity-50" />
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <div class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md bg-cream text-steel font-semibold flex items-center justify-center mx-auto">
                                    {{ rowTotals(row).lhTotal }}
                                </div>
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.kn_lengkap_l" min="0" :disabled="!row.id"
                                    class="w-16 h-9 text-center font-mono text-sm border rounded-md focus:outline-none focus:ring-2 outline-none bg-white disabled:opacity-50"
                                    :class="(row.kn_lengkap_l||0) > (row.lahir_hidup_l||0) ? 'border-coral-dark focus:ring-coral-dark/20' : 'border-border focus:border-teal focus:ring-teal/20'" />
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="number" v-model.number="row.kn_lengkap_p" min="0" :disabled="!row.id"
                                    class="w-16 h-9 text-center font-mono text-sm border rounded-md focus:outline-none focus:ring-2 outline-none bg-white disabled:opacity-50"
                                    :class="(row.kn_lengkap_p||0) > (row.lahir_hidup_p||0) ? 'border-coral-dark focus:ring-coral-dark/20' : 'border-border focus:border-teal focus:ring-teal/20'" />
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <div class="w-16 h-9 text-center font-mono text-sm border border-border rounded-md bg-cream text-steel font-semibold flex items-center justify-center mx-auto">
                                    {{ rowTotals(row).knTotal }}
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold whitespace-nowrap"
                                    :class="rowStatus(row).cls">
                                    {{ rowStatus(row).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
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

            <!-- Error Summary -->
            <div v-if="hasErrors" class="mt-4 bg-coral-light border border-coral/30 rounded-xl p-4 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-coral-dark mt-0.5 flex-shrink-0" />
                <p class="text-[13px] text-coral-dark font-semibold">⚠️ KN Lengkap tidak boleh melebihi Lahir Hidup pada beberapa desa.</p>
            </div>

            <!-- Audit Info -->
            <div class="mt-5 bg-cream border border-border rounded-xl p-4 shadow-sm">
                <p class="text-[14px] font-semibold text-text-primary mb-3">📋 Riwayat Perubahan</p>
                <div class="space-y-2">
                    <div v-if="rows[0]?.created_by" class="flex items-center gap-3 text-[13px]">
                        <div class="w-2 h-2 rounded-full bg-success flex-shrink-0"></div>
                        <span class="text-text-primary">Dibuat oleh {{ rows[0].created_by }} — {{ rows[0].created_at }}</span>
                    </div>
                    <div v-if="rows[0]?.updated_by" class="flex items-center gap-3 text-[13px]">
                        <div class="w-2 h-2 rounded-full bg-steel flex-shrink-0"></div>
                        <span class="text-text-muted">Terakhir diedit oleh {{ rows[0].updated_by }} — {{ rows[0].updated_at }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Row -->
            <div class="flex items-center justify-between mt-5">
                <button type="button" class="bg-white border-[1.5px] border-coral-dark text-coral-dark px-4 py-2.5 rounded-lg text-[14px] hover:bg-coral-light transition-colors flex items-center gap-2">
                    <Trash2 class="w-4 h-4" />
                    Hapus Data Ini
                </button>
                <div class="flex gap-3">
                    <button type="button" @click="router.get(route('rekap.index', {bulan, tahun}))"
                        class="bg-white border-[1.5px] border-border text-text-muted px-5 py-2.5 rounded-lg text-[14px] hover:bg-cream transition-colors">
                        Batal
                    </button>
                    <button type="submit" :disabled="hasErrors"
                        class="bg-teal text-white px-6 py-2.5 rounded-lg text-[14px] font-semibold shadow-[0_4px_12px_rgba(68,161,148,0.25)] hover:bg-teal-dark transition-all flex items-center gap-2 disabled:opacity-50">
                        <CheckCircle class="w-4 h-4" />
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
