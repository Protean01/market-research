<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Loader2, Users, Sparkles, Target, Filter, RefreshCw, 
    SlidersHorizontal, Plus, Trash2, Tag
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    surveys: any[];
    available_traits: string[];
    clients: any[];
    selected_client_id: string | number | null;
}>();

const selectedClient = ref(props.selected_client_id || '');

watch(selectedClient, (newVal) => {
    router.get('/admin/targeting', { client_id: newVal }, {
        preserveState: true,
        replace: true
    });
});

const editingSurveyId = ref<number | null>(null);

const form = useForm({
    target_gender: '',
    target_age_band: '',
    target_location: '',
    target_language: '',
    target_employment: '',
    target_income_band: '',
    target_traits: [] as any[],
    exclude_traits: [] as any[],
});

const editTargeting = (survey: any) => {
    editingSurveyId.value = survey.id;
    form.target_gender = survey.target_gender || '';
    form.target_age_band = survey.target_age_band || '';
    form.target_location = survey.target_location || '';
    form.target_language = survey.target_language || '';
    form.target_employment = survey.target_employment || '';
    form.target_income_band = survey.target_income_band || '';
    form.target_traits = Array.isArray(survey.target_traits) ? JSON.parse(JSON.stringify(survey.target_traits)) : [];
    form.exclude_traits = Array.isArray(survey.exclude_traits) ? JSON.parse(JSON.stringify(survey.exclude_traits)) : [];
    estimateAudience();
};

const addTrait = (type: 'include' | 'exclude') => {
    const list = type === 'include' ? form.target_traits : form.exclude_traits;
    list.push({ key: '', operator: 'eq', value: '' });
};

const removeTrait = (type: 'include' | 'exclude', index: number) => {
    const list = type === 'include' ? form.target_traits : form.exclude_traits;
    list.splice(index, 1);
};

const estimation = ref({ matched: 0, total: 0, percentage: 0 });
const isEstimating = ref(false);
let debounceTimeout: any = null;

const estimateAudience = async () => {
    if (editingSurveyId.value === null) {
return;
}

    isEstimating.value = true;

    try {
        const response = await axios.post(
            route('admin.targeting.estimate', {}),
            {
                target_gender: form.target_gender,
                target_age_band: form.target_age_band,
                target_location: form.target_location,
                target_language: form.target_language,
                target_employment: form.target_employment,
                target_income_band: form.target_income_band,
                target_traits: form.target_traits,
                exclude_traits: form.exclude_traits,
            },
        );
        estimation.value = response.data;
    } catch (e) {
        console.error('Audience estimation failed', e);
    } finally {
        isEstimating.value = false;
    }
};

watch(
    () => form.data(),
    () => {
        if (editingSurveyId.value !== null) {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(estimateAudience, 500);
        }
    },
    { deep: true },
);

const cancelEdit = () => {
    editingSurveyId.value = null;
    form.reset();
    estimation.value = { matched: 0, total: 0, percentage: 0 };
};

const saveTargeting = (surveyId: number) => {
    form.put(route('admin.targeting.update', surveyId), {
        onSuccess: () => cancelEdit(),
    });
};

const applyPreset = (preset: 'all' | 'young' | 'urban') => {
    if (preset === 'all') {
        form.reset();
    }

    if (preset === 'young') {
        form.target_age_band = '18-24';
        form.target_gender = '';
        form.target_employment = 'student';
        form.target_location = '';
        form.target_income_band = '';
    }

    if (preset === 'urban') {
        form.target_location = 'Lusaka';
        form.target_employment = 'employed_full_time';
        form.target_income_band = 'medium';
        form.target_age_band = '';
        form.target_gender = '';
    }

    estimateAudience();
};

const clearFilters = () => {
    form.reset();
    estimation.value = { matched: 0, total: 0, percentage: 0 };
};

