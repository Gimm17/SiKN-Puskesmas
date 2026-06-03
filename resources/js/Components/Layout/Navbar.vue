<script setup>
import { Bell, ChevronDown } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
    breadcrumb: {
        type: String,
        default: 'Beranda'
    }
});

const page = usePage();

// Current month/year formatting
const currentPeriod = computed(() => {
    const date = new Date();
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${months[date.getMonth()]} ${date.getFullYear()}`;
});
</script>

<template>
    <header class="h-[64px] bg-white border-b border-border px-6 flex items-center justify-between sticky top-0 z-10">
        <!-- Left: Title & Breadcrumb -->
        <div class="flex flex-col">
            <h3 class="text-[18px] font-semibold text-text-primary leading-tight">{{ title }}</h3>
            <p class="text-[12px] text-text-muted mt-0.5">{{ breadcrumb }}</p>
        </div>
        
        <!-- Right: Actions & User -->
        <div class="flex items-center gap-6">
            <!-- Period Badge -->
            <div class="bg-teal-light text-teal px-3 py-1.5 rounded-full text-[13px] font-medium shadow-sm border border-teal/10">
                {{ currentPeriod }}
            </div>
            
            <div class="w-px h-6 bg-border"></div>
            
            <!-- Notifications -->
            <button class="relative text-text-muted hover:text-text-primary transition-colors focus:outline-none">
                <Bell class="w-5 h-5" />
                <span class="absolute top-0 right-0 w-2 h-2 bg-coral rounded-full border border-white"></span>
            </button>
            
            <!-- User Menu -->
            <button class="flex items-center gap-2 hover:bg-surface-alt p-1 pr-2 rounded-full transition-colors focus:outline-none">
                <div class="w-9 h-9 bg-teal-light text-teal font-semibold rounded-full flex items-center justify-center">
                    {{ $page.props.auth.user.name.charAt(0) }}
                </div>
                <ChevronDown class="w-4 h-4 text-text-muted" />
            </button>
        </div>
    </header>
</template>
