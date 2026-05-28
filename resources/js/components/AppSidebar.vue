<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, LayoutGrid, ClipboardList, Target, LineChart, Download, Users, ShieldAlert, Settings, PieChart } from 'lucide-vue-next';
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { NavItem } from '@/types';

const { appearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
};

const navGroups = computed(() => [
    {
        label: 'Dashboard',
        items: [
            {
                title: 'Admin Dashboard',
                href: route('admin.dashboard', undefined as any),
                icon: LayoutGrid,
            },
        ],
    },
    {
        label: 'Research & Surveys',
        items: [
            {
                title: 'Survey Builder',
                href: route('admin.surveys', undefined as any),
                icon: ClipboardList,
            },
            {
                title: 'Question Library',
                href: route('admin.question-bank.index', undefined as any),
                icon: BookOpen,
            },
        ],
    },
    {
        label: 'Audience & Analytics',
        items: [
            {
                title: 'Audience Insights',
                href: route('admin.audience', undefined as any),
                icon: PieChart,
            },
            {
                title: 'Targeting',
                href: route('admin.targeting', undefined as any),
                icon: Target,
            },
            {
                title: 'Monitoring',
                href: route('admin.monitoring', undefined as any),
                icon: LineChart,
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                title: 'User Management',
                href: route('admin.users', undefined as any),
                icon: Users,
            },
            {
                title: 'Quality Control',
                href: route('admin.quality.index', undefined as any),
                icon: ShieldAlert,
            },
            {
                title: 'Data Export',
                href: route('admin.export', undefined as any),
                icon: Download,
            },
            {
                title: 'Settings',
                href: route('admin.settings', undefined as any),
                icon: Settings,
            },
        ],
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: 'View User UI',
        href: route('surveys', []),
        icon: BookOpen,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('admin.dashboard', [])">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <div class="px-2 py-2">
                <SidebarMenuButton @click="toggleTheme" class="w-full justify-start gap-2 text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100">
                    <Sun v-if="appearance === 'dark'" class="h-4 w-4" />
                    <Moon v-else class="h-4 w-4" />
                    <span>{{ appearance === 'dark' ? 'Light Mode' : 'Dark Mode' }}</span>
                </SidebarMenuButton>
            </div>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
