<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell, CheckCheck, CheckCircle, ChevronRight, Clock, Coins, Trophy, Zap, ClipboardList, Info } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type FeedItem = {
    id: string;
    data: any;
    created_at: string;
    read_at: string | null;
};

const items = ref<FeedItem[]>([]);
const loading = ref(false);
const page = usePage();

const unreadCount = computed(() => items.value.filter(i => !i.read_at).length);

const load = async () => {
    if (!page.props.auth?.user) {
return;
}

    loading.value = true;

    try {
        const { data } = await axios.get('/notifications/feed');
        items.value = data;
    } catch (err) {
        console.warn('Failed to load notifications', err);
    } finally {
        loading.value = false;
    }
};

const markRead = async (id: string) => {
    await axios.post('/notifications/read', { id });
    items.value = items.value.map(i =>
        i.id === id ? { ...i, read_at: new Date().toISOString() } : i
    );
};

const markAllRead = async () => {
    const unread = items.value.filter(i => !i.read_at);
    await Promise.all(unread.map(i => axios.post('/notifications/read', { id: i.id })));
    items.value = items.value.map(i => ({ ...i, read_at: i.read_at ?? new Date().toISOString() }));
};

const go = async (url: string, id: string) => {
    if (!items.value.find(i => i.id === id)?.read_at) {
        await markRead(id);
    }

    window.location.href = url;
};

function getTimeAgo(dateString: string) {
    const seconds = Math.floor((new Date().getTime() - new Date(dateString).getTime()) / 1000);

    if (seconds < 60) {
return 'Just now';
}

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
return `${minutes}m ago`;
}

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
return `${hours}h ago`;
}

    return `${Math.floor(hours / 24)}d ago`;
}

// Pick icon based on notification type
function getIcon(data: any) {
    const type = data?.type ?? data?.notification_type ?? '';

    if (type.includes('prize') || type.includes('win')) {
return Trophy;
}

    if (type.includes('reward') || type.includes('earn') || type.includes('point')) {
return Coins;
}

    if (type.includes('survey')) {
return ClipboardList;
}

    if (type.includes('streak') || type.includes('zap')) {
return Zap;
}

    return Info;
}

function getIconStyle(data: any) {
    const type = data?.type ?? data?.notification_type ?? '';

    if (type.includes('prize') || type.includes('win')) {
return 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400';
}

    if (type.includes('reward') || type.includes('earn') || type.includes('point')) {
return 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400';
}

    if (type.includes('survey')) {
return 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400';
}

    if (type.includes('streak')) {
return 'bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400';
}

    return 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400';
}

onMounted(load);
</script>

<template>
    <Head title="Notifications" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-8 p-6 max-w-3xl mx-auto w-full pb-32">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="p-3 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                            <Bell class="w-6 h-6" />
                        </div>
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center shadow-sm"
                        >
                            {{ unreadCount }}
                        </span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-foreground">Notifications</h1>
                        <p class="text-sm text-muted-foreground font-medium">Rewards, surveys, and platform alerts.</p>
                    </div>
                </div>

                <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-black uppercase tracking-widest hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all border border-indigo-100 dark:border-indigo-900/30"
                >
                    <CheckCheck class="w-4 h-4" />
                    Mark all read
                </button>
            </div>

            <!-- Loading skeleton -->
            <div v-if="loading" class="space-y-3">
                <div v-for="n in 4" :key="n" class="h-20 rounded-[2rem] bg-muted/40 animate-pulse" />
            </div>

            <!-- Empty state -->
            <div
                v-else-if="items.length === 0"
                class="flex flex-col items-center justify-center py-24 text-center space-y-6"
            >
                <div class="w-20 h-20 rounded-full bg-muted flex items-center justify-center">
                    <Bell class="w-8 h-8 text-muted-foreground/30" />
                </div>
                <div class="space-y-2">
                    <h2 class="text-xl font-black text-foreground uppercase tracking-tight">All caught up!</h2>
                    <p class="text-sm text-muted-foreground max-w-xs mx-auto font-medium">
                        You have no notifications yet. Complete a survey to start earning rewards.
                    </p>
                </div>
            </div>

            <!-- Notification list -->
            <div v-else class="space-y-3">
                <!-- Unread section -->
                <template v-if="unreadCount > 0">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground px-2">
                        Unread · {{ unreadCount }}
                    </p>
                    <div
                        v-for="item in items.filter(i => !i.read_at)"
                        :key="item.id"
                        class="group flex items-start gap-4 p-5 rounded-[2rem] bg-card border border-indigo-200 dark:border-indigo-900/40 shadow-sm relative overflow-hidden hover:shadow-md transition-all"
                    >
                        <!-- Unread indicator stripe -->
                        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-[2rem] bg-indigo-500"></div>

                        <div :class="['p-3 rounded-2xl shrink-0 transition-transform group-hover:scale-110', getIconStyle(item.data)]">
                            <component :is="getIcon(item.data)" class="w-5 h-5" />
                        </div>

                        <div
                            class="flex-1 min-w-0"
                            :class="{'cursor-pointer': item.data.url}"
                            @click="item.data.url ? go(item.data.url, item.id) : null"
                        >
                            <p class="text-sm font-black text-foreground leading-tight">
                                {{ item.data.title ?? item.data.body ?? 'New Notification' }}
                            </p>
                            <p v-if="item.data.body || item.data.message" class="text-sm text-muted-foreground mt-1 font-medium leading-relaxed">
                                {{ item.data.body || item.data.message }}
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <Clock class="w-3 h-3 text-muted-foreground/60" />
                                <span class="text-[10px] font-bold text-muted-foreground">{{ getTimeAgo(item.created_at) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="item.data.url"
                                @click="go(item.data.url, item.id)"
                                class="p-2 rounded-xl bg-muted/50 text-muted-foreground hover:text-foreground hover:bg-muted transition-all"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                            <button
                                @click="markRead(item.id)"
                                class="p-2 rounded-xl bg-muted/50 text-muted-foreground hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all"
                                title="Mark as read"
                            >
                                <CheckCircle class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Read section -->
                <template v-if="items.filter(i => i.read_at).length > 0">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground px-2 mt-4">
                        Earlier
                    </p>
                    <div
                        v-for="item in items.filter(i => i.read_at)"
                        :key="item.id"
                        class="group flex items-start gap-4 p-5 rounded-[2rem] bg-card border border-border opacity-70 hover:opacity-100 hover:shadow-sm transition-all"
                    >
                        <div :class="['p-3 rounded-2xl shrink-0 opacity-60', getIconStyle(item.data)]">
                            <component :is="getIcon(item.data)" class="w-5 h-5" />
                        </div>

                        <div
                            class="flex-1 min-w-0"
                            :class="{'cursor-pointer': item.data.url}"
                            @click="item.data.url ? go(item.data.url, item.id) : null"
                        >
                            <p class="text-sm font-bold text-foreground leading-tight">
                                {{ item.data.title ?? item.data.body ?? 'Notification' }}
                            </p>
                            <p v-if="item.data.body || item.data.message" class="text-sm text-muted-foreground mt-1 font-medium">
                                {{ item.data.body || item.data.message }}
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <Clock class="w-3 h-3 text-muted-foreground/60" />
                                <span class="text-[10px] font-bold text-muted-foreground">{{ getTimeAgo(item.created_at) }}</span>
                            </div>
                        </div>

                        <button
                            v-if="item.data.url"
                            @click="go(item.data.url, item.id)"
                            class="p-2 rounded-xl text-muted-foreground hover:text-foreground hover:bg-muted transition-all shrink-0"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </div>

        </div>
    </AppLayout>
</template>
