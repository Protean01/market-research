<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement } from 'chart.js';
import { 
    Users, TrendingUp, 
    MapPin, User, Briefcase, Banknote, Target, Star, Filter, X, BookOpen
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Pie, Bar } from 'vue-chartjs';
import AppLayout from '@/layouts/AppLayout.vue';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const props = defineProps<{
    surveys: any[];
    clients: any[];
    selected_survey_id: string | number | null;
    selected_client_id: string | number | null;
    stats: {
        total_users: number;
        profile_completion: number;
    };
    demographics: {
        gender: any[];
        age: any[];
        income: any[];
        occupation: any[];
        location: any[];
        employment: any[];
        education: any[];
    };
    traits: Record<string, number>;
}>();

const selectedSurvey = ref(props.selected_survey_id || '');
const selectedClient = ref(props.selected_client_id || '');

watch([selectedSurvey, selectedClient], ([newSurvey, newClient], [, oldClient]) => {
    // If client changes, reset survey
    let querySurvey = newSurvey;

    if (newClient !== oldClient) {
        selectedSurvey.value = '';
        querySurvey = '';
    }

    router.get('/admin/audience', { survey_id: querySurvey, client_id: newClient }, {
        preserveState: true,
        replace: true
    });
});

function clearFilter() {
    selectedSurvey.value = '';
    selectedClient.value = '';
}

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
            labels: {
                usePointStyle: true,
                padding: 20,
                color: '#888',
                font: { size: 10, weight: 'bold' as any }
            }
        }
    }
};

const barOptions = {
    ...chartOptions,
    scales: {
        y: { beginAtZero: true, grid: { display: false }, ticks: { color: '#888' } },
        x: { grid: { display: false }, ticks: { color: '#888' } }
    }
};

const genderChart = {
    labels: props.demographics.gender.map(d => d.gender),
    datasets: [{
        data: props.demographics.gender.map(d => d.count),
        backgroundColor: ['#6366f1', '#ec4899', '#10b981', '#f59e0b'],
        borderWidth: 0
    }]
};

const ageChart = {
    labels: props.demographics.age.map(d => d.age_band),
    datasets: [{
        label: 'Users',
        data: props.demographics.age.map(d => d.count),
        backgroundColor: '#6366f1',
        borderRadius: 8
    }]
};

const incomeChart = {
    labels: props.demographics.income.map(d => d.income_band),
    datasets: [{
        data: props.demographics.income.map(d => d.count),
        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
        borderWidth: 0
    }]
};

const employmentChart = {
    labels: props.demographics.employment.map(d => d.employment),
    datasets: [{
        data: props.demographics.employment.map(d => d.count),
        backgroundColor: ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
        borderWidth: 0
    }]
};

const educationChart = {
    labels: props.demographics.education.map(d => d.education),
    datasets: [{
        label: 'Users',
        data: props.demographics.education.map(d => d.count),
        backgroundColor: '#ec4899',
        borderRadius: 8
    }]
};
</script>

