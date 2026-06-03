<script setup>
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';
import Toast from '../Toast.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    breadcrumb: String
});
</script>

<template>
    <div class="min-h-screen bg-cream font-sans flex text-text-primary">
        <Head :title="title" />
        <Toast />
        
        <!-- Sidebar -->
        <Sidebar />
        
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col pl-[240px]">
            <!-- Navbar -->
            <Navbar :title="title" :breadcrumb="breadcrumb" />
            
            <!-- Page Content -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
/* Global background */
body {
    background-color: #F4F0E4;
}
</style>
