<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profil Saya" />

    <AppLayout>
        <!-- Top Navbar injected via AppLayout, we just need to provide the content -->
        <template #header>
            Profil Saya
        </template>
        <template #breadcrumb>
            <nav class="text-sm text-text-muted flex gap-2">
                <a href="/dashboard" class="hover:text-primary transition-colors">Beranda</a>
                <span>/</span>
                <span class="text-text-primary">Profil</span>
            </nav>
        </template>

        <div class="flex justify-center">
            <div class="w-full max-w-[800px] flex flex-col gap-6">
                <!-- Profile Information Card -->
                <div class="bg-white border border-standard rounded-xl p-6 shadow-sm">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- Update Password Card -->
                <div class="bg-white border border-standard rounded-xl p-6 shadow-sm">
                    <UpdatePasswordForm />
                </div>

                <!-- Delete Account Card -->
                <div class="bg-white border border-standard rounded-xl p-6 shadow-sm">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
