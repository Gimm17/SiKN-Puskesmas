<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Mail, Lock, Eye, EyeOff, Activity, Building, Phone } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen w-full flex flex-col lg:flex-row bg-cream font-sans">
        
        <!-- LEFT COLUMN (55%) -->
        <div class="hidden lg:flex lg:w-[55%] relative flex-col justify-center bg-gradient-to-b from-teal to-teal-dark overflow-hidden p-12">
            <!-- Background Decoration (Blob) -->
            <div class="absolute w-[600px] h-[600px] bg-[#6DBDB3] rounded-full mix-blend-multiply filter blur-[80px] opacity-40 top-[-100px] left-[-100px] animate-pulse"></div>
            
            <div class="relative z-10 w-full max-w-xl mx-auto flex flex-col h-full justify-between py-12">
                <!-- Top Logo -->
                <div class="flex flex-col items-start gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-teal shadow-lg">
                            <Activity class="w-6 h-6" stroke-width="2.5" />
                        </div>
                        <h1 class="text-white text-[32px] font-bold tracking-tight">SiKN</h1>
                    </div>
                    <p class="text-white/80 text-sm tracking-wide">Sistem Informasi Kunjungan Neonatal</p>
                </div>
                
                <!-- Center Graphic Area -->
                <div class="flex-1 flex items-center justify-center py-12">
                    <!-- Placeholder for illustration -->
                    <div class="w-full h-[400px] bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center backdrop-blur-sm relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent"></div>
                        <Activity class="w-32 h-32 text-white/20" stroke-width="1" />
                    </div>
                </div>
                
                <!-- Bottom Quote -->
                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-6 border border-white/20 shadow-xl">
                    <p class="text-white/90 text-sm italic leading-relaxed">
                        "Rekap cerdas untuk layanan neonatal yang lebih baik."
                    </p>
                    <p class="text-white/60 text-xs mt-3 font-medium">— Puskesmas Mapane</p>
                </div>
            </div>
        </div>
        
        <!-- RIGHT COLUMN (45%) -->
        <div class="w-full lg:w-[45%] flex flex-col justify-center items-center p-6 sm:p-12 min-h-screen relative z-10">
            <div class="w-full max-w-[380px] mx-auto flex flex-col">
                
                <!-- Mobile Logo (hidden on desktop) -->
                <div class="flex lg:hidden items-center gap-2 mb-10">
                    <div class="w-8 h-8 bg-teal text-white rounded-lg flex items-center justify-center">
                        <Activity class="w-5 h-5" stroke-width="2.5" />
                    </div>
                    <span class="text-teal font-semibold text-lg">SiKN</span>
                </div>
                
                <!-- Headings -->
                <div class="mb-8">
                    <h2 class="text-[28px] font-bold text-text-primary tracking-tight">Selamat Datang 👋</h2>
                    <p class="text-[14px] text-text-muted mt-2">Masuk ke akun Anda untuk melanjutkan</p>
                </div>
                
                <div v-if="status" class="mb-4 text-[13px] font-medium text-success">
                    {{ status }}
                </div>
                
                <!-- FORM -->
                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    
                    <!-- Email Field -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="email" class="text-[13px] font-medium text-text-primary">Email</Label>
                        <div class="relative">
                            <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" stroke-width="2" />
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="petugas@puskesmas.go.id"
                                required
                                autofocus
                                autocomplete="username"
                                class="pl-10 h-[42px] bg-white border-border text-[15px] focus-visible:ring-teal/30 focus-visible:border-teal rounded-lg transition-shadow"
                                :class="{ 'border-coral-dark focus-visible:ring-coral-dark/30 focus-visible:border-coral-dark': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-[13px] text-coral-dark mt-1">{{ form.errors.email }}</p>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="flex flex-col gap-1.5 mt-2">
                        <Label for="password" class="text-[13px] font-medium text-text-primary">Password</Label>
                        <div class="relative">
                            <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" stroke-width="2" />
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                class="pl-10 pr-10 h-[42px] bg-white border-border text-[15px] focus-visible:ring-teal/30 focus-visible:border-teal rounded-lg transition-shadow"
                                :class="{ 'border-coral-dark focus-visible:ring-coral-dark/30 focus-visible:border-coral-dark': form.errors.password }"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors focus:outline-none">
                                <EyeOff v-if="!showPassword" class="w-4 h-4" stroke-width="2" />
                                <Eye v-else class="w-4 h-4" stroke-width="2" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-[13px] text-coral-dark mt-1">{{ form.errors.password }}</p>
                    </div>
                    
                    <!-- Forgot Password Link -->
                    <div class="flex justify-end mt-1">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-[13px] font-medium text-teal hover:underline focus:outline-none focus:underline"
                        >
                            Lupa password?
                        </Link>
                    </div>
                    
                    <!-- Submit Button -->
                    <Button 
                        type="submit" 
                        class="w-full h-[46px] mt-4 bg-teal hover:bg-teal-dark text-white text-[14px] font-semibold tracking-wide rounded-lg shadow-[0_4px_12px_rgba(68,161,148,0.3)] transition-all active:scale-[0.98]"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="mr-2">...</span>
                        Masuk ke Sistem
                    </Button>
                </form>
                
                <!-- Divider -->
                <div class="flex items-center my-8">
                    <div class="flex-1 h-px bg-border"></div>
                    <span class="px-4 text-[13px] text-text-muted font-medium bg-cream">ataupun</span>
                    <div class="flex-1 h-px bg-border"></div>
                </div>
                
                <!-- Bottom Info Card -->
                <div class="bg-white border border-border rounded-[10px] p-4 flex flex-col gap-2.5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <Building class="w-4 h-4 text-teal" />
                        <span class="text-[13px] text-text-muted">Puskesmas Mapane, Kabupaten Poso</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <Phone class="w-4 h-4 text-text-muted" />
                        <span class="text-[13px] text-text-muted">Hubungi admin untuk akun baru</span>
                    </div>
                </div>
                
                <!-- Footer Text -->
                <p class="text-center text-[12px] text-text-muted mt-8 font-medium">
                    &copy; 2026 SiKN &middot; Gimora Digital
                </p>
                
            </div>
        </div>
        
    </div>
</template>

<style>
/* Reset basic styles to match design system body colors */
body {
    background-color: #F4F0E4;
}
</style>
