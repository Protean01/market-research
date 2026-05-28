<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Wallet, ArrowRight, Table, Trophy } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = withDefaults(defineProps<{
    surveys?: any[];
}>(), {
    surveys: () => []
});

const selectedSurveyId = ref('');
const responsesDateFrom = ref('');
const responsesDateTo = ref('');

const selectedPrizeSurveyId = ref('');
const prizeEntriesDateFrom = ref('');
const prizeEntriesDateTo = ref('');

const transactionsDateFrom = ref('');
const transactionsDateTo = ref('');

function buildUrl(routeName: string, params: Record<string, string>): string {
    const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v !== ''))
    ).toString();
    return route(routeName) + (qs ? `?${qs}` : '');
}

const exportResponsesUrl = computed(() =>
    buildUrl('admin.export.responses', {
        survey_id: selectedSurveyId.value,
        date_from: responsesDateFrom.value,
        date_to:   responsesDateTo.value,
    })
);

const exportTransactionsUrl = computed(() =>
    buildUrl('admin.export.transactions', {
        date_from: transactionsDateFrom.value,
        date_to:   transactionsDateTo.value,
    })
);

const exportPrizeEntriesUrl = computed(() =>
    buildUrl('admin.export.prize-entries', {
        survey_id: selectedPrizeSurveyId.value,
        date_from: prizeEntriesDateFrom.value,
        date_to:   prizeEntriesDateTo.value,
    })
);
</script>

<template>
    <Head title="Admin · Data Export" />
    <AppLayout>
        <div class="space-y-8 p-6 max-w-7xl mx-auto w-full min-h-[400px]">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3">
                    <FileSpreadsheet class="w-8 h-8 text-indigo-600" />
                    Data Export
                </h1>
                <p class="text-base font-medium text-muted-foreground mt-1">
                    Download campaign results and financial records in CSV format.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:grid-rows-[auto_auto]">
                <!-- Survey Responses Card -->
                <div class="rounded-3xl border border-border bg-card p-8 shadow-sm flex flex-col space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <Table class="w-32 h-32" />
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center shadow-sm">
                            <Download class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-foreground uppercase tracking-tight">Survey Responses</h2>
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Raw data & Analytics</p>
                        </div>
                    </div>

                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Export respondent data, including their names, contact info, and individual answers. Selecting a specific survey will format the CSV with question texts as column headers.
                    </p>

                    <div class="space-y-4 pt-4">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">Filter by Campaign (Optional)</label>
                            <select v-model="selectedSurveyId" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                <option value="">All Campaigns (Aggregated JSON)</option>
                                <option v-for="survey in props.surveys" :key="survey.id" :value="survey.id">
                                    {{ survey.title }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">From</label>
                                <input type="date" v-model="responsesDateFrom" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">To</label>
                                <input type="date" v-model="responsesDateTo" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all" />
                            </div>
                        </div>

                        <a
                            :href="exportResponsesUrl"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-3 px-6 py-4 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 shadow-xl shadow-indigo-600/20 transition-all active:scale-95"
                        >
                            Download Responses CSV
                            <ArrowRight class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Wallet Transactions Card -->
                <div class="rounded-3xl border border-border bg-card p-8 shadow-sm flex flex-col space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <Wallet class="w-32 h-32" />
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shadow-sm">
                            <Download class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-foreground uppercase tracking-tight">Financial Audit</h2>
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Earnings & Redemptions</p>
                        </div>
                    </div>

                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Export all point-based transactions. Useful for reconciling payouts, tracking total platform liabilities, and monitoring reward distribution across the user base.
                    </p>

                    <div class="space-y-4 pt-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">From</label>
                                <input type="date" v-model="transactionsDateFrom" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-emerald-500/20 transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">To</label>
                                <input type="date" v-model="transactionsDateTo" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-emerald-500/20 transition-all" />
                            </div>
                        </div>

                        <a
                            :href="exportTransactionsUrl"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-3 px-6 py-4 bg-emerald-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-emerald-700 shadow-xl shadow-emerald-600/20 transition-all active:scale-95"
                        >
                            Download Audit CSV
                            <ArrowRight class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Prize Draw Winners Card -->
                <div class="rounded-3xl border border-border bg-card p-8 shadow-sm flex flex-col space-y-6 relative overflow-hidden lg:col-span-2">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <Trophy class="w-32 h-32" />
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center shadow-sm">
                            <Trophy class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-foreground uppercase tracking-tight">Prize Draw Entries</h2>
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Winners & Delivery Audit</p>
                        </div>
                    </div>

                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Export all prize draw entries including winners, the specific prize each winner was awarded, delivery status, and timestamps. Essential for airtime and cash prize delivery audits.
                    </p>

                    <div class="space-y-4 pt-2">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">Filter by Campaign (Optional)</label>
                            <select v-model="selectedPrizeSurveyId" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-amber-500/20 transition-all">
                                <option value="">All Campaigns</option>
                                <option v-for="survey in props.surveys" :key="survey.id" :value="survey.id">
                                    {{ survey.title }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">From</label>
                                <input type="date" v-model="prizeEntriesDateFrom" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-amber-500/20 transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">To</label>
                                <input type="date" v-model="prizeEntriesDateTo" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-amber-500/20 transition-all" />
                            </div>
                        </div>

                        <a
                            :href="exportPrizeEntriesUrl"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-3 px-6 py-4 bg-amber-500 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-amber-600 shadow-xl shadow-amber-500/20 transition-all active:scale-95"
                        >
                            Download Prize Entries CSV
                            <ArrowRight class="w-4 h-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
