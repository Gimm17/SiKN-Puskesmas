<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Download, Upload, Database, ShieldAlert, CheckCircle, XCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    rekapCount: Number,
    lastUpdated: String,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const fileInput = ref(null);
const selectedFile = ref(null);
const importing = ref(false);
const showConfirm = ref(false);

function onFileChange(e) {
    selectedFile.value = e.target.files[0] ?? null;
}

function triggerFileInput() {
    fileInput.value?.click();
}

function handleDrop(e) {
    const file = e.dataTransfer.files[0];
    if (file && file.name.endsWith('.json')) {
        selectedFile.value = file;
    }
}

function confirmImport() {
    if (!selectedFile.value) return;
    showConfirm.value = true;
}

function doImport() {
    showConfirm.value = false;
    importing.value = true;
    const form = new FormData();
    form.append('backup_file', selectedFile.value);
    router.post(route('backup.import'), form, {
        onFinish: () => {
            importing.value = false;
            selectedFile.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}
</script>

<template>
    <AppLayout title="Backup & Restore">
        <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">

            <!-- Header -->
            <div class="mb-2">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Database class="w-6 h-6 text-teal-600" />
                    Backup & Restore Database
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Export data rekap Anda ke file JSON, lalu import kembali ke server manapun dengan aman.
                </p>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash.success" class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl p-4">
                <CheckCircle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                <p class="text-sm font-medium">{{ flash.success }}</p>
            </div>
            <div v-if="flash.error" class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
                <XCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                <p class="text-sm font-medium">{{ flash.error }}</p>
            </div>

            <!-- EXPORT Card -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center">
                            <Download class="w-5 h-5 text-teal-600" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Export Data (Backup)</h2>
                            <p class="text-xs text-gray-500">Download semua data rekap ke file .json</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 pb-4 border-t border-gray-100 pt-4 bg-gray-50 flex items-center justify-between gap-4">
                    <div class="text-sm text-gray-600 space-y-0.5">
                        <div>Total data: <span class="font-bold text-gray-900">{{ rekapCount.toLocaleString('id-ID') }} baris</span></div>
                        <div>Terakhir diubah: <span class="font-medium text-gray-700">{{ lastUpdated }}</span></div>
                    </div>
                    <a :href="route('backup.export')"
                        class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                        <Download class="w-4 h-4" />
                        Export JSON
                    </a>
                </div>
            </div>

            <!-- IMPORT Card -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <Upload class="w-5 h-5 text-amber-600" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Import Data (Restore)</h2>
                            <p class="text-xs text-gray-500">Upload file backup .json untuk memulihkan data</p>
                        </div>
                    </div>
                </div>

                <!-- Warning -->
                <div class="mx-6 mb-4 flex items-start gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-3.5">
                    <ShieldAlert class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                    <p class="text-xs leading-relaxed">
                        <strong>Perhatian:</strong> Proses import akan <strong>menghapus seluruh data rekap yang ada</strong> saat ini dan menggantinya dengan data dari file backup. Pastikan Anda sudah melakukan export terlebih dahulu!
                    </p>
                </div>

                <!-- Drop Zone -->
                <div class="px-6 pb-6">
                    <div
                        @click="triggerFileInput"
                        @dragover.prevent
                        @drop.prevent="handleDrop"
                        class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-colors"
                        :class="selectedFile ? 'border-teal-400 bg-teal-50' : 'border-gray-300 hover:border-gray-400 bg-gray-50'"
                    >
                        <input type="file" ref="fileInput" accept=".json" class="hidden" @change="onFileChange" />
                        <Upload class="w-8 h-8 mx-auto mb-2" :class="selectedFile ? 'text-teal-600' : 'text-gray-400'" />
                        <p v-if="!selectedFile" class="text-sm text-gray-500">
                            Klik atau seret file <strong class="text-gray-700">.json</strong> ke sini
                        </p>
                        <p v-else class="text-sm font-semibold text-teal-700">
                            ✓ {{ selectedFile.name }} <span class="text-teal-500 font-normal">({{ (selectedFile.size / 1024).toFixed(1) }} KB)</span>
                        </p>
                    </div>

                    <button
                        @click="confirmImport"
                        :disabled="!selectedFile || importing"
                        class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-semibold px-5 py-3 rounded-xl transition-all"
                        :class="selectedFile && !importing
                            ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                    >
                        <Loader2 v-if="importing" class="w-4 h-4 animate-spin" />
                        <Upload v-else class="w-4 h-4" />
                        {{ importing ? 'Sedang Mengimpor...' : 'Import & Restore Data' }}
                    </button>
                </div>
            </div>

            <!-- Info Footer -->
            <p class="text-xs text-center text-gray-400">
                Format file backup: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">sikn-backup-YYYYMMDD-HHMMSS.json</code>
            </p>
        </div>

        <!-- Confirm Modal -->
        <Teleport to="body">
            <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <ShieldAlert class="w-6 h-6 text-red-600" />
                    </div>
                    <h3 class="text-center text-lg font-bold text-gray-900 mb-1">Konfirmasi Restore</h3>
                    <p class="text-center text-sm text-gray-500 mb-6">
                        Seluruh data rekap yang ada akan <strong class="text-red-600">dihapus permanen</strong> dan digantikan dengan data dari file backup. Lanjutkan?
                    </p>
                    <div class="flex gap-3">
                        <button @click="showConfirm = false" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button @click="doImport" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold transition-colors">
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
