<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { useOnline } from '@vueuse/core';
import { Bell, Search, X, Moon, Sun } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useAppearance } from '@/composables/useAppearance';
import { subscribeToPush } from '@/composables/usePush';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { appearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
};

const title = computed(() => {
    if (props.breadcrumbs.length > 0) {
        return props.breadcrumbs[props.breadcrumbs.length - 1].title;
    }

    return 'Market Research';
});

const isSearching = ref(false);
const searchQuery = ref('');
const isSubscribing = ref(false);
const isOnline = useOnline();

const page = usePage();

// Keep local search query in sync with the URL
watch(() => page.url, () => {
    const params = new URLSearchParams(window.location.search);
    searchQuery.value = params.get('q') || '';
}, { immediate: true });

function toggleSearch() {
    isSearching.value = !isSearching.value;

    if (!isSearching.value) {
        searchQuery.value = '';
        executeSearch();
    }
}

function executeSearch() {
    router.get('/surveys', { q: searchQuery.value }, { preserveState: true, replace: true, preserveScroll: true });
}

async function handleSubscribe() {
    if (isSubscribing.value) {
return;
}
    
    isSubscribing.value = true;

    try {
        await subscribeToPush();
        // You could add a success toast here if you implement a toast system
    } catch (e) {
        console.error('Failed to subscribe to push notifications', e);
    } finally {
        isSubscribing.value = false;
    }
}
</script>

<template>
    <header class="sticky top-0 z-40 w-full border-b bg-white/80 backdrop-blur-md dark:bg-zinc-900/80 dark:border-zinc-800 pt-safe">
        <div class="flex h-16 items-center justify-between px-4">
            <template v-if="!isSearching">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard" class="flex items-center">
                        <AppLogoIcon class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                    </Link>
                    <div class="flex flex-col -space-y-1">
                        <h1 class="text-base font-black tracking-tight text-foreground truncate max-w-[120px] uppercase">
                            {{ title }}
                        </h1>
                        <div class="flex items-center gap-1.5">
                            <div :class="[isOnline ? 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.5)]' : 'bg-zinc-400']" class="h-1.5 w-1.5 rounded-full transition-all duration-500"></div>
                            <span class="text-[7px] font-black text-muted-foreground uppercase tracking-widest whitespace-nowrap">{{ isOnline ? 'Data Optimized' : 'Offline Mode' }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="toggleTheme" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors">
                        <Sun v-if="appearance === 'dark'" class="w-5 h-5" />
                        <Moon v-else class="w-5 h-5" />
                    </button>
                    <button @click="toggleSearch" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors">
                        <Search class="w-5 h-5" />
                    </button>
                    <button @click="handleSubscribe" :disabled="isSubscribing" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors relative disabled:opacity-50">
                        <Bell class="w-5 h-5" />
                        <span class="absolute top-2 right-2 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                    </button>
                </div>
            </template>
            <template v-else>
                <div class="flex items-center w-full gap-2 transition-all animate-in fade-in slide-in-from-right-4 duration-200">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="executeSearch"
                            type="text" 
                            placeholder="Search surveys..." 
                            class="w-full h-10 pl-9 pr-4 rounded-full bg-gray-100 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm dark:bg-zinc-800 dark:text-gray-100 dark:focus:bg-zinc-900 transition-all outline-none"
                            autofocus
                        />
                    </div>
                    <button @click="toggleSearch" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors flex-shrink-0">
                        <X class="w-5 h-5" />
                    </button>
                </div>
            </template>
        </div>
    </header>
</template>
