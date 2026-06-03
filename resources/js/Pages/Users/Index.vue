<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Users, ShieldCheck, UserCheck, Search, Plus, X, Edit2, KeyRound, Trash2, Eye, EyeOff } from 'lucide-vue-next';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    users: Array,
    stats: Object,
    search: String,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ─── Search ───────────────────────────────────────────
const searchQ = ref(props.search ?? '');
let debounce;
watch(searchQ, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => router.get(route('users.index'), { search: v }, { preserveState: true }), 300);
});

// ─── Add User Modal ───────────────────────────────────
const showAddModal  = ref(false);
const showEditModal = ref(false);
const editTarget    = ref(null);
const showPw        = ref(false);
const showPwStrip   = ref(false);

const addForm = useForm({
    name: '', email: '', password: '', password_confirmation: '', role: 'petugas',
});

const editForm = useForm({
    name: '', email: '', role: 'petugas', password: '', password_confirmation: '',
});

function openEdit(user) {
    editTarget.value = user;
    editForm.name  = user.name;
    editForm.email = user.email;
    editForm.role  = user.role;
    editForm.password = '';
    editForm.password_confirmation = '';
    showEditModal.value = true;
}

function submitAdd() {
    addForm.post(route('users.store'), {
        onSuccess: () => { showAddModal.value = false; addForm.reset(); },
    });
}

