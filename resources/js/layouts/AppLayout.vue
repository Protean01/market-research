<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { watch } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import { useAppearance } from '@/composables/useAppearance';
import AppMobileLayout from '@/layouts/app/AppMobileLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { useSettingsStore } from '@/stores/settingsStore';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAdmin = computed(() => !!page.props.auth?.is_admin);
const CurrentLayout = computed(() => isAdmin.value ? AppSidebarLayout : AppMobileLayout);

// const surveyStore = useSurveyStore();
const settingsStore = useSettingsStore();
const { appearance } = useAppearance();

watch(() => settingsStore.dataSaver, (active) => {
    if (active) {
        document.body.classList.add('data-saver');
    } else {
        document.body.classList.remove('data-saver');
    }
}, { immediate: true });

// Watch flash messages and show as toasts
watch(() => page.props?.flash, (flash: any) => {
    if (flash?.success) {
        toast.success(flash.success);
    }

    if (flash?.error) {
        toast.error(flash.error);
    }
}, { deep: true, immediate: true });
</script>

<template>
    <Toaster :theme="appearance === 'dark' ? 'dark' : 'light'" position="top-right" close-button rich-colors />
    <component :is="CurrentLayout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
