<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    LineElement, PointElement, Filler
} from 'chart.js';
import {
    Users, Activity, Coins, TrendingUp, CheckCircle2,
    ShieldAlert, BarChart3, MapPin, ChevronRight,
    PlusCircle, UserPlus, Trophy, AlertTriangle,
    Clock, Zap, Target
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

ChartJS.register(
    Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, LineElement,
    PointElement, Filler
);

const props = defineProps<{
    summary: {
        users: { total: number; growth_7d: number; new_today: number; active_30d: number; active_rate: number };
        surveys: { active: number; total_responses: number; responses_7d: number; responses_today: number; avg_time: number };
        financial: { points_wild: number; liability_zmw: number; total_payout_zmw: number };
        quality: { avg_trust: number; flagged_total: number; flagged_today: number };
        demographics: { locations: any[]; income: any[] };
    };
    charts: { users: any[]; responses: any[] };
    top_surveys: any[];
    surveys_nearing_cap: any[];
    active_draws: any[];
    trends: { user_growth_7d: number };
}>();



const hasQualityAlert = computed(() =>
    props.summary.quality.flagged_today > 0 || props.summary.quality.avg_trust < 70
);

const metricCards = computed(() => [
    {
        label: 'Panel Size',
        value: props.summary.users.total.toLocaleString(),
        sub: `+${props.summary.users.new_today} today`,
        icon: Users,
        trend: `${props.summary.users.growth_7d} new this week`,
        color: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-50 dark:bg-blue-900/20',
    },
    {
        label: 'Active Surveys',
        value: props.summary.surveys.active,
        sub: 'Running in-field',
        icon: Activity,
        trend: `${props.summary.surveys.responses_today} responses today`,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-50 dark:bg-emerald-900/20',
    },
    {
        label: 'Responses (7d)',
        value: props.summary.surveys.responses_7d.toLocaleString(),
        sub: `${props.summary.surveys.total_responses.toLocaleString()} all time`,
        icon: CheckCircle2,
        trend: `Avg. ${props.summary.surveys.avg_time}s to complete`,
        color: 'text-purple-600 dark:text-purple-400',
        bg: 'bg-purple-50 dark:bg-purple-900/20',
    },
    {
        label: 'Engagement Rate',
        value: props.summary.users.active_rate + '%',
        sub: `${props.summary.users.active_30d.toLocaleString()} active users`,
        icon: Zap,
        trend: 'Active in last 30 days',
        color: 'text-amber-600 dark:text-amber-400',
        bg: 'bg-amber-50 dark:bg-amber-900/20',
    },
    {
        label: 'Avg. Completion',
        value: props.summary.surveys.avg_time + 's',
        sub: 'Per survey',
        icon: Clock,
        trend: 'Across all responses',
        color: 'text-sky-600 dark:text-sky-400',
        bg: 'bg-sky-50 dark:bg-sky-900/20',
    },
    {
        label: 'Trust Score',
        value: props.summary.quality.avg_trust + '%',
        sub: `${props.summary.quality.flagged_total} flagged total`,
        icon: ShieldAlert,
        trend: props.summary.quality.flagged_today > 0 ? `⚠ ${props.summary.quality.flagged_today} flagged today` : 'No flags today',
        color: props.summary.quality.avg_trust < 70 ? 'text-rose-600 dark:text-rose-400' : 'text-teal-600 dark:text-teal-400',
        bg: props.summary.quality.avg_trust < 70 ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-teal-50 dark:bg-teal-900/20',
    },
]);

const responseChartData = computed(() => ({
    labels: props.charts.responses.map(d => {
        const date = new Date(d.date);

        return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
    }),
    datasets: [{
        label: 'Daily Completions',
        data: props.charts.responses.map(d => d.count),
        backgroundColor: '#6366f1',
        borderRadius: 6,
        barThickness: 12,
    }]
}));

const chartOptions = computed(() => {
    const isDark = document.documentElement.classList.contains('dark');
    const tickColor = isDark ? '#a1a1aa' : '#71717a';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDark ? '#27272a' : '#18181b',
                titleFont: { size: 12, weight: 'bold' as const },
                bodyFont: { size: 12 },
                padding: 12,
                cornerRadius: 12,
                displayColors: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [5, 5], color: gridColor },
                ticks: { font: { size: 10, weight: 'bold' }, color: tickColor }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 9, weight: 'bold' }, color: tickColor }
            }
        }
    };
});

const maxLocationCount = computed(() =>
    props.summary.demographics.locations.reduce((max, l) => Math.max(max, l.count), 1)
);

