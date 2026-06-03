<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <div class="mb-6">
            <h3 class="text-[16px] font-semibold text-text-primary mb-1">Informasi Profil</h3>
            <p class="text-text-muted text-sm">Perbarui informasi profil dan alamat email akun Anda.</p>
        </div>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">
            <div>
                <label for="name" class="block text-[13px] font-medium tracking-[0.04em] text-text-primary mb-2">NAMA</label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full bg-white border border-standard rounded-lg px-3 py-2 text-text-primary focus:ring-3 focus:ring-teal/20 focus:border-teal outline-none transition-all"
                />
                <p v-if="form.errors.name" class="mt-2 text-sm text-coral-dark">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-[13px] font-medium tracking-[0.04em] text-text-primary mb-2">EMAIL</label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    class="w-full bg-white border border-standard rounded-lg px-3 py-2 text-text-primary focus:ring-3 focus:ring-teal/20 focus:border-teal outline-none transition-all"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-coral-dark">{{ form.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-text-muted">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-teal underline hover:text-teal-dark focus:outline-none"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-success">
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
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
