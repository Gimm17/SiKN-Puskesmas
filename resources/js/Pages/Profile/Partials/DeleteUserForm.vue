<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <div class="mb-6">
            <h3 class="text-[16px] font-semibold text-coral-dark mb-1">Hapus Akun</h3>
            <p class="text-text-muted text-sm">Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.</p>
        </div>

        <div class="pt-2">
            <button
                @click="confirmUserDeletion"
                class="bg-coral-light border border-coral-dark text-coral-dark text-[14px] font-semibold tracking-[0.01em] px-6 py-2.5 rounded-lg hover:bg-coral-dark hover:text-white transition-colors"
            >
                HAPUS AKUN
            </button>
        </div>

        <!-- Custom Modal for Delete Confirmation -->
        <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
            
            <!-- Modal Content -->
            <div class="bg-white rounded-2xl w-full max-w-lg mx-auto p-8 shadow-2xl relative z-10">
                <button @click="closeModal" class="absolute top-4 right-4 text-text-muted hover:bg-surface-alt p-1.5 rounded-full transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>

                <h2 class="text-xl font-bold text-text-primary mb-3">Apakah Anda yakin ingin menghapus akun?</h2>
                <p class="text-[14px] text-text-muted mb-6">
                    Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun.
                </p>

                <div class="mb-6">
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Kata Sandi"
                        class="w-full bg-white border border-standard rounded-lg px-3 py-2 text-text-primary focus:ring-3 focus:ring-coral-light focus:border-coral-dark outline-none transition-all"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-sm text-coral-dark">{{ form.errors.password }}</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        @click="closeModal"
                        class="px-6 py-2.5 rounded-lg text-[14px] font-semibold text-text-muted border-1.5 border-standard hover:bg-surface-alt transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        @click="deleteUser"
                        :disabled="form.processing"
                        class="bg-coral-light border border-coral-dark text-coral-dark text-[14px] font-semibold tracking-[0.01em] px-6 py-2.5 rounded-lg hover:bg-coral-dark hover:text-white transition-colors disabled:opacity-50"
                    >
                        Hapus Akun
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
