<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { CheckCircle, Upload, Eye, Check, AlertCircle, FileSpreadsheet, Clock, Layers, Table, Database } from 'lucide-vue-next';

const props = defineProps({
    namaBulan: Object,
    history: Array,
    lastImportBulan: Number,
    lastImportTahun: Number,
});

// ---------- State ----------
const step = ref(1);
const selectedBulan = ref(props.lastImportBulan || new Date().getMonth() + 1);
const selectedTahun = ref(props.lastImportTahun || new Date().getFullYear());
const selectedFile = ref(null);
const fileInputRef = ref(null);
const isDragging = ref(false);
const isLoading = ref(false);
const previewRows = ref([]);
const previewMeta = ref(null);
const errorMsg = ref('');

// Sheet state
const sheetNames = ref([]);
const activeSheet = ref('');
const isSwitchingSheet = ref(false);

// Raw data state
const rawData = ref(null);
const knCols = ref({ l_lbl: 'W', p_lbl: 'X' });

// Preview tab state
const previewTab = ref('parsed'); // 'parsed' | 'raw'

const tahunOptions = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i);

// ---------- File Handling ----------
function onFileChange(e) {
    const file = e.target.files?.[0];
    if (file) selectedFile.value = file;
}

function onDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer.files?.[0];
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
        selectedFile.value = file;
    } else {
        errorMsg.value = 'Hanya file .xlsx atau .xls yang diterima.';
    }
}

