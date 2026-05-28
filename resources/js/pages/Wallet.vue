<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Gift,
    AlertCircle, Clock, Zap, History, Trophy, Smartphone, Star
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

import type { WalletTransaction } from '@/types/wallet';

const props = defineProps<{
    points: number;
    total_earned: number;
    transactions: WalletTransaction[];
    draw_entries: any[];
    won_prizes: any[];
}>();

// Unified activity stream: wallet transactions + draw/airtime entries, newest first
const activityItems = computed(() => {
    const txItems = props.transactions.map(tx => ({ _source: 'transaction' as const, ...tx }));
    const entryItems = props.draw_entries.map(entry => ({ _source: 'entry' as const, ...entry }));
    return [...txItems, ...entryItems].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
});

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });
}

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
</script>

<template>
    <Head title="Rewards Center" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-10 p-6 max-w-5xl mx-auto w-full pb-32">
            <!-- Header -->
            <div>
                <h2 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3">
                    Rewards Center
                    <Gift class="w-6 h-6 text-indigo-500 fill-indigo-500/10" />
                </h2>
                <p class="text-muted-foreground mt-1 font-medium text-lg">Your direct rewards and prize draw status.</p>
            </div>

            <div class="grid gap-10 lg:grid-cols-12">
                <!-- Left: Stats & Prize Draws -->
                <div class="lg:col-span-5 space-y-10">
                    <!-- Total Earnings Card -->
                    <div class="relative overflow-hidden rounded-[3rem] bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 dark:from-zinc-900 dark:via-black dark:to-zinc-900 p-10 text-white shadow-2xl border border-border/60">
                        <div class="absolute top-0 right-0 p-10 opacity-15 dark:opacity-10">
                            <Zap class="w-40 h-40 fill-current text-white/15" />
                        </div>
                        
                        <div class="relative z-10 space-y-2">
                            <p class="text-[10px] font-black tracking-[0.3em] text-white/70 uppercase">Annual Earnings</p>
                            <div class="flex items-baseline gap-3">
                                <span class="text-7xl font-black tracking-tighter leading-none">{{ total_earned }}</span>
                                <span class="text-xl font-bold text-white/70 uppercase tracking-widest">pts</span>
                            </div>
                            <div class="pt-6">
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 border border-white/20 text-white text-[10px] font-black uppercase tracking-widest shadow-sm">
                                    <Star class="w-3 h-3 fill-current text-amber-300" />
                                    Active Member
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Prize Draws -->
                    <section class="space-y-6">
                        <h3 class="text-xs font-black text-muted-foreground uppercase tracking-[0.3em] px-2 flex items-center gap-2">
                            <Trophy class="w-4 h-4 text-amber-500" />
                            Active Prize Draws
                        </h3>
                        
                        <div v-if="draw_entries.length > 0" class="grid gap-4">
                            <div v-for="entry in draw_entries" :key="entry.id" class="p-6 rounded-[2rem] bg-card border border-border shadow-sm relative overflow-hidden group">
                                <div class="relative z-10">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest bg-amber-50 dark:bg-amber-900/20 px-3 py-1 rounded-full border border-amber-100 dark:border-amber-900/30">Entered</span>
                                        <span class="text-[10px] font-bold text-muted-foreground">{{ formatDate(entry.created_at) }}</span>
                                    </div>
                                    <h4 class="text-base font-black text-foreground uppercase tracking-tight truncate">{{ entry.survey?.prize_name || entry.survey?.title || 'Grand Prize Draw' }}</h4>
                                    <p class="text-xs text-muted-foreground mt-1 font-medium">Winner selection in progress...</p>
                                </div>
                                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500">
                                    <Trophy class="w-20 h-20" />
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-12 text-center border-2 border-dashed border-border rounded-[2.5rem]">
                            <p class="text-sm font-bold text-muted-foreground italic">No active entries</p>
                            <Link href="/surveys" class="text-xs font-black text-indigo-600 uppercase tracking-widest mt-4 block hover:underline">Complete surveys to enter</Link>
                        </div>
                    </section>

                    <!-- Won Prizes -->
                    <section v-if="won_prizes.length > 0" class="space-y-6">
                        <h3 class="text-xs font-black text-emerald-600 uppercase tracking-[0.3em] px-2">Winning History</h3>
                        <div class="grid gap-4">
                            <div v-for="prize in won_prizes" :key="prize.id" class="p-6 rounded-[2rem] bg-emerald-500 text-white shadow-xl shadow-emerald-500/20 relative overflow-hidden">
                                <div class="relative z-10">
                                    <h4 class="text-lg font-black uppercase leading-tight">Winner!</h4>
                                    <p class="text-emerald-100 text-sm font-medium mt-1">{{ prize.survey?.title }}</p>
                                    <div class="mt-4 text-xs font-black bg-white/20 w-fit px-4 py-2 rounded-xl border border-white/20">
                                        PRIZE CLAIMED
                                    </div>
                                </div>
                                <Trophy class="absolute -right-6 -bottom-6 w-32 h-32 opacity-20 rotate-12" />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right: Full Activity Stream -->
                <div class="lg:col-span-7 flex flex-col gap-6">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-xs font-black text-muted-foreground uppercase tracking-[0.3em] flex items-center gap-2">
                            <History class="w-4 h-4" />
                            Activity Stream
                        </h2>
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 px-3 py-1 rounded-full uppercase tracking-widest">{{ activityItems.length }} logs</span>
                    </div>

                    <div class="rounded-[3rem] border border-border bg-card overflow-hidden shadow-sm flex-1">
                        <div v-if="activityItems.length === 0" class="flex flex-col items-center justify-center p-20 text-center space-y-6">
                            <div class="h-20 w-20 rounded-full bg-muted flex items-center justify-center">
                                <Clock class="w-8 h-8 text-muted-foreground/30" />
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-xl font-black text-foreground uppercase tracking-tight">No activity yet</h3>
                                <p class="text-sm text-muted-foreground max-w-xs mx-auto font-medium">Complete surveys to see your rewards here.</p>
                            </div>
                        </div>

                        <div v-else class="divide-y divide-border/50">
                            <div
                                v-for="item in activityItems"
                                :key="`${item._source}-${item.id}`"
                                class="group flex items-center justify-between p-6 hover:bg-muted/30 transition-all"
                            >
                                <!-- Wallet transaction (points earn / airtime redeem) -->
                                <template v-if="item._source === 'transaction'">
                                    <div class="flex items-center gap-5">
                                        <div :class="[
                                            'flex h-12 w-12 items-center justify-center rounded-2xl shadow-inner transition-transform group-hover:scale-110',
                                            item.type === 'earn' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/20'
                                        ]">
                                            <Zap v-if="item.type === 'earn'" class="h-6 w-6 fill-current" />
                                            <Smartphone v-else class="h-6 w-6" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-black text-foreground uppercase truncate tracking-tight">
                                                    {{ item.type === 'earn' ? (item.meta?.survey_title || 'Survey Reward') : 'Direct Airtime' }}
                                                </p>
                                                <span
                                                    v-if="item.status && item.status !== 'completed'"
                                                    :class="[
                                                        item.status === 'processing' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                                        'text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded border'
                                                    ]"
                                                >
                                                    {{ item.status }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest">{{ getTimeAgo(item.created_at) }}</span>
                                                <span class="h-1 w-1 rounded-full bg-border"></span>
                                                <span class="text-[9px] font-bold text-muted-foreground opacity-60 uppercase">{{ formatDate(item.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="item.type === 'earn'" class="text-lg font-black tracking-tighter shrink-0 ml-4 text-emerald-600 dark:text-emerald-400">
                                        +{{ item.points }}
                                    </div>
                                    <div v-else class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-xl border border-blue-100 dark:border-blue-900/30">
                                        {{ item.meta?.amount ? 'K' + item.meta.amount : 'Sent' }}
                                    </div>
                                </template>

                                <!-- Prize draw / airtime survey entry -->
                                <template v-else>
                                    <div class="flex items-center gap-5">
                                        <div :class="[
                                            'flex h-12 w-12 items-center justify-center rounded-2xl shadow-inner transition-transform group-hover:scale-110',
                                            item.survey?.reward_type === 'airtime' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20' : 'bg-amber-50 text-amber-600 dark:bg-amber-900/20'
                                        ]">
                                            <Smartphone v-if="item.survey?.reward_type === 'airtime'" class="h-6 w-6" />
                                            <Trophy v-else class="h-6 w-6" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-black text-foreground uppercase truncate tracking-tight">
                                                    {{ item.survey?.title || 'Survey Completed' }}
                                                </p>
                                                <span v-if="item.is_winner" class="text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded border bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                                                    Winner
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest">{{ getTimeAgo(item.created_at) }}</span>
                                                <span class="h-1 w-1 rounded-full bg-border"></span>
                                                <span class="text-[9px] font-bold text-muted-foreground opacity-60 uppercase">{{ formatDate(item.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="item.survey?.reward_type === 'airtime'" class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-xl border border-blue-100 dark:border-blue-900/30 shrink-0 ml-4">
                                        {{ item.survey?.reward_amount ? 'K' + item.survey.reward_amount : 'Airtime' }}
                                    </div>
                                    <div v-else class="text-[10px] font-black uppercase tracking-widest text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 rounded-xl border border-amber-100 dark:border-amber-900/30 shrink-0 ml-4">
                                        Draw Entry
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-[2rem] bg-muted/30 border border-border flex items-center gap-4">
                        <AlertCircle class="w-5 h-5 text-indigo-500 shrink-0" />
                        <p class="text-[10px] font-bold text-muted-foreground leading-relaxed italic">All rewards are delivered directly upon survey completion. Airtime payouts are processed via Africa's Talking API.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
