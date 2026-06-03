<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { Activity, LayoutDashboard, Database, Download, Upload, User, Users, LogOut, FileInput, FileOutput, CalendarDays, HardDriveDownload } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmationModal from '../ConfirmationModal.vue';

const page = usePage();
const showLogout = ref(false);

function performLogout() {
    router.post(route('logout'));
}

const navItems = [
    { label: 'Dashboard',    icon: LayoutDashboard, route: 'dashboard',    match: '/dashboard' },
    { label: 'Data Rekap',  icon: Database,         route: 'rekap.index',  match: '/rekap' },
    { label: 'Preview Tahunan', icon: CalendarDays, route: 'rekap.yearly', match: '/rekap/yearly' },
    { label: 'Import Excel', icon: FileInput,        route: 'rekap.import', match: '/rekap/import' },
];

function isActive(match) {
    // '/rekap' should NOT match '/rekap/import'
    if (match === '/rekap') return page.url === '/rekap' || (page.url.startsWith('/rekap') && !page.url.startsWith('/rekap/'));
    return page.url.startsWith(match);
}
</script>

<template>
    <aside class="fixed left-0 top-0 h-full w-[240px] bg-gradient-to-b from-teal to-teal-deeper flex flex-col shadow-xl z-20 overflow-y-auto">
        <!-- Top Logo -->
        <div class="p-6 pb-4">
            <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                <div class="w-9 h-9 bg-white rounded-xl flex items-center justify-center text-teal-dark shadow-sm group-hover:scale-105 transition-transform">
                    <Activity class="w-5 h-5" stroke-width="2.5" />
                </div>
                <h1 class="text-white text-[20px] font-bold tracking-tight">SiKN</h1>
            </Link>
            <p class="text-white/50 text-[11px] mt-1.5 ml-0.5 font-medium tracking-wide">Puskesmas Mapane</p>
        </div>

        <!-- Nav Section -->
        <nav class="mt-4 flex-1 flex flex-col gap-0.5 px-2">
            <p class="text-[10px] uppercase tracking-[0.1em] text-white/40 px-2 mb-2 font-bold mt-2">Menu Utama</p>
            
            <template v-for="item in navItems" :key="item.label">
                <Link 
                    :href="route(item.route)"
                    class="h-[44px] px-3 rounded-lg flex items-center gap-3 transition-all duration-150 group"
                    :class="isActive(item.match)
                        ? 'bg-teal-deeper text-white border-l-[3px] border-white shadow-sm'
                        : 'text-white/80 hover:bg-white/10 hover:text-white border-l-[3px] border-transparent'"
                >
                    <component :is="item.icon" class="w-[18px] h-[18px] flex-shrink-0 transition-transform group-hover:scale-110" />
                    <span class="text-[14px] font-medium">{{ item.label }}</span>
                </Link>
            </template>
            
            <div class="my-3 border-t border-white/10 mx-2"></div>
            
            <p class="text-[10px] uppercase tracking-[0.1em] text-white/40 px-2 mb-2 font-bold">Sistem</p>
            
            <Link :href="route('profile.edit')"
                class="h-[44px] px-3 rounded-lg flex items-center gap-3 transition-all duration-150 group"
                :class="isActive('/profile')
                    ? 'bg-teal-deeper text-white border-l-[3px] border-white shadow-sm'
                    : 'text-white/80 hover:bg-white/10 hover:text-white border-l-[3px] border-transparent'">
                <User class="w-[18px] h-[18px] flex-shrink-0 transition-transform group-hover:scale-110" />
                <span class="text-[14px] font-medium">Profil Saya</span>
            </Link>
            
            <template v-if="$page.props.auth.user.role === 'admin'">
                <Link :href="route('users.index')"
                    class="h-[44px] px-3 rounded-lg flex items-center gap-3 transition-all duration-150 group"
                    :class="isActive('/users')
                        ? 'bg-teal-deeper text-white border-l-[3px] border-white shadow-sm'
                        : 'text-white/80 hover:bg-white/10 hover:text-white border-l-[3px] border-transparent'">
                    <Users class="w-[18px] h-[18px] flex-shrink-0 transition-transform group-hover:scale-110" />
                    <span class="text-[14px] font-medium">Kelola User</span>
                </Link>
                <Link :href="route('backup.index')"
                    class="h-[44px] px-3 rounded-lg flex items-center gap-3 transition-all duration-150 group"
                    :class="isActive('/backup')
                        ? 'bg-teal-deeper text-white border-l-[3px] border-white shadow-sm'
                        : 'text-white/80 hover:bg-white/10 hover:text-white border-l-[3px] border-transparent'">
                    <HardDriveDownload class="w-[18px] h-[18px] flex-shrink-0 transition-transform group-hover:scale-110" />
                    <span class="text-[14px] font-medium">Backup & Restore</span>
                </Link>
            </template>
        </nav>

        <!-- Bottom User Card -->
        <div class="p-3 mt-auto">
            <div class="flex items-center gap-2.5 p-2.5 bg-black/15 rounded-xl border border-white/5">
                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-white text-[14px] font-bold">
                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] text-white font-medium truncate leading-tight">{{ $page.props.auth.user.name }}</p>
                    <span class="text-[10px] bg-teal-light text-teal-dark px-1.5 py-0.5 rounded-full uppercase tracking-wide font-bold mt-0.5 inline-block">
                        {{ $page.props.auth.user.role }}
                    </span>
                </div>
                <button @click="showLogout = true" class="w-8 h-8 rounded-lg flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-colors flex-shrink-0" title="Keluar">
                    <LogOut class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Logout Modal -->
        <ConfirmationModal
            :show="showLogout"
            variant="standard"
            title="Keluar dari Aplikasi?"
            message="Apakah Anda yakin ingin mengakhiri sesi dan keluar dari aplikasi SiKN Puskesmas Mapane?"
            confirmText="Ya, Keluar"
            cancelText="Batal"
            @confirm="performLogout"
            @close="showLogout = false"
        />
    </aside>
</template>