<template>
    <Head title="Audience Insights" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-10 p-8 max-w-7xl mx-auto w-full pb-32">
            
            <!-- Header & Filter -->
            <div class="flex flex-col gap-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-black tracking-tight text-foreground uppercase leading-none">
                            {{ selected_survey_id ? 'Campaign Audience' : 'Global Audience' }}
                        </h1>
                        <p class="mt-2 text-muted-foreground font-medium text-lg italic">
                            {{ selected_survey_id ? 'Viewing demographics for respondents of the selected survey.' : 'Visualizing your entire researcher database.' }}
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap gap-4">
                        <div class="bg-card border border-border p-4 rounded-3xl shadow-sm flex items-center gap-4 px-8 min-w-[180px]">
                            <div class="h-12 w-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                <Users class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-2xl font-black leading-none">{{ stats.total_users }}</p>
                                <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mt-1">
                                    {{ selected_survey_id ? 'Respondents' : 'Total Users' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Toolbar -->
                <div class="flex items-center gap-4 p-4 bg-muted/20 border border-border rounded-2xl flex-wrap">
                    <div class="flex items-center gap-2 px-2 text-muted-foreground">
                        <Filter class="w-4 h-4" />
                        <span class="text-[10px] font-black uppercase tracking-widest">Filter By:</span>
                    </div>

                    <select 
                        v-model="selectedClient"
                        class="flex-1 min-w-[200px] max-w-xs rounded-xl border-border border p-2.5 bg-background text-foreground font-bold text-sm focus:ring-4 focus:ring-indigo-500/10 transition-all"
                    >
                        <option value="">All Clients (Entire Database)</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <select 
                        v-model="selectedSurvey"
                        class="flex-1 min-w-[200px] max-w-md rounded-xl border-border border p-2.5 bg-background text-foreground font-bold text-sm focus:ring-4 focus:ring-indigo-500/10 transition-all"
                    >
                        <option value="">All Surveys</option>
                        <option v-for="s in surveys" :key="s.id" :value="s.id">{{ s.title }}</option>
                    </select>

                    <button 
                        v-if="selectedSurvey || selectedClient"
                        @click="clearFilter"
                        class="p-2.5 rounded-xl bg-background border border-border text-muted-foreground hover:text-red-500 transition-all"
                        title="Clear Filter"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid gap-8 lg:grid-cols-12">
                
                <!-- Gender Dist -->
                <div class="lg:col-span-4 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <User class="w-4 h-4 text-indigo-500" />
                        Gender Split
                    </h3>
                    <div class="h-[250px] relative">
                        <Pie :data="genderChart" :options="chartOptions" />
                    </div>
                </div>

                <!-- Age Dist -->
                <div class="lg:col-span-8 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <TrendingUp class="w-4 h-4 text-indigo-500" />
                        Age Distribution
                    </h3>
                    <div class="h-[250px] relative">
                        <Bar :data="ageChart" :options="barOptions" />
                    </div>
                </div>

                <!-- Income Split -->
                <div class="lg:col-span-4 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <Banknote class="w-4 h-4 text-emerald-500" />
                        Income Brackets
                    </h3>
                    <div class="h-[250px] relative">
                        <Pie :data="incomeChart" :options="chartOptions" />
                    </div>
                </div>

                <!-- Employment Dist -->
                <div class="lg:col-span-4 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <Briefcase class="w-4 h-4 text-blue-500" />
                        Employment Status
                    </h3>
                    <div class="h-[250px] relative">
                        <Pie :data="employmentChart" :options="chartOptions" />
                    </div>
                </div>

                <!-- Education Dist -->
                <div class="lg:col-span-4 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <BookOpen class="w-4 h-4 text-purple-500" />
                        Education Level
                    </h3>
                    <div class="h-[250px] relative">
                        <Bar :data="educationChart" :options="barOptions" />
                    </div>
                </div>

                <!-- Locations & Occupations -->
                <div class="lg:col-span-6 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <MapPin class="w-4 h-4 text-rose-500" />
                        Top Locations
                    </h3>
                    <div class="space-y-4">
                        <div v-for="loc in demographics.location" :key="loc.location" class="flex items-center justify-between p-4 rounded-2xl bg-muted/30 border border-border/50">
                            <span class="text-xs font-black uppercase tracking-tight text-foreground">{{ loc.location }}</span>
                            <span class="text-xs font-black text-rose-600 bg-rose-50 dark:bg-rose-900/30 px-2 py-1 rounded-lg">{{ loc.count }} users</span>
                        </div>
                        <div v-if="demographics.location.length === 0" class="py-8 text-center text-muted-foreground italic text-xs">
                            No location data available.
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                        <Briefcase class="w-4 h-4 text-amber-500" />
                        Top Occupations
                    </h3>
                    <div class="space-y-4">
                        <div v-for="occ in demographics.occupation" :key="occ.occupation" class="flex items-center justify-between p-4 rounded-2xl bg-muted/30 border border-border/50">
                            <span class="text-xs font-black uppercase tracking-tight text-foreground">{{ occ.occupation }}</span>
                            <span class="text-xs font-black text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-lg">{{ occ.count }} users</span>
                        </div>
                        <div v-if="demographics.occupation.length === 0" class="py-8 text-center text-muted-foreground italic text-xs">
                            No occupation data available.
                        </div>
                    </div>
                </div>

                <!-- Custom Traits Table -->
                <div class="lg:col-span-8 bg-card border border-border rounded-[3rem] p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] flex items-center gap-2">
                            <Star class="w-4 h-4 text-amber-500" />
                            Top Custom Traits
                        </h3>
                        <span class="text-[10px] font-black text-muted-foreground uppercase bg-muted px-2 py-1 rounded">Extracted from surveys</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="(count, trait) in traits" :key="trait" class="flex items-center justify-between p-4 rounded-2xl bg-muted/30 border border-border/50">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg bg-background border border-border flex items-center justify-center">
                                    <Target class="w-4 h-4 text-indigo-400" />
                                </div>
                                <span class="text-xs font-black uppercase tracking-tight text-foreground">{{ trait }}</span>
                            </div>
                            <span class="text-xs font-black text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-lg">{{ count }} users</span>
                        </div>
                        <div v-if="Object.keys(traits).length === 0" class="sm:col-span-2 py-12 text-center text-muted-foreground italic text-sm">
                            No custom traits found for this selection.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