const surveysWithRules = computed(() =>
    props.surveys.filter(
        (s) =>
            s.target_gender ||
            s.target_age_band ||
            s.target_location ||
            s.target_language ||
            s.target_employment ||
            s.target_income_band,
    ).length,
);

const activeSurveys = computed(() => props.surveys.filter((s) => s.is_active).length);
</script>

<template>
    <Head title="Admin · Targeting" />
    <AppLayout>
        <div class="space-y-8 p-8 max-w-7xl mx-auto w-full">
            <header class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-2">
                        <Target class="w-4 h-4" /> Precision Targeting
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-foreground mt-1">Audience Filters</h1>
                    <p class="text-sm font-medium text-muted-foreground mt-1">
                        Guide surveys to the right members and see expected reach instantly.
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="selectedClient" class="rounded-xl border-border border px-4 py-2 bg-background text-sm font-bold w-48 text-foreground focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Clients</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                    </select>

                    <div class="flex gap-3">
                        <div class="px-4 py-3 rounded-2xl bg-card border border-border text-left">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Active Surveys</p>
                            <p class="text-xl font-black text-foreground">{{ activeSurveys }}</p>
                        </div>
                        <div class="px-4 py-3 rounded-2xl bg-card border border-border text-left">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">With Rules</p>
                            <p class="text-xl font-black text-foreground">{{ surveysWithRules }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <section class="rounded-2xl border border-border bg-card p-5 flex flex-wrap gap-3 items-center">
                <div class="flex items-center gap-2 text-sm font-bold text-foreground">
                    <Filter class="w-4 h-4 text-indigo-600" />
                    Quick presets
                </div>
                <button @click="applyPreset('all')" class="px-3 py-2 rounded-full text-xs font-black bg-muted hover:bg-muted/70 text-foreground">All users</button>
                <button @click="applyPreset('young')" class="px-3 py-2 rounded-full text-xs font-black bg-indigo-600 text-white hover:bg-indigo-500">Young adults</button>
                <button @click="applyPreset('urban')" class="px-3 py-2 rounded-full text-xs font-black bg-emerald-600 text-white hover:bg-emerald-500">Urban employed</button>
                <button @click="clearFilters" class="ml-auto inline-flex items-center gap-2 text-xs font-bold text-muted-foreground hover:text-foreground">
                    <RefreshCw class="w-4 h-4" /> Clear all
                </button>
            </section>

            <div class="space-y-4">
                <div v-for="survey in surveys" :key="survey.id" class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-muted-foreground flex items-center gap-2">
                                <SlidersHorizontal class="w-4 h-4" /> Targeting 
                                <span v-if="survey.client" class="text-indigo-500 border-l border-border pl-2 ml-1">{{ survey.client.name }}</span>
                            </p>
                            <h3 class="text-xl font-black text-foreground">{{ survey.title }}</h3>
                            <span
                                class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full mt-2"
                                :class="survey.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'"
                            >
                                {{ survey.is_active ? 'Active' : 'Closed' }}
                            </span>
                        </div>
                        <button
                            v-if="editingSurveyId !== survey.id"
                            @click="editTargeting(survey)"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-black uppercase tracking-[0.15em] hover:bg-indigo-500"
                        >
                            <Sparkles class="w-4 h-4" /> Edit rules
                        </button>
                    </div>

                    <div v-if="editingSurveyId === survey.id" class="mt-5 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-muted-foreground">Gender</label>
                                <select v-model="form.target_gender" class="w-full rounded-2xl border border-border bg-background px-3 py-2 text-sm">
                                    <option value="">Any</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-muted-foreground">Age Band</label>
                                <select v-model="form.target_age_band" class="w-full rounded-2xl border border-border bg-background px-3 py-2 text-sm">
                                    <option value="">Any</option>
                                    <option value="18-24">18-24</option>
                                    <option value="25-34">25-34</option>
                                    <option value="35-44">35-44</option>
                                    <option value="45-54">45-54</option>
                                    <option value="55+">55+</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-muted-foreground">Location</label>
                                <input v-model="form.target_location" type="text" placeholder="e.g. Lusaka, Ndola" class="w-full rounded-2xl border border-border bg-background px-3 py-2 text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-muted-foreground">Language</label>
                                <input v-model="form.target_language" type="text" placeholder="e.g. English, Bemba" class="w-full rounded-2xl border border-border bg-background px-3 py-2 text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-muted-foreground">Employment Status</label>
                                <select v-model="form.target_employment" class="w-full rounded-2xl border border-border bg-background px-3 py-2 text-sm">
                                    <option value="">Any</option>
                                    <option value="employed_full_time">Employed Full-time</option>
                                    <option value="employed_part_time">Employed Part-time</option>
                                    <option value="self_employed">Self-employed</option>
                                    <option value="unemployed">Unemployed</option>
                                    <option value="student">Student</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-muted-foreground">Income Band</label>
                                <select v-model="form.target_income_band" class="w-full rounded-2xl border border-border bg-background px-3 py-2 text-sm">
                                    <option value="">Any</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <!-- Advanced Traits Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-border pt-6 mt-6">
                            <!-- Included Traits -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-black uppercase tracking-widest text-indigo-600 flex items-center gap-2">
                                        <Plus class="w-4 h-4" /> Include Traits
                                    </h4>
                                    <button @click="addTrait('include')" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">Add Rule</button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(trait, index) in form.target_traits" :key="index" class="flex gap-2 items-center bg-muted/30 p-3 rounded-2xl border border-border/50">
                                        <select v-model="trait.key" class="bg-background border-border border rounded-xl px-2 py-1.5 text-xs font-bold flex-1">
                                            <option value="">Select Trait</option>
                                            <option v-for="t in available_traits" :key="t" :value="t">{{ t }}</option>
                                        </select>
                                        <select v-model="trait.operator" class="bg-background border-border border rounded-xl px-2 py-1.5 text-xs font-bold w-20">
                                            <option value="eq">is</option>
                                            <option value="neq">is not</option>
                                        </select>
                                        <input v-model="trait.value" type="text" placeholder="Value" class="bg-background border-border border rounded-xl px-2 py-1.5 text-xs font-bold flex-1">
                                        <button @click="removeTrait('include', index)" class="p-1.5 text-muted-foreground hover:text-red-500">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div v-if="form.target_traits.length === 0" class="text-center py-4 rounded-2xl border border-dashed border-border text-[11px] font-medium text-muted-foreground">
                                        No inclusion traits defined.
                                    </div>
                                </div>
                            </div>

                            <!-- Excluded Traits -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-black uppercase tracking-widest text-rose-600 flex items-center gap-2">
                                        <Trash2 class="w-4 h-4" /> Exclude Traits
                                    </h4>
                                    <button @click="addTrait('exclude')" class="text-xs font-bold text-rose-600 hover:text-rose-500">Add Rule</button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(trait, index) in form.exclude_traits" :key="index" class="flex gap-2 items-center bg-muted/30 p-3 rounded-2xl border border-border/50">
                                        <select v-model="trait.key" class="bg-background border-border border rounded-xl px-2 py-1.5 text-xs font-bold flex-1">
                                            <option value="">Select Trait</option>
                                            <option v-for="t in available_traits" :key="t" :value="t">{{ t }}</option>
                                        </select>
                                        <select v-model="trait.operator" class="bg-background border-border border rounded-xl px-2 py-1.5 text-xs font-bold w-20">
                                            <option value="eq">is</option>
                                            <option value="neq">is not</option>
                                        </select>
                                        <input v-model="trait.value" type="text" placeholder="Value" class="bg-background border-border border rounded-xl px-2 py-1.5 text-xs font-bold flex-1">
                                        <button @click="removeTrait('exclude', index)" class="p-1.5 text-muted-foreground hover:text-red-500">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div v-if="form.exclude_traits.length === 0" class="text-center py-4 rounded-2xl border border-dashed border-border text-[11px] font-medium text-muted-foreground">
                                        No exclusion traits defined.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-indigo-200 dark:border-indigo-900/40 bg-indigo-50/60 dark:bg-indigo-900/10 p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-white/70 dark:bg-indigo-900/30 p-2.5">
                                    <Users class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-foreground flex items-center gap-2">
                                        Audience estimate
                                        <Loader2 v-if="isEstimating" class="w-4 h-4 animate-spin text-muted-foreground" />
                                    </p>
                                    <p class="text-xs font-medium text-muted-foreground">Live preview updates as you tweak filters</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-black text-indigo-600 dark:text-indigo-300">
                                    ~{{ (estimation.matched ?? 0).toLocaleString() }}
                                </p>
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
                                    {{ (estimation.percentage ?? 0).toFixed(1) }}% of network
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-border pt-4">
                            <button type="button" @click="cancelEdit" class="px-4 py-2 rounded-xl bg-muted text-foreground text-sm font-bold">Cancel</button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                @click.prevent="saveTargeting(survey.id)"
                                class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-black uppercase tracking-[0.12em] disabled:opacity-60"
                            >
                                Save rules
                            </button>
                        </div>
                    </div>

                    <div v-else class="mt-4 flex flex-wrap gap-2 text-sm">
                        <span v-if="survey.target_gender" class="px-3 py-1.5 rounded-full bg-muted border border-border font-semibold">Gender: {{ survey.target_gender }}</span>
                        <span v-if="survey.target_age_band" class="px-3 py-1.5 rounded-full bg-muted border border-border font-semibold">Age: {{ survey.target_age_band }}</span>
                        <span v-if="survey.target_location" class="px-3 py-1.5 rounded-full bg-muted border border-border font-semibold">Location: {{ survey.target_location }}</span>
                        <span v-if="survey.target_language" class="px-3 py-1.5 rounded-full bg-muted border border-border font-semibold">Language: {{ survey.target_language }}</span>
                        <span v-if="survey.target_employment" class="px-3 py-1.5 rounded-full bg-muted border border-border font-semibold">Employment: {{ survey.target_employment }}</span>
                        <span v-if="survey.target_income_band" class="px-3 py-1.5 rounded-full bg-muted border border-border font-semibold text-xs">Income: {{ survey.target_income_band }}</span>
                        
                        <!-- Display Traits -->
                        <template v-if="survey.target_traits && survey.target_traits.length > 0">
                            <span v-for="(t, i) in survey.target_traits" :key="'inc-'+i" class="px-3 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 font-semibold text-xs flex items-center gap-1">
                                <Tag class="w-3 h-3" /> {{ t.key }} {{ t.operator === 'eq' ? '=' : '!=' }} {{ t.value }}
                            </span>
                        </template>
                        <template v-if="survey.exclude_traits && survey.exclude_traits.length > 0">
                            <span v-for="(t, i) in survey.exclude_traits" :key="'exc-'+i" class="px-3 py-1.5 rounded-full bg-rose-50 border border-rose-100 text-rose-700 font-semibold text-xs flex items-center gap-1">
                                <Trash2 class="w-3 h-3 text-rose-400" /> {{ t.key }} {{ t.operator === 'eq' ? '!=' : '=' }} {{ t.value }}
                            </span>
                        </template>

                        <span
                            v-if="
                                !survey.target_gender &&
                                !survey.target_age_band &&
                                !survey.target_location &&
                                !survey.target_language &&
                                !survey.target_employment &&
                                !survey.target_income_band &&
                                (!survey.target_traits || survey.target_traits.length === 0) &&
                                (!survey.exclude_traits || survey.exclude_traits.length === 0)
                            "
                            class="italic text-muted-foreground text-xs"
                        >
                            No targeting rules applied (all users).
                        </span>
                    </div>
                </div>

                <div v-if="surveys.length === 0" class="text-center py-8 text-muted-foreground border border-border rounded-2xl bg-card">
                    No surveys found.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
