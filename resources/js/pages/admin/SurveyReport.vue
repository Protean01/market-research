<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { ArrowLeft, Download, Users, MessageSquare, Trophy, AlertTriangle, Clock, BookOpen, CheckCircle, Loader2, Phone, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps<{
    survey: any;
    responseCount: number;
    analytics: Record<string, any>;
    flaggedCount: number;
    averageTime: number;
    drawEntries: any[];
    spunCount: number;
    totalPrizes: number;
    winners: any[];
}>();


const deliveringId = ref<number | null>(null);

const pendingSpins = computed(() => props.drawEntries.length - props.spunCount);

const drawPhaseStatus = computed(() => {
    if (props.survey.reward_type !== 'prize_draw' && props.survey.reward_type !== 'airtime') {
return null;
}

    const won        = props.winners.length;
    const multiPrize = props.totalPrizes > 1;
    const total      = props.totalPrizes > 0 ? props.totalPrizes : null;

    if (props.survey.draw_phase_active) {
        if (total !== null && won >= total) {
return { label: `All ${total} Prizes Won`, color: 'emerald' };
}

        if (won > 0) {
return { label: total !== null ? `${won} / ${total} Prizes Won` : 'Winner Found', color: 'amber' };
}

        return { label: 'Draw In Progress', color: 'amber' };
    }

    if (won > 0) {
return {
        label: multiPrize ? `${won} / ${total} Winners Selected` : 'Winner Selected',
        color: 'emerald',
    };
}

    return { label: 'Draw Not Started', color: 'muted' };
});

function saveToBank(index: number) {
    if (confirm('Save this question to the question library?')) {
        router.post(route('admin.surveys.export-question', { survey: props.survey.id } as any), {
            question_index: index
        });
    }
}

function closeDrawPhase() {
    if (confirm('Close the prize draw phase? Users will no longer be able to spin.')) {
        router.post(route('admin.surveys.close-draw', { survey: props.survey.id } as any));
    }
}

function markDelivered(entryId: number) {
    if (confirm('Mark this prize as delivered to the winner?')) {
        deliveringId.value = entryId;
        router.patch(
            route('admin.surveys.entries.deliver', { survey: props.survey.id, entry: entryId } as any),
            {},
            { onFinish: () => {
 deliveringId.value = null; 
} }
        );
    }
}

function getChartData(stats: any) {
    const labels = Object.keys(stats.data);
    const data = Object.values(stats.data) as number[];

    return {
        labels,
        datasets: [{ label: 'Responses', backgroundColor: '#6366f1', borderRadius: 8, data }]
    };
}

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } }
};

function exportToCSV() {
    alert('Preparing detailed CSV export...');
}
</script>

