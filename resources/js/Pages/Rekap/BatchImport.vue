<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import {
    Upload, CheckCircle, AlertCircle, AlertTriangle, Loader2,
    FileSpreadsheet, Trash2, ChevronDown, ChevronUp, Layers, Files
} from 'lucide-vue-next';

const props = defineProps({
    namaBulan: Object,
});

// ===== STATE =====
const mode = ref('batch'); // 'batch' | 'multisheet'
const tahun = ref(new Date().getFullYear());
const tahunOptions = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i);

// --- Batch (Opsi A) ---
const batchFiles = ref([]);
const batchDragOver = ref(false);
const batchFileInput = ref(null);
const batchItems = ref([]); // preview results from server
const batchLoading = ref(false);
const batchError = ref('');
const batchExpanded = ref({}); // track expanded file rows

// --- Multi-Sheet (Opsi C) ---
const msFile = ref(null);
const msDragOver = ref(false);
const msFileInput = ref(null);
const msItems = ref([]); // sheet results
const msLoading = ref(false);
const msError = ref('');
const msFilename = ref('');

// ===== BATCH HELPERS =====
function onBatchDrop(e) {
    batchDragOver.value = false;
    const files = [...e.dataTransfer.files].filter(f => f.name.match(/\.(xlsx|xls)$/i));
    addBatchFiles(files);
}

function onBatchFileChange(e) {
    addBatchFiles([...e.target.files]);
    e.target.value = '';
}

function addBatchFiles(files) {
    for (const f of files) {
        if (!batchFiles.value.find(existing => existing.name === f.name)) {
            batchFiles.value.push(f);
        }
    }
}

function removeBatchFile(index) {
    batchFiles.value.splice(index, 1);
    batchItems.value.splice(index, 1);
}

