<script setup>
import { computed, watch, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertTriangle, X } from 'lucide-vue-next';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const isVisible = ref(false);
const message = ref('');
const type = ref('success'); // success or error
let timeout;

// Handle initial load flash message
onMounted(() => {
    if (flash.value.success) {
        showToast(flash.value.success, 'success');
    } else if (flash.value.error) {
        showToast(flash.value.error, 'error');
    }
});

// Watch for inertia navigation updates
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.success) {
        showToast(newFlash.success, 'success');
    } else if (newFlash?.error) {
        showToast(newFlash.error, 'error');
    }
}, { deep: true });

function showToast(msg, t) {
    message.value = msg;
    type.value = t;
    isVisible.value = true;
    
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        close();
    }, 5000);
}

function close() {
    isVisible.value = false;
}
</script>

<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-400 ease-out"
            enter-from-class="transform -translate-y-8 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform -translate-y-8 opacity-0"
        >
            <div v-if="isVisible" class="fixed top-6 left-1/2 -translate-x-1/2 z-[9999] flex items-center shadow-[0_8px_30px_rgb(0,0,0,0.12)] rounded-xl border overflow-hidden min-w-[320px] max-w-[500px]"
                :class="type === 'success' ? 'bg-success-light border-success/30' : 'bg-coral-light border-coral/30'">
                
                <div class="px-4 py-3.5 flex items-center justify-center" :class="type === 'success' ? 'text-success' : 'text-coral-dark'">
                    <CheckCircle2 v-if="type === 'success'" class="w-5 h-5" stroke-width="2.5" />
                    <AlertTriangle v-else class="w-5 h-5" stroke-width="2.5" />
                </div>
                
                <div class="py-3.5 pr-2 flex-1">
                    <p class="text-[14px] font-semibold leading-tight" :class="type === 'success' ? 'text-success' : 'text-coral-dark'">
                        {{ message }}
                    </p>
                </div>
                
                <button @click="close" class="p-3.5 opacity-60 hover:opacity-100 transition-opacity" :class="type === 'success' ? 'text-success' : 'text-coral-dark'">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </transition>
    </Teleport>
</template>