function submitEdit() {
    editForm.put(route('users.update', editTarget.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
}

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const showResetModal = ref(false);
const resetTarget = ref(null);

function doDelete(user) {
    deleteTarget.value = user;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (deleteTarget.value) {
        router.delete(route('users.destroy', deleteTarget.value.id));
        showDeleteModal.value = false;
        deleteTarget.value = null;
    }
}

function resetPw(user) {
    resetTarget.value = user;
    showResetModal.value = true;
}

function confirmReset() {
    if (resetTarget.value) {
        router.post(route('users.reset-password', resetTarget.value.id));
        showResetModal.value = false;
        resetTarget.value = null;
    }
}

// ─── Password Strength ────────────────────────────────
const pwStrength = computed(() => {
    const pw = addForm.password;
    if (!pw) return { score: 0, label: '', cls: '' };
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const map = [
        { label: 'Sangat lemah', cls: 'bg-coral-dark' },
        { label: 'Lemah', cls: 'bg-coral' },
        { label: 'Cukup', cls: 'bg-warning' },
        { label: 'Kuat', cls: 'bg-teal' },
        { label: 'Sangat kuat', cls: 'bg-success' },
    ];
    return { score, ...map[score] };
});

// ─── Avatar color by role ─────────────────────────────
const avatarColors = ['#44A194', '#537D96', '#EC8F8D', '#8B7355', '#2D7A6F'];
function avatarColor(index) {
    return avatarColors[index % avatarColors.length];
}

const roleBadge = {
    admin:   { cls: 'bg-teal-deeper text-white', label: 'ADMIN' },
    petugas: { cls: 'bg-steel-light text-steel-dark', label: 'PETUGAS' },
};
</script>

<template>
    <AppLayout title="Kelola Pengguna" breadcrumb="Beranda / Kelola Pengguna">

        <!-- SECTION 1: Summary Strip -->
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div class="bg-white border border-border rounded-xl p-4 flex items-center gap-3 shadow-sm">
                <div class="w-9 h-9 rounded-full bg-teal-light flex items-center justify-center text-teal flex-shrink-0">
                    <Users class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-[15px] font-semibold text-text-primary">{{ stats.total }} Pengguna Aktif</p>
                    <p class="text-[12px] text-text-muted">Semua dapat login</p>
                </div>
            </div>
            <div class="bg-white border border-border rounded-xl p-4 flex items-center gap-3 shadow-sm">
                <div class="w-9 h-9 rounded-full bg-steel-light flex items-center justify-center text-steel flex-shrink-0">
                    <ShieldCheck class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-[15px] font-semibold text-text-primary">{{ stats.admin }} Admin</p>
                    <p class="text-[12px] text-text-muted">Akses penuh sistem</p>
                </div>
            </div>
            <div class="bg-white border border-border rounded-xl p-4 flex items-center gap-3 shadow-sm">
                <div class="w-9 h-9 rounded-full bg-[#F0EAD0] flex items-center justify-center text-[#8B7355] flex-shrink-0">
                    <UserCheck class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-[15px] font-semibold text-text-primary">{{ stats.petugas }} Petugas</p>
                    <p class="text-[12px] text-text-muted">Input & export data</p>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Action Bar -->
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="relative w-72">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                <input v-model="searchQ" type="text" placeholder="Cari nama atau email..."
                    class="w-full bg-white border border-border rounded-xl pl-10 pr-4 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
            </div>
            <button @click="showAddModal = true"
                class="bg-teal hover:bg-teal-dark text-white text-[14px] font-semibold px-4 py-2.5 rounded-xl flex items-center gap-2 shadow-[0_4px_12px_rgba(68,161,148,0.25)] transition-all active:scale-[0.98]">
                <Plus class="w-4 h-4" />
                Tambah Pengguna
            </button>
        </div>

        <!-- SECTION 3: User Table -->
        <div class="bg-white border border-border rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-steel text-white text-[13px] font-semibold uppercase tracking-[0.04em]">
                        <th class="px-4 py-3.5 text-center w-12 border-r border-white/20">No</th>
                        <th class="px-4 py-3.5 text-left border-r border-white/20">Nama Pengguna</th>
                        <th class="px-4 py-3.5 text-left border-r border-white/20">Email</th>
                        <th class="px-4 py-3.5 text-center w-28 border-r border-white/20">Role</th>
                        <th class="px-4 py-3.5 text-center w-28 border-r border-white/20">Dibuat</th>
                        <th class="px-4 py-3.5 text-center w-24 border-r border-white/20">Status</th>
                        <th class="px-4 py-3.5 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!users.length">
                        <td colspan="7" class="py-14 text-center text-text-muted text-[14px]">
                            Tidak ada pengguna yang ditemukan.
                        </td>
                    </tr>
                    <tr v-for="(user, i) in users" :key="user.id"
                        class="border-b border-border hover:bg-teal-light/30 transition-colors"
                        :class="i % 2 === 0 ? 'bg-white' : 'bg-[#F8F6F0]'"
                    >
                        <!-- No -->
                        <td class="px-4 py-3.5 text-center text-text-muted text-[13px]">{{ i + 1 }}</td>
                        
                        <!-- Nama + Avatar -->
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-[14px] font-bold flex-shrink-0"
                                    :style="{ backgroundColor: avatarColor(i) }">
                                    {{ user.initial }}
                                </div>
                                <div>
                                    <p class="text-[14px] font-semibold text-text-primary leading-tight">{{ user.name }}</p>
                                    <p class="text-[11px] text-text-muted mt-0.5">{{ user.is_self ? 'Akun Anda' : (user.role === 'admin' ? 'Administrator' : 'Petugas Puskesmas') }}</p>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Email -->
                        <td class="px-4 py-3.5">
                            <span class="text-[13px] font-mono text-text-muted">{{ user.email }}</span>
                        </td>
                        
                        <!-- Role Badge -->
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-mono font-semibold tracking-wide"
                                :class="roleBadge[user.role]?.cls">
                                {{ roleBadge[user.role]?.label }}
                            </span>
                        </td>
                        
                        <!-- Dibuat -->
                        <td class="px-4 py-3.5 text-center text-[13px] text-text-muted">{{ user.created_at }}</td>
                        
                        <!-- Status -->
                        <td class="px-4 py-3.5 text-center">
                            <span class="flex items-center justify-center gap-1.5 text-[13px] font-medium text-success">
                                <span class="w-2 h-2 rounded-full bg-success inline-block"></span>
                                Aktif
                            </span>
                        </td>
                        
                        <!-- Aksi -->
                        <td class="px-4 py-3.5">
                            <div class="flex items-center justify-center gap-1.5">
                                <button @click="openEdit(user)"
                                    class="w-8 h-8 bg-steel-light text-steel rounded-lg flex items-center justify-center hover:bg-steel hover:text-white transition-colors"
                                    title="Edit pengguna">
                                    <Edit2 class="w-3.5 h-3.5" />
                                </button>
                                <button @click="resetPw(user)"
                                    class="w-8 h-8 bg-cream text-text-muted rounded-lg flex items-center justify-center hover:bg-warning-light hover:text-warning transition-colors"
                                    title="Reset password ke default">
                                    <KeyRound class="w-3.5 h-3.5" />
                                </button>
                                <button v-if="!user.is_self"
                                    @click="doDelete(user)"
                                    class="w-8 h-8 bg-coral-light text-coral-dark rounded-lg flex items-center justify-center hover:bg-coral-dark hover:text-white transition-colors"
                                    title="Hapus pengguna">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ═══════════════ ADD USER MODAL ═══════════════ -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAddModal = false"></div>
                
                <!-- Card -->
                <div class="relative bg-white rounded-2xl w-[460px] p-8 shadow-2xl z-10 animate-in fade-in zoom-in-95 duration-200">
                    <!-- Close -->
                    <button @click="showAddModal = false" class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-cream text-text-muted hover:text-text-primary hover:bg-border transition-colors flex items-center justify-center">
                        <X class="w-4 h-4" />
                    </button>
                    
                    <!-- Icon & Title -->
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="w-12 h-12 rounded-full bg-teal-light flex items-center justify-center text-teal mb-4">
                            <Plus class="w-6 h-6" />
                        </div>
                        <h2 class="text-[20px] font-bold text-text-primary">Tambah Pengguna Baru</h2>
                        <p class="text-[13px] text-text-muted mt-1">Buat akun login untuk petugas Puskesmas</p>
                    </div>
                    
                    <!-- Form -->
                    <form @submit.prevent="submitAdd" class="flex flex-col gap-4">
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Nama Lengkap</label>
                            <input v-model="addForm.name" type="text" placeholder="Siti Rahmadhani"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                            <p v-if="addForm.errors.name" class="text-[12px] text-coral-dark mt-1">{{ addForm.errors.name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Email</label>
                            <input v-model="addForm.email" type="email" placeholder="petugas@puskesmas.go.id"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                            <p v-if="addForm.errors.email" class="text-[12px] text-coral-dark mt-1">{{ addForm.errors.email }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Password</label>
                            <div class="relative">
                                <input v-model="addForm.password" :type="showPw ? 'text' : 'password'" placeholder="Min. 8 karakter, huruf kapital, angka"
                                    class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 pr-10 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                                <button type="button" @click="showPw = !showPw" class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted">
                                    <EyeOff v-if="!showPw" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <!-- Strength Bar -->
                            <div v-if="addForm.password" class="mt-1.5">
                                <div class="h-1.5 bg-cream rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                        :class="pwStrength.cls"
                                        :style="{ width: (pwStrength.score / 4 * 100) + '%' }"></div>
                                </div>
                                <p class="text-[11px] mt-1" :class="pwStrength.score >= 3 ? 'text-success' : 'text-warning'">
                                    {{ pwStrength.label }}
                                </p>
                            </div>
                            <p v-if="addForm.errors.password" class="text-[12px] text-coral-dark mt-1">{{ addForm.errors.password }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Konfirmasi Password</label>
                            <input v-model="addForm.password_confirmation" :type="showPw ? 'text' : 'password'" placeholder="Ulangi password"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Role</label>
                            <select v-model="addForm.role"
                                class="w-full bg-white border border-border rounded-lg pl-3 pr-8 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none">
                                <option value="petugas">Petugas (Input & Export)</option>
                                <option value="admin">Admin (Akses Penuh)</option>
                            </select>
                            <p v-if="addForm.errors.role" class="text-[12px] text-coral-dark mt-1">{{ addForm.errors.role }}</p>
                        </div>
                        
                        <div class="flex gap-3 mt-2">
                            <button type="button" @click="showAddModal = false"
                                class="flex-1 py-2.5 border-[1.5px] border-border text-text-muted rounded-xl text-[14px] hover:bg-cream transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="addForm.processing"
                                class="flex-1 py-2.5 bg-teal text-white rounded-xl text-[14px] font-semibold shadow-[0_4px_12px_rgba(68,161,148,0.25)] hover:bg-teal-dark transition-all disabled:opacity-50">
                                {{ addForm.processing ? 'Menyimpan...' : 'Buat Akun' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════ EDIT USER MODAL ═══════════════ -->
        <Teleport to="body">
            <div v-if="showEditModal && editTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEditModal = false"></div>
                
                <div class="relative bg-white rounded-2xl w-[460px] p-8 shadow-2xl z-10">
                    <button @click="showEditModal = false" class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-cream text-text-muted hover:text-text-primary flex items-center justify-center">
                        <X class="w-4 h-4" />
                    </button>
                    
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="w-12 h-12 rounded-full bg-steel-light flex items-center justify-center text-steel mb-4">
                            <Edit2 class="w-6 h-6" />
                        </div>
                        <h2 class="text-[20px] font-bold text-text-primary">Edit Pengguna</h2>
                        <p class="text-[13px] text-text-muted mt-1">Perbarui data akun {{ editTarget.name }}</p>
                    </div>
                    
                    <form @submit.prevent="submitEdit" class="flex flex-col gap-4">
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Nama Lengkap</label>
                            <input v-model="editForm.name" type="text"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                            <p v-if="editForm.errors.name" class="text-[12px] text-coral-dark mt-1">{{ editForm.errors.name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Email</label>
                            <input v-model="editForm.email" type="email"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                            <p v-if="editForm.errors.email" class="text-[12px] text-coral-dark mt-1">{{ editForm.errors.email }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">
                                Password Baru <span class="text-text-muted font-normal">(kosongkan jika tidak ingin diubah)</span>
                            </label>
                            <input v-model="editForm.password" type="password" placeholder="Biarkan kosong jika tidak berubah"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                        </div>
                        
                        <div v-if="editForm.password">
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Konfirmasi Password Baru</label>
                            <input v-model="editForm.password_confirmation" type="password"
                                class="w-full bg-white border border-border rounded-lg px-3.5 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none" />
                        </div>
                        
                        <div>
                            <label class="block text-[13px] font-medium text-text-primary mb-1.5">Role</label>
                            <select v-model="editForm.role"
                                class="w-full bg-white border border-border rounded-lg pl-3 pr-8 py-2.5 text-[14px] focus:border-teal focus:ring-2 focus:ring-teal/20 focus:outline-none">
                                <option value="petugas">Petugas (Input & Export)</option>
                                <option value="admin">Admin (Akses Penuh)</option>
                            </select>
                        </div>
                        
                        <div class="flex gap-3 mt-2">
                            <button type="button" @click="showEditModal = false"
                                class="flex-1 py-2.5 border-[1.5px] border-border text-text-muted rounded-xl text-[14px] hover:bg-cream transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="editForm.processing"
                                class="flex-1 py-2.5 bg-steel text-white rounded-xl text-[14px] font-semibold hover:bg-steel-dark transition-all disabled:opacity-50">
                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
        <!-- Delete Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            variant="danger"
            title="Hapus Pengguna?"
            :message="`Tindakan ini tidak dapat dibatalkan. Akun <b>${deleteTarget?.name}</b> akan dihapus secara permanen dari sistem.`"
            confirmText="Hapus Permanen"
            cancelText="Batal"
            @confirm="confirmDelete"
            @close="showDeleteModal = false"
        />

        <!-- Reset Password Modal -->
        <ConfirmationModal
            :show="showResetModal"
            variant="standard"
            title="Reset Password?"
            :message="`Apakah Anda yakin ingin mereset password untuk akun <b>${resetTarget?.name}</b> menjadi <b>password</b>?`"
            confirmText="Ya, Reset Password"
            cancelText="Batal"
            @confirm="confirmReset"
            @close="showResetModal = false"
        />

    </AppLayout>
</template>