// ===== BATCH PREVIEW =====
async function doBatchPreview() {
    if (batchFiles.value.length === 0) { batchError.value = 'Pilih minimal 1 file Excel.'; return; }
    batchError.value = '';
    batchLoading.value = true;
    batchItems.value = [];

    const formData = new FormData();
    formData.append('tahun', tahun.value);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');
    batchFiles.value.forEach(f => formData.append('files[]', f));

    try {
        const res = await axios.post(route('rekap.import.batch-preview'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        batchItems.value = res.data.map(item => ({
            ...item,
            bulan: item.detectedBulan ?? 1,
        }));
    } catch (err) {
        batchError.value = err.response?.data?.message ?? 'Gagal membaca file. Pastikan format file valid.';
    } finally {
        batchLoading.value = false;
    }
}

// ===== BATCH SAVE =====
const batchSaving = ref(false);
function doBatchStore() {
    const unassigned = batchItems.value.filter(i => !i.bulan);
    if (unassigned.length > 0) {
        batchError.value = 'Semua file harus memiliki bulan yang valid sebelum disimpan.';
        return;
    }
    batchSaving.value = true;
    router.post(route('rekap.import.batch-store'), {
        tahun: tahun.value,
        items: batchItems.value.map(i => ({
            tmpPath: i.tmpPath,
            bulan: i.bulan,
            rows: i.rows,
            filename: i.filename,
        })),
    }, { onFinish: () => { batchSaving.value = false; } });
}

const batchTotalRows = computed(() => batchItems.value.reduce((s, i) => s + i.rowCount, 0));
const batchHasUndetected = computed(() => batchItems.value.some(i => !i.detectedBulan));

// ===== MULTI-SHEET HELPERS =====
function onMsDrop(e) {
    msDragOver.value = false;
    const file = e.dataTransfer.files[0];
    if (file?.name.match(/\.(xlsx|xls)$/i)) msFile.value = file;
}

function onMsFileChange(e) {
    msFile.value = e.target.files[0] ?? null;
    e.target.value = '';
}

// ===== MULTI-SHEET PREVIEW =====
async function doMsPreview() {
    if (!msFile.value) { msError.value = 'Pilih file Excel terlebih dahulu.'; return; }
    msError.value = '';
    msLoading.value = true;
    msItems.value = [];

    const formData = new FormData();
    formData.append('file', msFile.value);
    formData.append('tahun', tahun.value);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

    try {
        const res = await axios.post(route('rekap.import.multisheet-preview'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        msFilename.value = res.data.filename;
        msItems.value = res.data.sheetResults.map(s => ({
            ...s,
            bulan: s.detectedBulan ?? 1,
            include: s.rowCount > 0, // auto-uncheck empty sheets
        }));
    } catch (err) {
        msError.value = err.response?.data?.message ?? 'Gagal membaca file.';
    } finally {
        msLoading.value = false;
    }
}

// ===== MULTI-SHEET SAVE =====
const msSaving = ref(false);
function doMsStore() {
    const sheets = msItems.value.filter(s => s.include);
    if (sheets.length === 0) { msError.value = 'Pilih minimal 1 sheet untuk diimport.'; return; }
    msSaving.value = true;
    router.post(route('rekap.import.multisheet-store'), {
        tahun: tahun.value,
        sheets: sheets.map(s => ({
            sheetName: s.sheetName,
            bulan: s.bulan,
            rows: s.rows,
        })),
    }, { onFinish: () => { msSaving.value = false; } });
}

const msTotalRows = computed(() => msItems.value.filter(s => s.include).reduce((sum, s) => sum + s.rowCount, 0));

function statusChip(status) {
    if (status === 'success') return 'bg-green-100 text-green-700';
    if (status === 'warning') return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-700';
}
</script>

<template>
    <AppLayout title="Import Batch / Tahunan" breadcrumb="Beranda / Import Batch">

        <!-- Mode Switcher -->
        <div class="bg-white border border-gray-200 rounded-2xl p-1.5 mb-6 shadow-sm flex gap-1 w-fit">
            <button @click="mode = 'batch'; batchItems = []; batchFiles = [];"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                :class="mode === 'batch' ? 'bg-teal text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'">
                <Files class="w-4 h-4" />
                Multi-File (Opsi A)
            </button>
            <button @click="mode = 'multisheet'; msItems = []; msFile = null;"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                :class="mode === 'multisheet' ? 'bg-teal text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'">
                <Layers class="w-4 h-4" />
                Multi-Sheet (Opsi C)
            </button>
        </div>

        <!-- Shared: Tahun Selector -->
        <div class="bg-white border border-gray-200 rounded-2xl p-5 mb-5 shadow-sm flex items-center gap-4">
            <label class="text-sm font-semibold text-gray-700">Tahun Data:</label>
            <select v-model="tahun" class="border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium focus:ring-2 focus:ring-teal/20 focus:border-teal outline-none bg-white">
                <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
            </select>
            <p class="text-xs text-gray-400 ml-2">Semua file yang diupload akan dimasukkan ke tahun ini.</p>
        </div>

        <!-- ===== OPSI A: BATCH MULTI-FILE ===== -->
        <div v-if="mode === 'batch'" class="space-y-5">

            <!-- Drop zone -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <Files class="w-5 h-5 text-teal-600" />
                    Upload Banyak File Sekaligus
                    <span class="text-xs font-normal text-gray-400 ml-1">(Maks. 12 file .xlsx)</span>
                </h2>

                <div
                    @dragover.prevent="batchDragOver = true"
                    @dragleave="batchDragOver = false"
                    @drop.prevent="onBatchDrop"
                    @click="batchFileInput.click()"
                    class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-all"
                    :class="batchDragOver ? 'border-teal-400 bg-teal-50' : 'border-gray-300 hover:border-teal-400 hover:bg-teal-50/30'"
                >
                    <input ref="batchFileInput" type="file" accept=".xlsx,.xls" multiple class="hidden" @change="onBatchFileChange" />
                    <FileSpreadsheet class="w-10 h-10 mx-auto text-gray-400 mb-3" />
                    <p class="text-sm font-semibold text-gray-700">Drop semua file .xlsx di sini</p>
                    <p class="text-xs text-gray-400 mt-1">atau klik untuk memilih beberapa file sekaligus</p>
                    <div class="mt-3 inline-block bg-teal-600 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        Pilih File
                    </div>
                </div>

                <!-- File List -->
                <div v-if="batchFiles.length > 0" class="mt-4 space-y-2">
                    <div v-for="(f, idx) in batchFiles" :key="f.name"
                        class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200">
                        <FileSpreadsheet class="w-5 h-5 text-green-600 flex-shrink-0" />
                        <span class="flex-1 text-sm font-medium text-gray-800 truncate">{{ f.name }}</span>
                        <span class="text-xs text-gray-400">{{ (f.size / 1024).toFixed(1) }} KB</span>
                        <button @click.stop="removeBatchFile(idx)" class="text-gray-400 hover:text-red-500 transition-colors">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div v-if="batchError" class="mt-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 text-sm">
                    <AlertCircle class="w-4 h-4 flex-shrink-0" />
                    {{ batchError }}
                </div>

                <button @click="doBatchPreview" :disabled="batchFiles.length === 0 || batchLoading"
                    class="mt-5 w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold transition-all"
                    :class="batchFiles.length > 0 && !batchLoading ? 'bg-teal-600 hover:bg-teal-700 text-white shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                    <Loader2 v-if="batchLoading" class="w-4 h-4 animate-spin" />
                    <Upload v-else class="w-4 h-4" />
                    {{ batchLoading ? 'Membaca semua file...' : `Baca ${batchFiles.length} File` }}
                </button>
            </div>

            <!-- Preview Results -->
            <div v-if="batchItems.length > 0" class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900">Hasil Pembacaan File</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ batchItems.length }} file · {{ batchTotalRows }} baris terdeteksi
                        </p>
                    </div>
                    <div v-if="batchHasUndetected" class="flex items-center gap-1.5 text-amber-600 text-xs font-medium bg-amber-50 px-3 py-1.5 rounded-full border border-amber-200">
                        <AlertTriangle class="w-3.5 h-3.5" />
                        Beberapa bulan tidak terdeteksi, atur manual
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="(item, idx) in batchItems" :key="idx" class="p-4">
                        <div class="flex items-center gap-3">
                            <!-- Status icon -->
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="item.rowCount > 0 ? 'bg-green-100' : 'bg-red-100'">
                                <CheckCircle v-if="item.rowCount > 0" class="w-5 h-5 text-green-600" />
                                <AlertCircle v-else class="w-5 h-5 text-red-500" />
                            </div>

                            <!-- Filename & info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ item.filename }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-500">{{ item.size }}</span>
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded-full"
                                        :class="item.rowCount > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                        {{ item.rowCount }} baris
                                    </span>
                                    <span v-if="item.hasWarning" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                        ⚠️ Ada KN &gt; LH
                                    </span>
                                </div>
                            </div>

                            <!-- Bulan Selector -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <label class="text-xs text-gray-500 font-medium">Bulan:</label>
                                <select v-model="item.bulan"
                                    class="border rounded-lg px-2 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-teal/20 focus:border-teal outline-none"
                                    :class="item.detectedBulan ? 'border-green-300 bg-green-50 text-green-800' : 'border-amber-300 bg-amber-50 text-amber-800'">
                                    <option v-for="(nm, val) in namaBulan" :key="val" :value="Number(val)">{{ nm }}</option>
                                </select>
                                <span v-if="!item.detectedBulan" class="text-[10px] text-amber-600 font-medium">Manual</span>
                                <span v-else class="text-[10px] text-green-600 font-medium">Auto</span>
                            </div>

                            <!-- Expand toggle -->
                            <button @click="batchExpanded[idx] = !batchExpanded[idx]" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors text-gray-400">
                                <ChevronDown v-if="!batchExpanded[idx]" class="w-4 h-4" />
                                <ChevronUp v-else class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Expanded row detail -->
                        <div v-if="batchExpanded[idx] && item.rows.length > 0" class="mt-3 ml-12 overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-100 text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Desa</th>
                                        <th class="px-3 py-2 text-center font-semibold">LH L</th>
                                        <th class="px-3 py-2 text-center font-semibold">LH P</th>
                                        <th class="px-3 py-2 text-center font-semibold">KN L</th>
                                        <th class="px-3 py-2 text-center font-semibold">KN P</th>
                                        <th class="px-3 py-2 text-center font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in item.rows" :key="row.desa_id"
                                        class="border-t border-gray-100"
                                        :class="row.warning ? 'bg-red-50' : 'bg-white'">
                                        <td class="px-3 py-1.5 font-medium text-gray-800">{{ row.nama_desa }}</td>
                                        <td class="px-3 py-1.5 text-center font-mono">{{ row.lahir_hidup_l }}</td>
                                        <td class="px-3 py-1.5 text-center font-mono">{{ row.lahir_hidup_p }}</td>
                                        <td class="px-3 py-1.5 text-center font-mono">{{ row.kn_lengkap_l }}</td>
                                        <td class="px-3 py-1.5 text-center font-mono">{{ row.kn_lengkap_p }}</td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                                :class="row.warning ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">
                                                {{ row.warning ? '⚠️ KN>LH' : '✓ OK' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Siap menyimpan <strong class="text-gray-900">{{ batchTotalRows }} baris</strong> dari <strong class="text-gray-900">{{ batchItems.length }} file</strong> ke tahun <strong>{{ tahun }}</strong>
                    </p>
                    <button @click="doBatchStore" :disabled="batchSaving || batchTotalRows === 0"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold transition-all"
                        :class="!batchSaving && batchTotalRows > 0 ? 'bg-teal-600 hover:bg-teal-700 text-white shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                        <Loader2 v-if="batchSaving" class="w-4 h-4 animate-spin" />
                        <CheckCircle v-else class="w-4 h-4" />
                        {{ batchSaving ? 'Menyimpan...' : 'Simpan Semua Data' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== OPSI C: MULTI-SHEET ===== -->
        <div v-if="mode === 'multisheet'" class="space-y-5">

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <Layers class="w-5 h-5 text-teal-600" />
                    Upload 1 File Excel dengan Banyak Sheet
                </h2>
                <p class="text-xs text-gray-400 mb-4">Setiap sheet = 1 bulan. Nama sheet harus mengandung nama bulan (misal: "Januari", "Februari", dst).</p>

                <!-- Drop zone -->
                <div
                    @dragover.prevent="msDragOver = true"
                    @dragleave="msDragOver = false"
                    @drop.prevent="onMsDrop"
                    @click="msFileInput.click()"
                    class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-all"
                    :class="msDragOver ? 'border-teal-400 bg-teal-50' : 'border-gray-300 hover:border-teal-400 hover:bg-teal-50/30'"
                >
                    <input ref="msFileInput" type="file" accept=".xlsx,.xls" class="hidden" @change="onMsFileChange" />
                    <Layers class="w-10 h-10 mx-auto text-gray-400 mb-3" />
                    <p v-if="!msFile" class="text-sm font-semibold text-gray-700">Drop file Excel multi-sheet di sini</p>
                    <p v-else class="text-sm font-semibold text-teal-700">✓ {{ msFile.name }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ msFile ? (msFile.size/1024).toFixed(1)+' KB' : 'atau klik untuk memilih file' }}</p>
                </div>

                <div v-if="msError" class="mt-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 text-sm">
                    <AlertCircle class="w-4 h-4 flex-shrink-0" /> {{ msError }}
                </div>

                <button @click="doMsPreview" :disabled="!msFile || msLoading"
                    class="mt-5 w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold transition-all"
                    :class="msFile && !msLoading ? 'bg-teal-600 hover:bg-teal-700 text-white shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                    <Loader2 v-if="msLoading" class="w-4 h-4 animate-spin" />
                    <Upload v-else class="w-4 h-4" />
                    {{ msLoading ? 'Membaca semua sheet...' : 'Baca File' }}
                </button>
            </div>

            <!-- Sheet Preview Results -->
            <div v-if="msItems.length > 0" class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900">Sheet Terdeteksi — {{ msFilename }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Centang sheet yang ingin diimport, lalu atur bulan-nya.</p>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="(sheet, idx) in msItems" :key="idx" class="px-6 py-4 flex items-center gap-4">
                        <input type="checkbox" v-model="sheet.include"
                            class="w-4 h-4 accent-teal-600 flex-shrink-0 cursor-pointer" />

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ sheet.sheetName }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500">{{ sheet.rowCount }} baris desa</span>
                                <span v-if="sheet.hasWarning" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">⚠️ Ada KN &gt; LH</span>
                                <span v-if="sheet.rowCount === 0" class="text-xs text-red-500 font-medium">Tidak ada data</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <label class="text-xs text-gray-500 font-medium">Bulan:</label>
                            <select v-model="sheet.bulan" :disabled="!sheet.include"
                                class="border rounded-lg px-2 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-teal/20 outline-none disabled:opacity-50"
                                :class="sheet.detectedBulan ? 'border-green-300 bg-green-50 text-green-800' : 'border-amber-300 bg-amber-50 text-amber-800'">
                                <option v-for="(nm, val) in namaBulan" :key="val" :value="Number(val)">{{ nm }}</option>
                            </select>
                            <span class="text-[10px] font-medium" :class="sheet.detectedBulan ? 'text-green-600' : 'text-amber-600'">
                                {{ sheet.detectedBulan ? 'Auto' : 'Manual' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        <strong class="text-gray-900">{{ msItems.filter(s=>s.include).length }} sheet</strong> dipilih ·
                        <strong class="text-gray-900">{{ msTotalRows }} baris</strong> akan diimport
                    </p>
                    <button @click="doMsStore" :disabled="msSaving || msTotalRows === 0"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold transition-all"
                        :class="!msSaving && msTotalRows > 0 ? 'bg-teal-600 hover:bg-teal-700 text-white shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                        <Loader2 v-if="msSaving" class="w-4 h-4 animate-spin" />
                        <CheckCircle v-else class="w-4 h-4" />
                        {{ msSaving ? 'Menyimpan...' : 'Simpan Sheet Terpilih' }}
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
