<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { Users, ClipboardList, Activity, Coins, TrendingUp, CheckCircle2, Clock, AlertCircle, BarChart2, BookmarkPlus } from 'lucide-vue-next';
import { Bar } from 'vue-chartjs';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

defineProps<{
    metrics: {
        total_users: number;
        active_surveys: number;
        responses_today: number;
        total_points_distributed: number;
    };
    active_surveys: any[];
}>();



function getStatusColor(count: number, cap: number) {
    const percent = (count / cap) * 100;

    if (percent >= 90) {
return 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400';
}

    if (percent >= 50) {
return 'text-amber-600 bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400';
}

    return 'text-indigo-600 bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-400';
}

function getProgressColor(count: number, cap: number) {
    const percent = (count / cap) * 100;

    if (percent >= 90) {
return 'bg-emerald-500';
}

    if (percent >= 50) {
return 'bg-amber-500';
}

    return 'bg-indigo-600';
}

function getChartData(qData: any) {
    return {
        labels: qData.labels,
        datasets: [{
            label: 'Responses',
            data: qData.data,
            backgroundColor: '#4f46e5', // indigo-600
            borderRadius: 6,
        }]
    };
}

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
    }
};

function exportSurveyToBank(survey: any) {
    const payload = {
        name: `${survey.title} · Exported`,
        category: 'Monitoring Export',
        client_id: survey.client_id,
    };

    router.post(
        route('admin.surveys.export-question', survey.id),
        payload,
        {
            preserveScroll: true,
        }
    );
}
</script>

<template>
    <Head title="Admin · Monitoring" />
    <AppLayout>
        <div class="space-y-8 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Live Monitoring</h1>
                    <p class="text-base font-medium text-muted-foreground mt-1">
                        Real-time analytics for survey performance and system health.
                    </p>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    SYSTEM ONLINE
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Users -->
                <div class="group rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-indigo-500/50">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-muted-foreground uppercase tracking-wider">Total Users</p>
                            <h3 class="mt-2 text-3xl font-black text-foreground">{{ metrics.total_users.toLocaleString() }}</h3>
                        </div>
                        <div class="rounded-xl bg-indigo-100 p-2.5 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                            <Users class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-bold text-emerald-600">
                        <TrendingUp class="mr-1 h-3.5 w-3.5" />
                        <span>GROWING</span>
                    </div>
                </div>

                <!-- Active Surveys -->
                <div class="group rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-emerald-500/50">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-muted-foreground uppercase tracking-wider">Active Surveys</p>
                            <h3 class="mt-2 text-3xl font-black text-foreground">{{ metrics.active_surveys.toLocaleString() }}</h3>
                        </div>
                        <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <ClipboardList class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-bold text-muted-foreground">
                        <Activity class="mr-1 h-3.5 w-3.5 text-emerald-500" />
                        <span>LIVE NOW</span>
                    </div>
                </div>

                <!-- Responses Today -->
                <div class="group rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-amber-500/50">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-muted-foreground uppercase tracking-wider">Today's Traffic</p>
                            <h3 class="mt-2 text-3xl font-black text-foreground">{{ metrics.responses_today.toLocaleString() }}</h3>
                        </div>
                        <div class="rounded-xl bg-amber-100 p-2.5 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <TrendingUp class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-bold text-amber-600">
                        <Clock class="mr-1 h-3.5 w-3.5" />
                        <span>REPLIES TODAY</span>
                    </div>
                </div>

                <!-- Points Distributed -->
                <div class="group rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-purple-500/50">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-muted-foreground uppercase tracking-wider">Distributed</p>
                            <h3 class="mt-2 text-3xl font-black text-foreground">{{ metrics.total_points_distributed.toLocaleString() }}</h3>
                        </div>
                        <div class="rounded-xl bg-purple-100 p-2.5 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                            <Coins class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-bold text-purple-600">
                        <CheckCircle2 class="mr-1 h-3.5 w-3.5" />
                        <span>PTS ISSUED</span>
                    </div>
                </div>
            </div>

            <!-- Active Surveys Progress -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold tracking-tight text-foreground">Survey Performance</h2>
                    <div class="text-sm font-bold text-muted-foreground">
                        Showing {{ active_surveys.length }} active campaigns
                    </div>
                </div>

                <div v-if="active_surveys.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border p-12 bg-card/50 text-center">
                    <div class="rounded-full bg-muted p-4 mb-4">
                        <AlertCircle class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-bold text-foreground">No active surveys found</h3>
                    <p class="text-sm text-muted-foreground max-w-sm mt-1">When you publish surveys, their progress will appear here in real-time.</p>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div 
                        v-for="survey in active_surveys" 
                        :key="survey.id" 
                        class="rounded-2xl border border-border bg-card p-6 shadow-sm flex flex-col justify-between"
                    >
                        <div class="flex justify-between items-start mb-6 gap-3">
                            <div class="max-w-[65%]">
                                <h3 class="text-lg font-bold text-foreground truncate">{{ survey.title }}</h3>
                                <p class="text-xs font-bold text-muted-foreground mt-1 flex items-center gap-1.5 uppercase tracking-wider">
                                    <Users class="w-3 h-3" />
                                    {{ survey.response_cap }} Responses Targeted
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span 
                                    class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-tighter"
                                    :class="getStatusColor(survey.responses_count, survey.response_cap)"
                                >
                                    {{ Math.round((survey.responses_count / survey.response_cap) * 100) }}% Complete
                                </span>
                                <button
                                    @click="exportSurveyToBank(survey)"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase tracking-tight shadow-sm hover:bg-indigo-500 active:scale-95 transition-all"
                                >
                                    <BookmarkPlus class="w-4 h-4 text-white" />
                                    Save to Question Bank
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black text-muted-foreground tracking-widest uppercase">{{ survey.responses_count }} Received</span>
                                <span class="text-xs font-black text-muted-foreground tracking-widest uppercase">{{ survey.response_cap - survey.responses_count }} Left</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-3 overflow-hidden p-0.5 border border-border">
                                <div 
                                    class="h-full rounded-full transition-all duration-700 ease-out shadow-sm" 
                                    :class="getProgressColor(survey.responses_count, survey.response_cap)"
                                    :style="{ width: Math.min(100, (survey.responses_count / survey.response_cap) * 100) + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Data Charts -->
                        <div v-if="survey.chart_data && Object.keys(survey.chart_data).length > 0" class="mt-6 pt-6 border-t border-border">
                            <h4 class="text-sm font-bold text-foreground mb-4 flex items-center gap-2">
                                <BarChart2 class="w-4 h-4 text-indigo-500" />
                                Response Analytics
                            </h4>
                            <div class="space-y-6">
                                <div v-for="(qData, qId) in survey.chart_data" :key="qId" class="bg-muted/10 rounded-xl p-4 border border-border/50">
                                    <p class="text-sm font-bold text-foreground mb-3 truncate" :title="qData.question">{{ qData.question }}</p>
                                    <div class="h-48">
                                        <Bar v-if="qData.labels.length" :data="getChartData(qData)" :options="chartOptions" />
                                        <div v-else class="h-full flex items-center justify-center text-xs font-semibold text-muted-foreground">No data yet</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
