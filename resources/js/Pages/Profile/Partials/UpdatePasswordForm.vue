<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <div class="mb-6">
            <h3 class="text-[16px] font-semibold text-text-primary mb-1">Perbarui Kata Sandi</h3>
            <p class="text-text-muted text-sm">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak untuk menjaga keamanan.</p>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-5">
            <div>
                <label for="current_password" class="block text-[13px] font-medium tracking-[0.04em] text-text-primary mb-2">KATA SANDI SAAT INI</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="w-full bg-white border border-standard rounded-lg px-3 py-2 text-text-primary focus:ring-3 focus:ring-teal/20 focus:border-teal outline-none transition-all"
                    autocomplete="current-password"
                />
                <p v-if="form.errors.current_password" class="mt-2 text-sm text-coral-dark">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label for="password" class="block text-[13px] font-medium tracking-[0.04em] text-text-primary mb-2">KATA SANDI BARU</label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="w-full bg-white border border-standard rounded-lg px-3 py-2 text-text-primary focus:ring-3 focus:ring-teal/20 focus:border-teal outline-none transition-all"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-coral-dark">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-[13px] font-medium tracking-[0.04em] text-text-primary mb-2">KONFIRMASI KATA SANDI</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full bg-white border border-standard rounded-lg px-3 py-2 text-text-primary focus:ring-3 focus:ring-teal/20 focus:border-teal outline-none transition-all"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-coral-dark">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="pt-2 flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-teal text-white text-[14px] font-semibold tracking-[0.01em] px-6 py-2.5 rounded-lg hover:bg-teal-dark transition-colors shadow-[0_4px_12px_rgba(68,161,148,0.3)] disabled:opacity-50"
                >
                    SIMPAN
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-text-muted">Disimpan.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