<template>
    <Head :title="'Report: ' + survey.title" />
    <AppLayout>
        <div class="space-y-8 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/admin/surveys" class="p-2 hover:bg-muted rounded-full transition-colors">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-foreground">{{ survey.title }}</h1>
                        <p class="text-base font-medium text-muted-foreground mt-1">Real-time Response Analytics</p>
                    </div>
                </div>
                <button @click="exportToCSV" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all active:scale-95">
                    <Download class="w-4 h-4" />
                    Export Data
                </button>
            </div>

            <!-- Stats -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="p-6 rounded-3xl border border-border bg-card shadow-sm">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl"><Users class="w-6 h-6" /></div>
                        <p class="text-sm font-black uppercase tracking-widest text-muted-foreground">Responses</p>
                    </div>
                    <p class="text-4xl font-black">{{ responseCount }}</p>
                </div>
                <div class="p-6 rounded-3xl border border-border bg-card shadow-sm">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl"><Clock class="w-6 h-6" /></div>
                        <p class="text-sm font-black uppercase tracking-widest text-muted-foreground">Avg. Time</p>
                    </div>
                    <p class="text-4xl font-black">{{ Math.round(averageTime || 0) }}s</p>
                </div>
                <div class="p-6 rounded-3xl border border-border bg-card shadow-sm">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="p-3 bg-red-100 text-red-600 rounded-xl"><AlertTriangle class="w-6 h-6" /></div>
                        <p class="text-sm font-black uppercase tracking-widest text-muted-foreground">Flagged</p>
                    </div>
                    <p class="text-4xl font-black">{{ flaggedCount }}</p>
                </div>
                <div class="p-6 rounded-3xl border border-border bg-card shadow-sm">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl"><Trophy class="w-6 h-6" /></div>
                        <p class="text-sm font-black uppercase tracking-widest text-muted-foreground">Winners</p>
                    </div>
                    <p class="text-4xl font-black">{{ winners.length }}</p>
                </div>
            </div>

            <!-- Prize Draw Management -->
            <div v-if="survey.reward_type === 'prize_draw' || survey.reward_type === 'airtime'" class="rounded-3xl border-2 border-amber-500/20 bg-amber-50/10 p-8 space-y-6">

                <!-- Header row: title + status badge -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-foreground flex items-center gap-2">
                            <Trophy class="w-6 h-6 text-amber-500" />
                            Prize Draw Management
                            <span v-if="survey.prize_name" class="text-base font-bold text-amber-600 bg-amber-100 dark:bg-amber-900/30 px-3 py-1 rounded-full">
                                {{ survey.prize_name }}
                            </span>
                        </h2>
                        <p class="text-muted-foreground font-medium mt-1">
                            {{ drawEntries.length }} total {{ drawEntries.length === 1 ? 'entry' : 'entries' }}
                        </p>
                    </div>

                    <!-- Draw phase status badge -->
                    <span
                        v-if="drawPhaseStatus"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest"
                        :class="{
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': drawPhaseStatus.color === 'emerald',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': drawPhaseStatus.color === 'amber',
                            'bg-muted text-muted-foreground': drawPhaseStatus.color === 'muted',
                        }"
                    >
                        <span class="w-2 h-2 rounded-full"
                            :class="{
                                'bg-emerald-500': drawPhaseStatus.color === 'emerald',
                                'bg-amber-500 animate-pulse': drawPhaseStatus.color === 'amber',
                                'bg-muted-foreground': drawPhaseStatus.color === 'muted',
                            }"
                        ></span>
                        {{ drawPhaseStatus.label }}
                    </span>

                    <!-- Manual close button if draw is stuck open -->
                    <button
                        v-if="survey.draw_phase_active"
                        @click="closeDrawPhase"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest bg-muted hover:bg-rose-100 hover:text-rose-700 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 text-muted-foreground transition-all"
                        title="Manually close the draw phase"
                    >
                        <XCircle class="w-3.5 h-3.5" />
                        Close Draw
                    </button>
                </div>

                <!-- Spins progress -->
                <div v-if="survey.draw_phase_active && drawEntries.length > 0" class="bg-white dark:bg-zinc-900 rounded-2xl border border-amber-200 dark:border-amber-900/30 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-black uppercase tracking-widest text-muted-foreground">Spin Progress</p>
                        <p class="text-xs font-black text-foreground">
                            {{ spunCount }} / {{ drawEntries.length }} spun
                            <span v-if="pendingSpins > 0" class="text-amber-600 ml-2">· {{ pendingSpins }} still waiting</span>
                            <span v-else class="text-emerald-600 ml-2">· All done</span>
                        </p>
                    </div>
                    <div class="w-full bg-muted rounded-full h-2.5 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="winners.length > 0 ? 'bg-emerald-500' : 'bg-amber-500'"
                            :style="{ width: drawEntries.length > 0 ? (spunCount / drawEntries.length * 100) + '%' : '0%' }"
                        ></div>
                    </div>
                </div>

                <!-- Winners -->
                <div v-if="winners.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="winner in winners"
                        :key="winner.id"
                        class="p-5 rounded-2xl border-2 shadow-lg relative overflow-hidden"
                        :class="winner.prize_delivered
                            ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-400'
                            : 'bg-white dark:bg-zinc-900 border-amber-500'"
                    >
                        <div class="absolute -right-4 -top-4 opacity-10">
                            <Trophy class="w-24 h-24 text-amber-500" />
                        </div>

                        <!-- Status label -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-black uppercase tracking-widest"
                                :class="winner.prize_delivered ? 'text-emerald-600' : 'text-amber-600'">
                                {{ winner.prize_delivered ? 'Prize Delivered' : 'Winner Selected!' }}
                            </span>
                            <CheckCircle v-if="winner.prize_delivered" class="w-4 h-4 text-emerald-500" />
                        </div>

                        <!-- Winner details -->
                        <p class="font-black text-lg text-foreground">{{ winner.user.name }}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <Phone class="w-3.5 h-3.5 text-muted-foreground" />
                            <p class="text-sm font-bold text-muted-foreground">{{ winner.user.phone_number }}</p>
                        </div>

                        <!-- Prize won -->
                        <div v-if="winner.prize_snapshot" class="mt-3 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30">
                            <p class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest">
                                {{ winner.prize_snapshot.name || `Prize ${(winner.prize_index ?? 0) + 1}` }}
                            </p>
                            <p v-if="winner.prize_snapshot.points" class="text-sm font-bold text-amber-600 dark:text-amber-300 mt-0.5">
                                {{ winner.prize_snapshot.points }} pts
                            </p>
                            <p v-else-if="winner.prize_snapshot.amount" class="text-sm font-bold text-amber-600 dark:text-amber-300 mt-0.5">
                                {{ winner.prize_snapshot.amount }} airtime
                            </p>
                        </div>

                        <!-- Timestamps -->
                        <div class="mt-3 space-y-1">
                            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider">
                                Won: {{ winner.won_at ? new Date(winner.won_at).toLocaleString() : '—' }}
                            </p>
                            <p v-if="winner.prize_delivered && winner.delivered_at" class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">
                                Delivered: {{ new Date(winner.delivered_at).toLocaleString() }}
                            </p>
                        </div>

                        <!-- Mark delivered button -->
                        <button
                            v-if="!winner.prize_delivered"
                            @click="markDelivered(winner.id)"
                            :disabled="deliveringId === winner.id"
                            class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-700 transition-all active:scale-95 disabled:opacity-50"
                        >
                            <Loader2 v-if="deliveringId === winner.id" class="w-3.5 h-3.5 animate-spin" />
                            <CheckCircle v-else class="w-3.5 h-3.5" />
                            Mark as Delivered
                        </button>
                    </div>
                </div>

                <!-- No winners yet -->
                <div v-else-if="survey.draw_phase_active" class="flex flex-col items-center justify-center py-10 text-center opacity-60">
                    <Trophy class="w-10 h-10 text-amber-400 mb-3" />
                    <p class="text-sm font-black text-muted-foreground uppercase tracking-widest">No winner yet</p>
                    <p class="text-xs text-muted-foreground mt-1">Waiting for users to spin the slot machine.</p>
                </div>
            </div>

            <!-- Question Analytics -->
            <div class="grid gap-8">
                <div v-for="(stat, qId, index) in analytics" :key="qId" class="p-8 rounded-3xl border border-border bg-card shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="h-6 w-1 bg-indigo-600 rounded-full"></div>
                            <h3 class="text-lg font-black text-foreground uppercase tracking-tight">{{ stat.text }}</h3>
                        </div>
                        <button
                            @click="saveToBank(index)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-muted hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-muted-foreground hover:text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                            title="Save to Question Bank"
                        >
                            <BookOpen class="w-3.5 h-3.5" />
                            Save to Bank
                        </button>
                    </div>

                    <div v-if="stat.type === 'mcq' || stat.type === 'checkbox' || stat.type === 'scale'" class="h-[300px]">
                        <Bar :data="getChartData(stat)" :options="chartOptions" />
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="(ans, idx) in stat.data" :key="idx" class="p-4 rounded-2xl bg-muted/30 border border-border flex gap-4">
                            <MessageSquare class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" />
                            <p class="text-sm font-medium text-foreground">{{ ans }}</p>
                        </div>
                        <p v-if="stat.data.length === 0" class="text-sm text-muted-foreground italic">No text responses yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