const maxIncomeCount = computed(() =>
    props.summary.demographics.income.reduce((max, i) => Math.max(max, i.count), 1)
);

function capPercent(survey: any) {
    if (!survey.response_cap) return 0;
    return Math.min(100, Math.round((survey.response_count / survey.response_cap) * 100));
}

function formatCurrency(val: number) {
    return new Intl.NumberFormat('en-ZM', { style: 'currency', currency: 'ZMW' }).format(val);
}
</script>

<template>
    <Head title="Admin Dashboard" />
    <AppLayout>
        <div class="p-8 max-w-[1600px] mx-auto w-full space-y-10 pb-32">

            <!-- Header -->
            <div class="flex items-end justify-between border-b border-border pb-8">
                <div>
                    <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase">Operational Summary</h1>
                    <p class="text-muted-foreground font-medium mt-1 uppercase tracking-widest text-[10px]">Real-time platform governance & research health</p>
                </div>
                <div class="flex gap-4">
                    <Link href="/admin/export" class="px-6 py-3 bg-card border border-border rounded-xl text-xs font-black uppercase tracking-widest hover:bg-muted transition-all">Export Data</Link>
                    <Link href="/admin/surveys" class="px-6 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all">New Campaign</Link>
                </div>
            </div>

            <!-- Quality Alert Banner -->
            <div v-if="hasQualityAlert" class="flex items-start gap-4 p-5 rounded-2xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                <AlertTriangle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                <div class="flex-1">
                    <p class="text-sm font-black text-rose-800 dark:text-rose-300 uppercase tracking-wide">Quality Alert</p>
                    <p class="text-xs text-rose-700 dark:text-rose-400 mt-0.5 font-medium">
                        <span v-if="summary.quality.flagged_today > 0">{{ summary.quality.flagged_today }} flagged response{{ summary.quality.flagged_today > 1 ? 's' : '' }} submitted today. </span>
                        <span v-if="summary.quality.avg_trust < 70">Platform trust score is below 70% (currently {{ summary.quality.avg_trust }}%). </span>
                        <Link href="/admin/quality" class="underline font-black">Review in Quality Control →</Link>
                    </p>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5">
                <div v-for="metric in metricCards" :key="metric.label" class="bg-card border border-border rounded-[2rem] p-6 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div :class="[metric.bg, metric.color, 'p-2.5 rounded-xl']">
                            <component :is="metric.icon" class="w-5 h-5" />
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground text-right leading-tight">{{ metric.label }}</span>
                    </div>
                    <div>
                        <h3 class="text-3xl font-black tracking-tight text-foreground">{{ metric.value }}</h3>
                        <p class="text-[10px] font-bold text-muted-foreground mt-0.5">{{ metric.sub }}</p>
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-60 text-muted-foreground border-t border-border pt-3">{{ metric.trend }}</p>
                </div>
            </div>

            <!-- Alerts row: nearing cap + active draws -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Surveys Nearing Cap -->
                <div v-if="surveys_nearing_cap.length > 0" class="bg-card border-2 border-amber-400/40 dark:border-amber-700/40 rounded-3xl p-6 space-y-4">
                    <div class="flex items-center gap-2">
                        <Target class="w-4 h-4 text-amber-600" />
                        <h3 class="text-xs font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">Surveys Nearing Cap</h3>
                        <span class="ml-auto text-[10px] font-black bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full">{{ surveys_nearing_cap.length }}</span>
                    </div>
                    <div class="space-y-3">
                        <div v-for="s in surveys_nearing_cap" :key="s.id" class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Link :href="'/admin/surveys/' + s.id + '/report'" class="text-xs font-black text-foreground hover:text-indigo-600 transition-colors truncate max-w-[200px]">{{ s.title }}</Link>
                                <span class="text-xs font-black" :class="capPercent(s) >= 100 ? 'text-rose-600' : 'text-amber-600'">
                                    {{ s.response_count }} / {{ s.response_cap }} ({{ capPercent(s) }}%)
                                </span>
                            </div>
                            <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    :class="capPercent(s) >= 100 ? 'bg-rose-500' : 'bg-amber-500'"
                                    :style="{ width: capPercent(s) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-card border border-border rounded-3xl p-6 flex items-center gap-4 opacity-50">
                    <Target class="w-5 h-5 text-muted-foreground" />
                    <p class="text-xs font-black uppercase tracking-widest text-muted-foreground">No surveys nearing response cap</p>
                </div>

                <!-- Active Prize Draws -->
                <div v-if="active_draws.length > 0" class="bg-card border-2 border-amber-500/30 rounded-3xl p-6 space-y-4">
                    <div class="flex items-center gap-2">
                        <Trophy class="w-4 h-4 text-amber-500" />
                        <h3 class="text-xs font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">Active Prize Draws</h3>
                        <span class="ml-auto text-[10px] font-black bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full">{{ active_draws.length }}</span>
                    </div>
                    <div class="space-y-3">
                        <div v-for="draw in active_draws" :key="draw.id" class="flex items-center justify-between p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-900/10 border border-amber-200/50 dark:border-amber-800/30">
                            <div>
                                <p class="text-xs font-black text-foreground">{{ draw.title }}</p>
                                <p class="text-[10px] text-muted-foreground font-medium mt-0.5">
                                    {{ draw.prize_name || 'No prize set' }} · {{ draw.prize_draw_entries_count }} entries
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="draw.winner_count > 0" class="flex items-center gap-1 text-[10px] font-black text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                                    <CheckCircle2 class="w-3 h-3" /> Winner Found
                                </span>
                                <span v-else class="flex items-center gap-1.5 text-[10px] font-black text-amber-600 bg-amber-100 dark:bg-amber-900/30 px-2 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> In Progress
                                </span>
                                <Link :href="'/admin/surveys/' + draw.id + '/report'" class="p-1.5 hover:bg-muted rounded-lg transition-colors">
                                    <ChevronRight class="w-4 h-4 text-muted-foreground" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-card border border-border rounded-3xl p-6 flex items-center gap-4 opacity-50">
                    <Trophy class="w-5 h-5 text-muted-foreground" />
                    <p class="text-xs font-black uppercase tracking-widest text-muted-foreground">No active prize draws</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">

                <!-- Left: Chart + Active Campaigns -->
                <div class="xl:col-span-8 space-y-10">

                    <!-- Survey Completions Chart -->
                    <section class="bg-card border border-border rounded-[2.5rem] p-8 shadow-sm">
                        <div class="flex items-center justify-between mb-8">
                            <div class="space-y-1">
                                <h2 class="text-sm font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-2">
                                    <TrendingUp class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    Research Velocity
                                </h2>
                                <p class="text-2xl font-black text-foreground uppercase tracking-tight">Survey Completions</p>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-muted rounded-full">
                                <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Last 30 Days</span>
                            </div>
                        </div>
                        <div class="h-[300px] w-full">
                            <Bar :data="responseChartData" :options="(chartOptions as any)" />
                        </div>
                    </section>

                    <!-- Active Fieldwork Table -->
                    <section class="space-y-6">
                        <div class="flex items-center justify-between px-2">
                            <h2 class="text-sm font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-2">
                                <Activity class="w-4 h-4" />
                                Active Fieldwork
                            </h2>
                            <Link href="/admin/surveys" class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 hover:underline uppercase tracking-widest">All Surveys</Link>
                        </div>
                        <div class="bg-card border border-border rounded-[2.5rem] overflow-hidden shadow-sm">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-muted/30 border-b border-border">
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Campaign</th>
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-muted-foreground text-center">Progress</th>
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-muted-foreground text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr v-for="s in top_surveys" :key="s.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-8 py-6">
                                            <p class="font-black text-foreground uppercase tracking-tight">{{ s.title }}</p>
                                            <p class="text-[10px] text-muted-foreground font-medium uppercase mt-1">{{ s.responses_count }} / {{ s.response_cap }} insights gathered</p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4 justify-center">
                                                <div class="w-32 h-2 bg-muted rounded-full overflow-hidden border border-border">
                                                    <div class="h-full rounded-full shadow-[0_0_10px_rgba(79,70,229,0.4)]"
                                                        :class="capPercent(s) >= 80 ? 'bg-amber-500' : 'bg-indigo-600 dark:bg-indigo-500'"
                                                        :style="{ width: Math.min(100, (s.responses_count / (s.response_cap || 100)) * 100) + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-xs font-black text-foreground">{{ Math.round((s.responses_count / (s.response_cap || 100)) * 100) }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <span class="text-[10px] font-black px-2 py-1 rounded-full"
                                                :class="s.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-muted text-muted-foreground'">
                                                {{ s.is_active ? 'ACTIVE' : 'PAUSED' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="top_surveys.length === 0">
                                        <td colspan="3" class="px-8 py-12 text-center text-sm text-muted-foreground font-medium">No surveys yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar -->
                <div class="xl:col-span-4 space-y-8">

                    <!-- Financial Governance -->
                    <div class="bg-gradient-to-br from-indigo-700 via-indigo-600 to-blue-500 dark:from-zinc-900 dark:via-black dark:to-zinc-900 rounded-[3rem] p-10 text-white shadow-2xl border border-indigo-500/40 dark:border-white/10 space-y-10 relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/70 mb-8">Financial Governance</h3>
                            <div class="space-y-8">
                                <div class="space-y-1">
                                    <p class="text-[9px] font-black uppercase text-white/70 tracking-widest">Total Liability (Points in Circulation)</p>
                                    <p class="text-4xl font-black tracking-tighter text-lime-200">{{ formatCurrency(summary.financial.liability_zmw) }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[9px] font-black uppercase text-white/70 tracking-widest">Total Redemptions (Paid)</p>
                                    <p class="text-2xl font-black tracking-tight text-white">{{ formatCurrency(summary.financial.total_payout_zmw) }}</p>
                                </div>
                                <div class="pt-8 border-t border-white/20 dark:border-white/10 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[9px] font-black uppercase text-white/70">Points in Wild</span>
                                        <span class="text-sm font-black text-white">{{ summary.financial.points_wild.toLocaleString() }} PTS</span>
                                    </div>
                                    <button
                                        @click="router.post('/admin/dashboard/reconcile')"
                                        class="w-full py-4 rounded-2xl bg-white dark:bg-zinc-800 text-indigo-900 dark:text-white font-black uppercase tracking-widest text-xs hover:opacity-90 transition-all active:scale-95"
                                    >
                                        Reconcile Accounts
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -top-10 -right-10 opacity-20 rotate-12">
                            <Coins class="w-64 h-64 text-white/30 dark:text-white/10" />
                        </div>
                    </div>

                    <!-- Demographics: Locations -->
                    <div class="bg-card border border-border rounded-[2.5rem] p-8 space-y-6">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-2">
                            <MapPin class="w-3 h-3" />
                            Top Locations
                        </h3>
                        <div v-if="summary.demographics.locations.length" class="space-y-4">
                            <div v-for="loc in summary.demographics.locations" :key="loc.location" class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold uppercase tracking-tight">
                                    <span>{{ loc.location }}</span>
                                    <span class="text-muted-foreground">{{ loc.count }}</span>
                                </div>
                                <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full" :style="{ width: (loc.count / maxLocationCount * 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground italic">No location data yet.</p>
                    </div>

                    <!-- Demographics: Income -->
                    <div class="bg-card border border-border rounded-[2.5rem] p-8 space-y-6">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-2">
                            <Coins class="w-3 h-3" />
                            Income Distribution
                        </h3>
                        <div v-if="summary.demographics.income.length" class="space-y-4">
                            <div v-for="band in summary.demographics.income" :key="band.income_band" class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold uppercase tracking-tight">
                                    <span>{{ band.income_band }}</span>
                                    <span class="text-muted-foreground">{{ band.count }}</span>
                                </div>
                                <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" :style="{ width: (band.count / maxIncomeCount * 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground italic">No income data yet.</p>
                    </div>

                    <!-- Quick Links -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground px-4">Research Modules</h3>
                        <div class="grid grid-cols-1 gap-3">
                            <Link :href="route('admin.surveys', [])" class="flex items-center justify-between p-5 rounded-3xl border border-border bg-card hover:bg-muted/30 transition-all group">
                                <div class="flex items-center gap-4">
                                    <PlusCircle class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                    <span class="text-xs font-black uppercase tracking-tight">Create Survey</span>
                                </div>
                                <ChevronRight class="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <Link :href="route('admin.users', [])" class="flex items-center justify-between p-5 rounded-3xl border border-border bg-card hover:bg-muted/30 transition-all group">
                                <div class="flex items-center gap-4">
                                    <UserPlus class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                    <span class="text-xs font-black uppercase tracking-tight">User Management</span>
                                </div>
                                <ChevronRight class="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <Link href="/admin/quality" class="flex items-center justify-between p-5 rounded-3xl border border-border bg-card hover:bg-muted/30 transition-all group">
                                <div class="flex items-center gap-4">
                                    <ShieldAlert class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                                    <span class="text-xs font-black uppercase tracking-tight">Quality Control</span>
                                </div>
                                <ChevronRight class="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <Link :href="route('admin.monitoring', [])" class="flex items-center justify-between p-5 rounded-3xl border border-border bg-card hover:bg-muted/30 transition-all group">
                                <div class="flex items-center gap-4">
                                    <BarChart3 class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                    <span class="text-xs font-black uppercase tracking-tight">View Analytics</span>
                                </div>
                                <ChevronRight class="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
