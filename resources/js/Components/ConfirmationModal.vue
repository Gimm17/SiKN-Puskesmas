<script setup>
import { computed } from 'vue';
import { AlertTriangle, CheckCircle, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    variant: { type: String, default: 'standard' }, // standard, success, danger
    title: { type: String, required: true },
    message: { type: String, required: true },
    confirmText: { type: String, default: 'Ya, Lanjutkan' },
    cancelText: { type: String, default: 'Batal' }
});

const emit = defineEmits(['close', 'confirm']);

const close = () => emit('close');
const confirm = () => emit('confirm');

// Variant Configurations
const config = computed(() => {
    switch (props.variant) {
        case 'success':
            return {
                overlay: 'bg-teal-dark/20 backdrop-blur-md',
                modal: 'bg-white rounded-xl shadow-xl max-w-[480px] w-full p-8 md:p-12 text-center flex flex-col items-center',
                iconContainer: 'w-20 h-20 rounded-full bg-teal-light flex items-center justify-center mb-6',
                iconComponent: CheckCircle,
                iconClass: 'text-teal-dark w-10 h-10',
                titleClass: 'font-headline-md text-headline-md text-text-primary mb-3',
                messageClass: 'font-body-md text-body-md text-text-muted mb-10',
                buttonsContainer: 'flex items-center gap-4 w-full justify-center',
                btnCancel: 'font-button text-button text-on-surface-variant hover:bg-surface-variant px-8 py-3 rounded-lg transition-all',
                btnConfirm: 'font-button text-button bg-primary text-white hover:bg-teal-deeper px-8 py-3 rounded-lg shadow-[0_4px_12px_rgba(0,106,96,0.3)] transition-all flex items-center gap-2'
            };
        case 'danger':
            return {
                overlay: 'bg-on-surface/40 backdrop-blur-lg',
                modal: 'bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border-t-[3px] border-[#EC8F8D] flex flex-col',
                modalInner: 'p-6 flex flex-col items-center text-center',
                iconContainer: 'w-16 h-16 rounded-full bg-[#FAEAEA] flex items-center justify-center mb-4',
                iconComponent: Trash2,
                iconClass: 'text-[#EC8F8D] w-8 h-8',
                titleClass: 'font-headline-md text-headline-md text-text-primary mb-2',
                messageClass: 'font-body-md text-body-md text-text-muted',
                buttonsContainer: 'flex gap-3 w-full px-6 pb-6 mt-8',
                btnCancel: 'flex-1 py-3 px-4 rounded-lg border border-border-standard text-on-surface-variant font-button text-button hover:bg-surface-variant transition-colors',
                btnConfirm: 'flex-1 py-3 px-4 rounded-lg bg-[#EC8F8D] text-white font-button text-button hover:opacity-90 transition-opacity shadow-md'
            };
        case 'standard':
        default:
            return {
                overlay: 'bg-black/40 backdrop-blur-sm',
                modal: 'bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col',
                modalInner: 'p-6 text-center',
                iconContainer: 'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-warning-light',
                iconComponent: AlertTriangle,
                iconClass: 'text-warning w-7 h-7',
                titleClass: 'text-headline-sm font-headline-sm text-text-primary mb-2',
                messageClass: 'text-body-md font-body-md text-text-muted',
                buttonsContainer: 'bg-surface-container-low px-6 py-4 flex gap-3 justify-center',
                btnCancel: 'flex-1 font-button text-button text-on-surface-variant hover:bg-surface-variant border border-border-standard rounded-lg py-2.5 transition-all',
                btnConfirm: 'flex-1 font-button text-button bg-primary-container text-white hover:bg-teal-dark rounded-lg py-2.5 shadow-[0_4px_12px_rgba(68,161,148,0.2)] transition-all'
            };
    }
});
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-show="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" :class="config.overlay">
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-show="show" :class="config.modal">
                    <div :class="config.modalInner || ''">
                        <!-- Icon -->
                        <div :class="config.iconContainer">
                            <component :is="config.iconComponent" :class="config.iconClass" stroke-width="2" />
                        </div>
                        
                        <!-- Content -->
                        <h3 :class="config.titleClass">{{ title }}</h3>
                        <p :class="config.messageClass" v-html="message"></p>
                    </div>

                    <!-- Buttons -->
                    <div :class="config.buttonsContainer">
                        <button type="button" @click="close" :class="config.btnCancel">
                            {{ cancelText }}
                        </button>
                        <button type="button" @click="confirm" :class="config.btnConfirm">
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </transition>
</template>