// ---------- Step 1 → Step 2: Preview ----------
async function doPreview() {
    if (!selectedFile.value) { errorMsg.value = 'Pilih file terlebih dahulu.'; return; }
    errorMsg.value = '';
    isLoading.value = true;

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('bulan', selectedBulan.value);
    formData.append('tahun', selectedTahun.value);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

    try {
        const res = await axios.post(route('rekap.import.preview'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        previewRows.value = res.data.rows;
        previewMeta.value = { filename: res.data.filename, size: res.data.size };
        sheetNames.value = res.data.sheetNames || [];
        activeSheet.value = res.data.activeSheet || '';
        rawData.value = res.data.rawData || null;
        knCols.value = res.data.knCols || { l_lbl: 'W', p_lbl: 'X' };
        step.value = 2;
    } catch (err) {
        errorMsg.value = err.response?.data?.message ?? err.response?.data?.errors?.file?.[0] ?? 'Gagal membaca file.';
    } finally {
        isLoading.value = false;
    }
}

// ---------- Switch Sheet ----------
async function switchSheet(newSheet) {
    if (newSheet === activeSheet.value) return;
    isSwitchingSheet.value = true;

    try {
        const res = await axios.post(route('rekap.import.switch-sheet'), {
            sheet: newSheet,
            bulan: selectedBulan.value,
            tahun: selectedTahun.value,
        });
        previewRows.value = res.data.rows;
        activeSheet.value = res.data.activeSheet;
        rawData.value = res.data.rawData || null;
        if (res.data.knCols) knCols.value = res.data.knCols;
    } catch (err) {
        errorMsg.value = err.response?.data?.error ?? 'Gagal mengganti sheet.';
    } finally {
        isSwitchingSheet.value = false;
    }
}

// ---------- Step 2 → Step 3: Confirm & Save ----------
function doImport() {
    isLoading.value = true;
    router.post(route('rekap.import.store'), {
        bulan: selectedBulan.value,
        tahun: selectedTahun.value,
        rows: previewRows.value,
    }, {
        onFinish: () => { isLoading.value = false; }
    });
}

// ---------- Reset ----------
function resetUpload() {
    step.value = 1;
    selectedFile.value = null;
    previewRows.value = [];
    sheetNames.value = [];
    activeSheet.value = '';
    rawData.value = null;
    previewTab.value = 'parsed';
}

// ---------- Computed ----------
const hasWarning = computed(() => previewRows.value.some(r => r.warning));
const hasMultipleSheets = computed(() => sheetNames.value.length > 1);

function rowStatusChip(row) {
    if (row.warning) return { label: '⚠️ KN > LH', cls: 'bg-coral-light text-coral-dark' };
    return { label: '✓ Terdeteksi', cls: 'bg-success-light text-success' };
}

const totalLH = computed(() => previewRows.value.reduce((s, r) => s + r.lahir_hidup_l + r.lahir_hidup_p, 0));
const totalKN = computed(() => previewRows.value.reduce((s, r) => s + r.kn_lengkap_l + r.kn_lengkap_p, 0));

const stepLabels = ['Upload File', 'Preview Data', 'Konfirmasi'];
</script>

<template>
    <AppLayout title="Import Data dari Excel" breadcrumb="Beranda / Import Excel">

        <!-- STEPPER -->
        <div class="bg-white border border-border rounded-xl px-8 py-5 mb-6 shadow-sm">
            <div class="flex items-center justify-center gap-0">
                <template v-for="(label, i) in stepLabels" :key="i">
                    <div class="flex flex-col items-center gap-1.5">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-[14px] font-bold transition-all"
                            :class="{
                                'bg-success-light border-2 border-success text-success': i + 1 < step,
                                'bg-teal border-2 border-teal text-white shadow-[0_0_0_4px_rgba(68,161,148,0.2)]': i + 1 === step,
                                'bg-white border-2 border-border text-text-muted': i + 1 > step,
                            }">
                            <Check v-if="i + 1 < step" class="w-4 h-4" />
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <span class="text-[13px] font-semibold"
                            :class="{
                                'text-success': i + 1 < step,
                                'text-teal': i + 1 === step,
                                'text-text-muted': i + 1 > step,
                            }">{{ label }}</span>
                    </div>
                    <div v-if="i < 2" class="w-32 h-[3px] mb-5 mx-2 transition-all rounded-full"
                        :class="i + 1 < step ? 'bg-success' : 'bg-border'">
                    </div>
                </template>
            </div>
        </div>

        <!-- ===== STEP 1: UPLOAD ===== -->
        <div v-if="step === 1">
            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2 bg-white border border-border rounded-xl p-6 shadow-sm">
                    <h3 class="text-[16px] font-semibold text-text-primary mb-5">📂 Upload File Excel</h3>

                    <div
                        @dragover.prevent="isDragging = true"
                        @dragleave="isDragging = false"
                        @drop.prevent="onDrop"
                        @click="fileInputRef.click()"
                        class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-all"
                        :class="isDragging ? 'border-teal bg-teal-light' : 'border-border hover:border-teal hover:bg-teal-light/30'"
                    >
                        <input ref="fileInputRef" type="file" accept=".xlsx,.xls" class="hidden" @change="onFileChange" />
                        <div v-if="!selectedFile">
                            <FileSpreadsheet class="w-12 h-12 mx-auto text-text-muted mb-3 opacity-50" />
                            <p class="text-[15px] font-medium text-text-primary">Drop file .xlsx di sini</p>
                            <p class="text-[13px] text-text-muted mt-1">atau klik untuk memilih file (maks. 5 MB)</p>
                            <div class="mt-4 inline-block bg-teal text-white text-[14px] font-semibold px-5 py-2 rounded-lg">
                                Pilih File
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-success-light flex items-center justify-center">
                                <CheckCircle class="w-8 h-8 text-success" />
                            </div>
                            <p class="text-[15px] font-semibold text-text-primary">{{ selectedFile.name }}</p>
                            <p class="text-[13px] text-text-muted">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
                            <button type="button" @click.stop="selectedFile = null" class="text-[13px] text-coral-dark hover:underline">Ganti File</button>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Bulan</label>
                            <select v-model="selectedBulan" class="w-full bg-white border border-border rounded-lg pl-3 pr-8 py-2.5 text-[14px] focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/20">
                                <option v-for="(nm, val) in namaBulan" :key="val" :value="Number(val)">{{ nm }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Tahun</label>
                            <select v-model="selectedTahun" class="w-full bg-white border border-border rounded-lg pl-3 pr-8 py-2.5 text-[14px] focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/20">
                                <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="errorMsg" class="mt-4 bg-coral-light border border-coral/30 rounded-lg p-3 flex items-center gap-2 text-[13px] text-coral-dark">
                        <AlertCircle class="w-4 h-4 flex-shrink-0" />
                        {{ errorMsg }}
                    </div>

                    <div class="mt-4 bg-steel-light border border-steel/20 rounded-lg p-3 text-[13px] text-steel-dark flex items-start gap-2">
                        💡 Data yang sudah ada akan ditimpa (overwrite) secara otomatis.
                    </div>

                    <div class="flex justify-end gap-3 mt-5">
                        <button type="button" @click="router.get(route('rekap.index'))"
                            class="bg-white border border-border text-text-muted px-5 py-2.5 rounded-lg text-[14px] hover:bg-cream">
                            Batal
                        </button>
                        <button type="button" @click="doPreview" :disabled="!selectedFile || isLoading"
                            class="bg-teal text-white px-6 py-2.5 rounded-lg text-[14px] font-semibold shadow-[0_4px_12px_rgba(68,161,148,0.25)] hover:bg-teal-dark disabled:opacity-50 flex items-center gap-2">
                            <Eye v-if="!isLoading" class="w-4 h-4" />
                            <span v-else class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full inline-block"></span>
                            {{ isLoading ? 'Membaca...' : 'Preview Data' }}
                        </button>
                    </div>
                </div>

                <!-- Riwayat Import -->
                <div class="bg-white border border-border rounded-xl p-5 shadow-sm">
                    <h3 class="text-[15px] font-semibold text-text-primary mb-4 flex items-center gap-2">
                        <Clock class="w-4 h-4 text-text-muted" />
                        Riwayat Import
                    </h3>
                    <div v-if="history.length" class="space-y-3">
                        <div v-for="log in history" :key="log.id" class="p-3 bg-cream rounded-lg">
                            <p class="text-[13px] font-medium text-text-primary truncate">{{ log.filename }}</p>
                            <p class="text-[11px] text-text-muted mt-1">
                                {{ namaBulan[log.bulan] }} {{ log.tahun }} &middot; {{ log.total_rows }} baris &middot; {{ log.created_at }}
                            </p>
                            <span class="inline-block mt-1.5 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                                :class="{
                                    'bg-success-light text-success': log.status === 'success',
                                    'bg-warning-light text-warning': log.status === 'partial',
                                    'bg-coral-light text-coral-dark': log.status === 'failed',
                                }">{{ log.status }}</span>
                        </div>
                    </div>
                    <p v-else class="text-[13px] text-text-muted text-center py-4">Belum ada riwayat import.</p>
                </div>
            </div>
        </div>

        <!-- ===== STEP 2: PREVIEW ===== -->
        <div v-if="step === 2">
            <!-- Completed Step 1 Summary -->
            <div class="bg-success-light border border-success/30 rounded-xl p-4 mb-4 flex items-center gap-4">
                <div class="w-9 h-9 rounded-full bg-success flex items-center justify-center flex-shrink-0">
                    <Check class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1">
                    <p class="text-[14px] font-semibold text-success">File berhasil dibaca</p>
                    <p class="text-[12px] text-text-muted">{{ previewMeta?.filename }} &middot; {{ previewMeta?.size }} &middot; Periode: {{ namaBulan[selectedBulan] }} {{ selectedTahun }}</p>
                </div>
                <button @click="resetUpload" class="text-[13px] text-steel hover:underline">Ganti File</button>
            </div>

            <!-- Sheet Selector (only if multiple sheets) -->
            <div v-if="hasMultipleSheets" class="bg-white border border-border rounded-xl p-4 mb-4 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <Layers class="w-5 h-5 text-teal" />
                    <h4 class="text-[14px] font-semibold text-text-primary">Pilih Sheet</h4>
                    <span class="bg-steel-light text-steel-dark text-[11px] font-medium px-2 py-0.5 rounded-full">
                        {{ sheetNames.length }} sheet terdeteksi
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="sheet in sheetNames"
                        :key="sheet"
                        @click="switchSheet(sheet)"
                        :disabled="isSwitchingSheet"
                        class="px-4 py-2 rounded-lg text-[13px] font-medium border transition-all disabled:opacity-50"
                        :class="activeSheet === sheet
                            ? 'bg-teal text-white border-teal shadow-[0_2px_8px_rgba(68,161,148,0.3)]'
                            : 'bg-white text-text-primary border-border hover:border-teal hover:bg-teal-light/30'"
                    >
                        {{ sheet }}
                    </button>
                </div>
                <div v-if="isSwitchingSheet" class="mt-3 flex items-center gap-2 text-[13px] text-teal">
                    <span class="animate-spin w-4 h-4 border-2 border-teal border-t-transparent rounded-full inline-block"></span>
                    Memuat data sheet...
                </div>
            </div>

            <!-- Warning -->
            <div v-if="hasWarning" class="bg-warning-light border border-warning/30 rounded-xl p-3 mb-4 flex items-center gap-2 text-[13px] text-warning">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                Beberapa baris memiliki nilai KN melebihi Lahir Hidup. Periksa data sebelum menyimpan.
            </div>

            <!-- Error on sheet switch -->
            <div v-if="errorMsg" class="bg-coral-light border border-coral/30 rounded-xl p-3 mb-4 flex items-center gap-2 text-[13px] text-coral-dark">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                {{ errorMsg }}
            </div>

            <!-- Preview Tab Switcher -->
            <div class="flex gap-1 mb-4 bg-white border border-border rounded-xl p-1 shadow-sm w-fit">
                <button
                    @click="previewTab = 'parsed'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-[13px] font-semibold transition-all"
                    :class="previewTab === 'parsed'
                        ? 'bg-teal text-white shadow-sm'
                        : 'text-text-muted hover:bg-cream'"
                >
                    <Database class="w-4 h-4" />
                    Data LH & KN
                </button>
                <button
                    @click="previewTab = 'raw'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-[13px] font-semibold transition-all"
                    :class="previewTab === 'raw'
                        ? 'bg-steel text-white shadow-sm'
                        : 'text-text-muted hover:bg-cream'"
                >
                    <Table class="w-4 h-4" />
                    Data Excel Asli
                </button>
            </div>

            <!-- ====== TAB 1: PARSED DATA (LH & KN) ====== -->
            <div v-if="previewTab === 'parsed'" class="bg-white border border-border rounded-xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-[16px] font-semibold text-text-primary">🔍 Preview Data yang Akan Diimport</h3>
                        <p class="text-[13px] text-text-muted mt-0.5">Data Lahir Hidup & KN Lengkap yang terdeteksi</p>
                    </div>
                    <span class="bg-teal-light text-teal-dark text-[13px] font-medium px-3 py-1.5 rounded-full">
                        {{ previewRows.length }} baris ditemukan
                    </span>
                </div>

                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-steel text-white text-[13px] font-semibold uppercase tracking-[0.04em]">
                            <th class="px-4 py-3 text-center w-12 border-r border-white/20">No</th>
                            <th class="px-4 py-3 text-left border-r border-white/20">Desa</th>
                            <th colspan="3" class="px-4 py-3 text-center border-r border-white/20">Lahir Hidup</th>
                            <th colspan="3" class="px-4 py-3 text-center border-r border-white/20">KN Lengkap</th>
                            <th class="px-4 py-3 text-center">Sumber</th>
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
                        <tr v-for="(row, i) in previewRows" :key="row.desa_id"
                            class="border-b border-border transition-colors"
                            :class="[
                                row.warning ? 'bg-[#FFF8F8] border-l-4 border-l-coral' : (i % 2 === 0 ? 'bg-white' : 'bg-[#F8F6F0]')
                            ]"
                        >
                            <td class="px-4 py-3 text-center text-text-muted text-[13px]">{{ i + 1 }}</td>
                            <td class="px-4 py-3 text-left font-semibold text-text-primary text-[14px]">{{ row.nama_desa }}</td>
                            <td class="px-3 py-3 text-center font-mono">{{ row.lahir_hidup_l }}</td>
                            <td class="px-3 py-3 text-center font-mono">{{ row.lahir_hidup_p }}</td>
                            <td class="px-3 py-3 text-center font-mono font-semibold">{{ row.lahir_hidup_l + row.lahir_hidup_p }}</td>
                            <td class="px-3 py-3 text-center font-mono">{{ row.kn_lengkap_l }}</td>
                            <td class="px-3 py-3 text-center font-mono">{{ row.kn_lengkap_p }}</td>
                            <td class="px-3 py-3 text-center font-mono font-semibold">{{ row.kn_lengkap_l + row.kn_lengkap_p }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                    :class="rowStatusChip(row).cls">
                                    {{ rowStatusChip(row).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#F0ECE4] border-t border-border font-semibold">
                            <td colspan="2" class="px-4 py-3 font-bold text-text-primary text-[13px] uppercase">TOTAL</td>
                            <td colspan="3" class="px-3 py-3 text-center font-mono font-bold">LH = {{ totalLH }}</td>
                            <td colspan="3" class="px-3 py-3 text-center font-mono font-bold">KN = {{ totalKN }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="bg-cream border-t border-border px-5 py-3 text-[13px] text-text-muted">
                    📊 Diambil dari kolom: <strong>B</strong> (Desa) &middot; <strong>C-D</strong> (Lahir Hidup) &middot; <strong>{{ knCols.l_lbl }}-{{ knCols.p_lbl }}</strong> (KN Lengkap)
                    <span v-if="hasMultipleSheets" class="ml-2">· Sheet: <strong class="text-teal">{{ activeSheet }}</strong></span>
                </div>
            </div>

            <!-- ====== TAB 2: RAW EXCEL DATA ====== -->
            <div v-if="previewTab === 'raw' && rawData" class="bg-white border border-border rounded-xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-[16px] font-semibold text-text-primary">📄 Data Excel Asli</h3>
                        <p class="text-[13px] text-text-muted mt-0.5">
                            Sheet: <strong class="text-teal">{{ rawData.sheetTitle }}</strong>
                            · {{ rawData.totalRows }} baris × {{ rawData.totalCols }} kolom
                            <span v-if="rawData.totalRows > rawData.rows.length" class="text-coral-dark">(menampilkan {{ rawData.rows.length }} baris pertama)</span>
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-sm border-collapse min-w-[1200px]">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-steel text-white text-[12px] font-semibold uppercase tracking-wider">
                                <th class="px-3 py-2.5 text-center border-r border-white/20 w-10 bg-steel sticky left-0 z-20">#</th>
                                <th v-for="(header, hi) in rawData.headers" :key="hi"
                                    class="px-3 py-2.5 text-center border-r border-white/20 min-w-[80px]">
                                    {{ header }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, ri) in rawData.rows" :key="ri"
                                class="border-b border-border/50 transition-colors hover:bg-cream/50"
                                :class="ri % 2 === 0 ? 'bg-white' : 'bg-[#FAFAF5]'">
                                <td class="px-3 py-2 text-center text-[11px] text-text-muted font-mono border-r border-border/50 sticky left-0 z-10"
                                    :class="ri % 2 === 0 ? 'bg-white' : 'bg-[#FAFAF5]'">
                                    {{ ri + 1 }}
                                </td>
                                <td v-for="(cell, ci) in row" :key="ci"
                                    class="px-3 py-2 text-[12px] border-r border-border/30 whitespace-nowrap"
                                    :class="cell ? 'text-text-primary' : 'text-text-muted/30'"
                                    :title="cell || ''">
                                    {{ cell || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="rawData.totalRows > rawData.rows.length" class="bg-cream border-t border-border px-5 py-3 text-[13px] text-text-muted text-center">
                    ⚠️ Hanya menampilkan {{ rawData.rows.length }} dari {{ rawData.totalRows }} baris. File lengkap tetap akan diproses saat import.
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between mt-5">
                <button @click="step = 1" class="text-[13px] text-teal hover:underline flex items-center gap-1">
                    ← Kembali
                </button>
                <div class="flex flex-col items-end gap-1">
                    <button @click="doImport" :disabled="isLoading || previewRows.length === 0"
                        class="bg-teal text-white px-7 py-2.5 rounded-lg text-[14px] font-semibold shadow-[0_4px_12px_rgba(68,161,148,0.25)] hover:bg-teal-dark disabled:opacity-50 flex items-center gap-2">
                        <CheckCircle v-if="!isLoading" class="w-4 h-4" />
                        <span v-else class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                        {{ isLoading ? 'Menyimpan...' : 'Simpan Semua Data' }}
                    </button>
                    <p class="text-[11px] text-text-muted">{{ previewRows.length }} baris akan disimpan</p>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
