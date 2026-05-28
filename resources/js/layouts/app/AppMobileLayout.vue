<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import BottomNav from '@/components/BottomNav.vue';
import InstallPrompt from '@/components/InstallPrompt.vue';
import MobileHeader from '@/components/MobileHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const pageKey = computed(() => page.url);
</script>

<template>
    <AppShell variant="header" class="bg-muted/10">
        <MobileHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header" class="pb-24 overflow-x-hidden">
            <transition 
                name="page-slide" 
                mode="out-in" 
                appear
            >
                <div :key="pageKey" class="h-full">
                    <slot />
                </div>
            </transition>
        </AppContent>
        <BottomNav />
        <InstallPrompt />
    </AppShell>
</template>

<style>
.page-slide-enter-active,
.page-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.page-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}
</style>
