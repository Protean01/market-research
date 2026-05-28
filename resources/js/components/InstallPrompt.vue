<script setup lang="ts">
import { Download, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const showPrompt = ref(false);
const deferredPrompt = ref<any>(null);

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt.value = e;
        // Update UI notify the user they can add to home screen
        showPrompt.value = true;
    });

    window.addEventListener('appinstalled', () => {
        showPrompt.value = false;
        deferredPrompt.value = null;
        console.log('PWA was installed');
    });
});

const installPWA = async () => {
    if (!deferredPrompt.value) {
return;
}
    
    // Show the prompt
    deferredPrompt.value.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.value.userChoice;
    console.log(`User response to the install prompt: ${outcome}`);
    
    // We've used the prompt, and can't use it again, throw it away
    deferredPrompt.value = null;
    showPrompt.value = false;
};

const dismissPrompt = () => {
    showPrompt.value = false;
};
</script>

<template>
    <transition
        enter-active-class="transform transition ease-out duration-300"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div v-if="showPrompt" class="fixed bottom-20 left-4 right-4 z-[60] md:left-auto md:right-4 md:w-96">
            <div class="bg-indigo-600 dark:bg-indigo-700 text-white p-4 rounded-2xl shadow-2xl flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-xl">
                        <Download class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="font-bold text-sm">Install App</p>
                        <p class="text-xs text-indigo-100">Add to home screen for better experience</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button 
                        variant="secondary" 
                        size="sm" 
                        @click="installPWA"
                        class="bg-white text-indigo-600 hover:bg-indigo-50 font-bold rounded-lg h-9"
                    >
                        Install
                    </Button>
                    <button @click="dismissPrompt" class="p-1 hover:bg-white/10 rounded-full transition-colors">
                        <X class="w-5 h-5 text-white/70" />
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
