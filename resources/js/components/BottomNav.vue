<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Gift, ClipboardList, User, Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import { useHaptics } from '@/composables/useHaptics';

const page = usePage();
const currentUrl = computed(() => page.url);
const { lightClick } = useHaptics();

const items = [
    {
        title: 'Home',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Surveys',
        href: '/surveys',
        icon: ClipboardList,
    },
    {
        title: 'Rewards',
        href: '/wallet',
        icon: Gift,
    },
    {
        title: 'Profile',
        href: '/profile',
        icon: User,
    },
    {
        title: 'Alerts',
        href: '/notifications',
        icon: Bell,
    },
];

const isActive = (href: string) => {
    if (href === '/dashboard') {
return currentUrl.value === '/dashboard';
}

    return currentUrl.value === href || currentUrl.value.startsWith(href + '/');
};
</script>

<template>
    <nav class="fixed bottom-0 left-0 z-50 w-full bg-white/90 backdrop-blur-lg border-t border-gray-100 dark:bg-zinc-900/90 dark:border-zinc-800 pb-safe shadow-[0_-4px_20px_rgba(0,0,0,0.03)]">
        <div class="grid h-20 grid-cols-5 mx-auto max-w-lg px-2">
            <Link
                v-for="item in items"
                :key="item.title"
                :href="item.href"
                @click="lightClick()"
                class="relative flex flex-col items-center justify-center transition-all duration-300"
                :class="[isActive(item.href) ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600']"
            >
                <!-- Active Background Pill -->
                <div 
                    v-if="isActive(item.href)"
                    class="absolute inset-x-2 inset-y-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-2xl -z-10 animate-in fade-in zoom-in-95 duration-300"
                ></div>

                <div class="relative">
                    <component 
                        :is="item.icon" 
                        class="w-6 h-6 mb-1 transition-all duration-300" 
                        :class="[isActive(item.href) ? 'scale-110' : 'group-active:scale-90']" 
                        :stroke-width="isActive(item.href) ? 2.5 : 2"
                    />
                    <!-- Unread Badge Placeholder (could be dynamic) -->
                    <div v-if="item.title === 'Alerts'" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white dark:border-zinc-900"></div>
                </div>
                
                <span 
                    class="text-[10px] font-bold tracking-tight transition-all duration-300"
                    :class="[isActive(item.href) ? 'opacity-100 translate-y-0' : 'opacity-70']"
                >
                    {{ item.title }}
                </span>
            </Link>
        </div>
    </nav>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
