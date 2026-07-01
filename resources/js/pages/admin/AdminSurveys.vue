<script setup lang="ts">
import { Head, router, useForm, usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { Activity, AlertCircle, ClipboardList, Clock, Coins, Edit2, Plus, Save, ToggleLeft, ToggleRight, Trash2, Users, X, Eye, Copy, BookOpen, BarChart3, ChevronDown, ChevronUp, GripVertical, Trophy, UserPlus, Target, ArrowRight, Gift } from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { ref, onMounted, reactive, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import { route } from 'ziggy-js';
import SurveySimulator from '@/components/admin/SurveySimulator.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Question } from '@/types/survey';

defineProps<{
    surveys: any[];
    clients: any[];
}>();

const isEditing = ref(false);
const showFormPanel = ref(false);
const showSimulationModal = ref(false);
const questionBank = ref<any[]>([]);
const showBankModal = ref(false);
const showQrModal = ref(false);
const collapsedQuestions = reactive<Record<string, boolean>>({});
const activeTabs = reactive<Record<string, 'content' | 'logic'>>({});

const page = usePage();
const isAdmin = computed(() => !!page.props.auth.is_admin);

const previewUrl = computed(() => {
    if (!form.id) {
return '';
}

    return window.location.origin + '/surveys/' + form.id;
});

const toggleQuestionCollapse = (id: string) => {
    collapsedQuestions[id] = !collapsedQuestions[id];
};

const setQuestionTab = (id: string, tab: 'content' | 'logic') => {
    activeTabs[id] = tab;
};

const hasBranching = (question: any) => {
    return question.logic && Object.keys(question.logic).length > 0;
};

const hasVisibility = (question: any) => {
    return question.visibility && question.visibility.conditions?.length > 0;
};

const expandAllQuestions = () => {
    form.questions.forEach(q => collapsedQuestions[q.id] = false);
};

const collapseAllQuestions = () => {
    form.questions.forEach(q => collapsedQuestions[q.id] = true);
};

onMounted(async () => {
    const res = await axios.get('/admin/api/question-bank');
    questionBank.value = res.data;
});

type Prize = { name: string; amount: number; points: number };

const defaultPrize = (type: string): Prize =>
    type === 'airtime'
        ? { name: 'Airtime Reward', amount: 10, points: 0 }
        : { name: 'Grand Prize', amount: 0, points: 100 };

const form = useForm({
    id: null as number | null,
    title: '',
    description: '',
    reward_amount: 0,
    reward_points: 0,
    reward_type: 'points',
    prize_name: '',
    prizes: [] as Prize[],
    response_cap: 100,
    estimated_time: 5,
    is_active: false,
    client_id: '' as string | number,
    target_gender: '',
    target_age_band: '',
    target_location: '',
    target_employment: '',
    target_income_band: '',
    target_traits: [] as { key: string; value: string; operator?: string }[],
    exclude_traits: [] as { key: string; value: string; operator?: string }[],
    enrichment_questions: [] as any[],
    questions: [
        { id: Date.now().toString() + Math.random().toString(36).substr(2, 5), text: '', hint: '', type: 'mcq', options: ['Option 1', 'Option 2'], required: true, logic: {} }
    ] as Question[]
});

watch(() => form.reward_type, (type) => {
    if ((type === 'airtime' || type === 'prize_draw') && form.prizes.length === 0) {
        form.prizes = [defaultPrize(type)];
    }
});

const estimatedReach = ref<number | null>(null);
const isEstimating = ref(false);

const estimateReach = debounce(async () => {
    isEstimating.value = true;

    try {
        const res = await axios.post(route('admin.targeting.estimate', []), {
            target_gender: form.target_gender,
            target_age_band: form.target_age_band,
            target_location: form.target_location,
            target_employment: form.target_employment,
            target_income_band: form.target_income_band,
        });
        estimatedReach.value = res.data?.matched ?? 0;
    } catch (e) {
        console.error('Failed to estimate reach', e);
    } finally {
        isEstimating.value = false;
    }
}, 500);

const showForm = () => {
    isEditing.value = false;
    showFormPanel.value = true;
    form.reset();
    form.clearErrors();
    estimatedReach.value = null;
    estimateReach();
};

watch(() => [
    form.target_gender, 
    form.target_age_band, 
    form.target_location, 
    form.target_employment, 
    form.target_income_band
], () => {
    estimateReach();
}, { deep: true });

const editSurvey = (survey: any) => {
    isEditing.value = true;
    showFormPanel.value = true;
    form.clearErrors();
    form.id = survey.id;
    form.title = survey.title;
    form.description = survey.description || '';
    form.reward_amount = survey.reward_amount;
    form.reward_points = survey.reward_points;
    form.reward_type = survey.reward_type || 'points';
    form.prize_name = survey.prize_name || '';
    form.response_cap = survey.response_cap;
    form.estimated_time = survey.estimated_time || 5;
    form.is_active = !!survey.is_active;
    form.client_id = survey.client_id || '';
    form.target_gender = survey.target_gender || '';
    form.target_age_band = survey.target_age_band || '';
    form.target_location = survey.target_location || '';
    form.target_employment = survey.target_employment || '';
    form.target_income_band = survey.target_income_band || '';
    form.target_traits = survey.target_traits ? JSON.parse(JSON.stringify(survey.target_traits)) : [];
    form.exclude_traits = survey.exclude_traits ? JSON.parse(JSON.stringify(survey.exclude_traits)) : [];
    form.enrichment_questions = survey.enrichment_questions ? JSON.parse(JSON.stringify(survey.enrichment_questions)) : [];

    // Load prizes; fall back to building one from legacy fields if prizes not saved yet
    if (survey.prizes && survey.prizes.length > 0) {
        form.prizes = JSON.parse(JSON.stringify(survey.prizes));
    } else if (survey.reward_type === 'prize_draw') {
        form.prizes = [{ name: survey.prize_name || 'Grand Prize', amount: 0, points: survey.reward_points || 0 }];
    } else if (survey.reward_type === 'airtime') {
        form.prizes = [{ name: 'Airtime Reward', amount: survey.reward_amount || 0, points: 0 }];
    } else {
        form.prizes = [];
    }

    form.questions = survey.questions ? JSON.parse(JSON.stringify(survey.questions)) : [];
    
    // Ensure every question has a logic object to prevent TypeErrors in the UI
    form.questions.forEach((q: any) => {
        if (!q.logic || Array.isArray(q.logic)) {
q.logic = {};
}
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelForm = () => {
    isEditing.value = false;
    showFormPanel.value = false;
    form.id = null;
    form.reset();
};

const addQuestion = () => {
    form.questions.push({ 
        id: Date.now().toString() + Math.random().toString(36).substr(2, 5), 
        text: '', 
        hint: '',
        type: 'mcq', 
        options: ['Option 1', 'Option 2'], 
        required: true, 
        logic: {} // Initialize logic object
    });
};

const removeQuestion = (index: number) => {
    form.questions.splice(index, 1);
};

const importFromBank = (template: any) => {
    if (template.questions && Array.isArray(template.questions)) {
        template.questions.forEach((q: any) => {
            form.questions.push({
                id: Date.now().toString() + Math.random().toString(36).substr(2, 5),
                text: q.text,
                hint: q.hint || '',
                type: q.type,
                options: q.options ? JSON.parse(JSON.stringify(q.options)) : [],
                required: true,
                logic: q.logic ? JSON.parse(JSON.stringify(q.logic)) : {} // Ensure logic is object
            });
        });
    }

    showBankModal.value = false;
};

const addPrize = () => {
    form.prizes.push(defaultPrize(form.reward_type));
};

const removePrize = (index: number) => {
    if (form.prizes.length > 1) {
form.prizes.splice(index, 1);
}
};

const addEnrichmentQuestion = () => {
    form.enrichment_questions.push({
        id: '',
        text: '',
        type: 'mcq',
        options: ['Yes', 'No']
    });
};

const removeEnrichmentQuestion = (index: number) => {
    form.enrichment_questions.splice(index, 1);
};

const addEnrichmentOption = (qIdx: number) => {
    if (!form.enrichment_questions[qIdx].options) {
form.enrichment_questions[qIdx].options = [];
}

    form.enrichment_questions[qIdx].options.push(`Option ${form.enrichment_questions[qIdx].options.length + 1}`);
};

const removeEnrichmentOption = (qIdx: number, oIdx: number) => {
    form.enrichment_questions[qIdx].options.splice(oIdx, 1);
};

const addOption = (qIndex: number) => {
    if (!form.questions[qIndex].options) {
        form.questions[qIndex].options = [];
    }

    form.questions[qIndex].options.push(`Option ${form.questions[qIndex].options.length + 1}`);
};

const removeOption = (qIndex: number, oIndex: number) => {
    form.questions[qIndex].options?.splice(oIndex, 1);
};

const onQuestionTypeChange = (question: any) => {
    if (question.type === 'image_mcq') {
        question.options = [
            { label: 'Option 1', image_url: '' },
            { label: 'Option 2', image_url: '' },
        ];
    } else if (
        (question.type === 'mcq' || question.type === 'checkbox') &&
        question.options?.length &&
        typeof question.options[0] === 'object' &&
        'image_url' in question.options[0]
    ) {
        // Only reset when switching FROM image_mcq — preserve custom string options otherwise
        question.options = ['Option 1', 'Option 2'];
    } else if (question.type === 'text' || question.type === 'scale') {
        // Clear options that don't apply to these types
        question.options = [];
    }
};

const addImageOption = (qIndex: number) => {
    const count = form.questions[qIndex].options!.length + 1;
    form.questions[qIndex].options!.push({ label: `Option ${count}`, image_url: '' });
};

const removeImageOption = (qIndex: number, oIndex: number) => {
    if (form.questions[qIndex].options!.length > 2) {
        form.questions[qIndex].options!.splice(oIndex, 1);
    }
};

const uploadOptionImage = async (qIndex: number, oIndex: number) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.style.display = 'none';
    document.body.appendChild(input);

    input.onchange = async (e) => {
        const file = (e.target as HTMLInputElement).files?.[0];

        if (document.body.contains(input)) {
            document.body.removeChild(input);
        }

        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);

        try {
            const { data } = await axios.post(
                route('admin.surveys.images.upload', []),
                formData
            );
            // Replace the options array immutably so Vue reactivity detects the change
            const updated = [...(form.questions[qIndex].options as any[])];
            updated[oIndex] = { ...updated[oIndex], image_url: data.url };
            form.questions[qIndex].options = updated;
        } catch (err) {
            console.error('Image upload failed', err);
        }
    };

    input.addEventListener('cancel', () => {
        if (document.body.contains(input)) {
            document.body.removeChild(input);
        }
    });

    input.click();
};

const addVisibilityRule = (qIndex: number) => {
    if (!form.questions[qIndex].visibility) {
        form.questions[qIndex].visibility = { logic: 'and', conditions: [] };
    }

    form.questions[qIndex].visibility!.conditions.push({ type: 'answer', key: '', operator: 'eq', value: '' });
};

const removeCondition = (qIndex: number, rIndex: number) => {
    form.questions[qIndex].visibility!.conditions.splice(rIndex, 1);

    if (form.questions[qIndex].visibility!.conditions.length === 0) {
        delete form.questions[qIndex].visibility;
    }
};

const addIncludeTrait = () => {
    form.target_traits.push({ key: '', value: '', operator: 'eq' });
};

const addExcludeTrait = () => {
    form.exclude_traits.push({ key: '', value: '', operator: 'eq' });
};

const removeIncludeTrait = (idx: number) => {
    form.target_traits.splice(idx, 1);
};

const removeExcludeTrait = (idx: number) => {
    form.exclude_traits.splice(idx, 1);
};

const submitForm = () => {
    if (form.id) {
        form.put(route('admin.surveys.update', form.id), {
            onSuccess: () => cancelForm(),
        });
    } else {
        form.post(route('admin.surveys.store', []), {
            onSuccess: () => cancelForm(),
        });
    }
};

const toggleStatus = (surveyId: number) => {
    router.post(route('admin.surveys.toggle', surveyId));
};

const startDrawPhase = (surveyId: number) => {
    if (confirm('Start the prize draw phase? This will notify all respondents.')) {
        router.post(route('admin.surveys.start-draw', surveyId));
    }
};

const cloneSurvey = (surveyId: number) => {
    router.post(route('admin.surveys.clone', surveyId));
};

const deleteSurvey = (surveyId: number) => {
    if (confirm('Are you sure you want to delete this survey? All associated responses will be lost.')) {
        router.delete(route('admin.surveys.destroy', surveyId));
    }
};
</script>

<template>
    <Head title="Admin · Survey Builder" />
    <AppLayout>
        <div class="space-y-8 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3">
                        <ClipboardList class="w-8 h-8 text-indigo-600" />
                        Survey Builder
                    </h1>
                    <p class="text-base font-medium text-muted-foreground mt-1">
                        Design and manage data collection campaigns.
                    </p>
                </div>
                <button 
                    v-if="!showFormPanel" 
                    @click="showForm" 
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all active:scale-95"
                >
                    <Plus class="w-4 h-4" />
                    Create New Survey
                </button>
            </div>

            <!-- Form Panel -->
            <div v-if="showFormPanel" class="rounded-3xl border border-border bg-card shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="grid grid-cols-1 xl:grid-cols-12">
                    <!-- Left: Form -->
                    <div class="xl:col-span-12 p-8 border-border">
                        <div class="flex items-center justify-between mb-8 pb-6 border-b border-border">
                            <div>
                                <h2 class="text-2xl font-black text-foreground">{{ isEditing ? 'Edit Survey' : 'New Survey' }}</h2>
                                <p class="text-xs font-black text-foreground uppercase tracking-widest mt-0.5 opacity-70">Configuration & Questions</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button 
                                    v-if="form.questions.length > 0"
                                    type="button"
                                    @click="showSimulationModal = true" 
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-indigo-300 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/30 text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-400 hover:bg-indigo-100 transition-all"
                                >
                                    <Activity class="w-3.5 h-3.5" />
                                    Test Survey Logic
                                </button>
                                
                                <button @click="cancelForm" class="p-2 hover:bg-muted rounded-full transition-colors">
                                    <X class="w-5 h-5 text-foreground" />
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-10">
                            <!-- Section: General Info -->
                            <section class="space-y-6">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="h-6 w-1 bg-indigo-600 rounded-full"></div>
                                    <h3 class="text-sm font-black uppercase tracking-widest text-foreground">General Information</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div v-if="isAdmin" class="md:col-span-2">
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Research Client (Partner)</label>
                                        <select v-model="form.client_id" class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                            <option value="">No Client (Platform Owned)</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                        <p class="text-[10px] text-muted-foreground mt-2 font-bold uppercase tracking-tight">Link this survey to a specific client to organize your data and question banks.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Campaign Title</label>
                                        <input v-model="form.title" type="text" placeholder="e.g. Consumer Habits 2026" class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                                        <div v-if="form.errors.title" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1"><AlertCircle class="w-3 h-3"/> {{ form.errors.title }}</div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Description</label>
                                        <textarea v-model="form.description" rows="3" placeholder="Tell users what this survey is about..." class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></textarea>
                                        <div v-if="form.errors.description" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1"><AlertCircle class="w-3 h-3"/> {{ form.errors.description }}</div>
                                    </div>
                                </div>
                            </section>

                            <!-- Section: Rewards & Caps -->
                            <section class="space-y-6">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="h-6 w-1 bg-emerald-600 rounded-full"></div>
                                    <h3 class="text-sm font-black uppercase tracking-widest text-foreground">Rewards & Distribution</h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Reward Type</label>
                                        <select v-model="form.reward_type" class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-bold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                            <option value="points">Points Reward</option>
                                            <option value="airtime">Direct Airtime</option>
                                            <option value="prize_draw">Prize Draw Entry</option>
                                        </select>
                                    </div>

                                    <!-- Points awarded (always shown — every survey gives participation points) -->
                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Points Awarded</label>
                                        <div class="relative">
                                            <Coins class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-amber-500" />
                                            <input v-model="form.reward_points" type="number" min="0" class="w-full rounded-xl border-border border p-3 pl-10 bg-background text-foreground font-bold focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" required>
                                        </div>
                                        <p class="text-[10px] text-muted-foreground font-medium mt-1">Points every participant earns on completion.</p>
                                        <div v-if="form.errors.reward_points" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1"><AlertCircle class="w-3 h-3"/> {{ form.errors.reward_points }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Response Cap</label>
                                        <div class="relative">
                                            <Users class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-500" />
                                            <input v-model="form.response_cap" type="number" class="w-full rounded-xl border-border border p-3 pl-10 bg-background text-foreground font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                                        </div>
                                        <div v-if="form.errors.response_cap" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1"><AlertCircle class="w-3 h-3"/> {{ form.errors.response_cap }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-widest text-foreground mb-2 opacity-80">Estimated Time (mins)</label>
                                        <div class="relative">
                                            <Clock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald-500" />
                                            <input v-model="form.estimated_time" type="number" min="1" class="w-full rounded-xl border-border border p-3 pl-10 bg-background text-foreground font-bold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" required>
                                        </div>
                                        <div v-if="form.errors.estimated_time" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1"><AlertCircle class="w-3 h-3"/> {{ form.errors.estimated_time }}</div>
                                    </div>
                                </div>

                                <!-- Multi-prize builder for prize_draw and airtime -->
                                <div v-if="form.reward_type === 'prize_draw' || form.reward_type === 'airtime'" class="rounded-2xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-900/10 p-6 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <Gift class="w-4 h-4 text-amber-600" />
                                            <h4 class="text-xs font-black uppercase tracking-widest text-amber-700 dark:text-amber-400">
                                                {{ form.reward_type === 'prize_draw' ? 'Prize Draw Prizes' : 'Airtime Prize Tiers' }}
                                            </h4>
                                        </div>
                                        <button type="button" @click="addPrize" class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-700 transition-all">
                                            <Plus class="w-3 h-3" /> Add Prize
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div
                                            v-for="(prize, pIdx) in form.prizes"
                                            :key="pIdx"
                                            class="grid grid-cols-12 gap-3 items-center bg-background rounded-xl border border-amber-100 dark:border-amber-900/30 p-4"
                                        >
                                            <div class="col-span-1 flex items-center justify-center">
                                                <span class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-black flex items-center justify-center">
                                                    {{ pIdx + 1 }}
                                                </span>
                                            </div>

                                            <!-- Prize name -->
                                            <div class="col-span-5">
                                                <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1">Prize Label</label>
                                                <input
                                                    v-model="prize.name"
                                                    type="text"
                                                    :placeholder="form.reward_type === 'airtime' ? 'e.g. Grand Prize' : 'e.g. iPhone 15 Pro'"
                                                    class="w-full rounded-lg border border-border p-2.5 bg-background text-foreground text-sm font-bold focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all"
                                                />
                                            </div>

                                            <!-- Amount (airtime) or Points (prize_draw) -->
                                            <div class="col-span-5">
                                                <template v-if="form.reward_type === 'airtime'">
                                                    <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1">Amount (ZMW)</label>
                                                    <div class="relative">
                                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-black text-muted-foreground">K</span>
                                                        <input v-model.number="prize.amount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-border p-2.5 pl-7 bg-background text-foreground text-sm font-bold focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1">Points Value</label>
                                                    <div class="relative">
                                                        <Coins class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-amber-500" />
                                                        <input v-model.number="prize.points" type="number" min="0" class="w-full rounded-lg border border-border p-2.5 pl-8 bg-background text-foreground text-sm font-bold focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="col-span-1 flex justify-end">
                                                <button
                                                    type="button"
                                                    @click="removePrize(pIdx)"
                                                    :disabled="form.prizes.length === 1"
                                                    class="p-1.5 text-muted-foreground hover:text-red-500 hover:bg-red-50 rounded-lg transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                                >
                                                    <Trash2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="text-[10px] text-amber-600/70 font-bold italic">
                                        {{ form.reward_type === 'airtime' ? 'Each prize tier can have a different airtime value. The slot machine picks one per winner.' : 'Add multiple prizes to be drawn separately (1st, 2nd, 3rd place etc.).' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 bg-muted/50 p-4 rounded-2xl border border-border">
                                    <input v-model="form.is_active" type="checkbox" id="is_active" class="w-5 h-5 rounded border-border text-emerald-600 focus:ring-emerald-500" />
                                    <label for="is_active" class="flex flex-col">
                                        <span class="text-sm font-black text-foreground uppercase tracking-tight">Publish Automatically</span>
                                        <span class="text-[10px] text-muted-foreground font-black uppercase tracking-widest">Make this survey live and visible to users immediately after saving.</span>
                                    </label>
                                </div>
                            </section>

                            <!-- Section: Targeting -->
                            <section class="space-y-6">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-1 bg-blue-600 rounded-full"></div>
                                        <h3 class="text-sm font-black uppercase tracking-widest text-foreground">Targeting & Reach</h3>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-xl border border-blue-100 dark:border-blue-900/50">
                                        <Users class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                        <div class="flex flex-col">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-blue-600/70 dark:text-blue-400/70">Estimated Reach</span>
                                            <span v-if="isEstimating" class="text-sm font-black text-blue-700 dark:text-blue-300">Calculating...</span>
                                            <span v-else class="text-sm font-black text-blue-700 dark:text-blue-300">{{ estimatedReach !== null && estimatedReach !== undefined ? estimatedReach.toLocaleString() + ' Users' : '---' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <input v-model="form.target_gender" placeholder="Gender (e.g. Female)" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                                    <input v-model="form.target_age_band" placeholder="Age band (e.g. 18-35)" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                                    <input v-model="form.target_location" placeholder="Location (e.g. Lusaka)" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                                    <input v-model="form.target_employment" placeholder="Employment" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                                    <input v-model="form.target_income_band" placeholder="Income band" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="rounded-2xl border border-border p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-xs font-black uppercase tracking-widest text-foreground">Must match (enriched answers)</h4>
                                            <button type="button" class="text-xs font-bold text-indigo-600 hover:underline" @click="addIncludeTrait">+ Add</button>
                                        </div>
                                        <div v-if="!form.target_traits.length" class="text-xs text-muted-foreground">No required traits</div>
                                        <div v-for="(trait, idx) in form.target_traits" :key="'inc-'+idx" class="flex items-center gap-2 mb-2">
                                            <input v-model="trait.key" placeholder="Key (e.g. eat_pork)" class="flex-1 rounded-lg border border-border p-2 text-sm" />
                                            <input v-model="trait.value" placeholder="Value (e.g. yes)" class="flex-1 rounded-lg border border-border p-2 text-sm" />
                                            <select v-model="trait.operator" class="rounded-lg border border-border p-2 text-sm">
                                                <option value="eq">=</option>
                                                <option value="neq">≠</option>
                                            </select>
                                            <button type="button" class="text-xs text-red-600 font-bold" @click="removeIncludeTrait(idx)">Remove</button>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-border p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-xs font-black uppercase tracking-widest text-foreground">Must NOT match</h4>
                                            <button type="button" class="text-xs font-bold text-indigo-600 hover:underline" @click="addExcludeTrait">+ Add</button>
                                        </div>
                                        <div v-if="!form.exclude_traits.length" class="text-xs text-muted-foreground">No exclusions</div>
                                        <div v-for="(trait, idx) in form.exclude_traits" :key="'exc-'+idx" class="flex items-center gap-2 mb-2">
                                            <input v-model="trait.key" placeholder="Key" class="flex-1 rounded-lg border border-border p-2 text-sm" />
                                            <input v-model="trait.value" placeholder="Value" class="flex-1 rounded-lg border border-border p-2 text-sm" />
                                            <select v-model="trait.operator" class="rounded-lg border border-border p-2 text-sm">
                                                <option value="eq">=</option>
                                                <option value="neq">≠</option>
                                            </select>
                                            <button type="button" class="text-xs text-red-600 font-bold" @click="removeExcludeTrait(idx)">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Section: Profile Tagging (Permanent Traits) -->
                            <section class="space-y-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                            <UserPlus class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-black uppercase tracking-widest text-foreground">Permanent Profile Tagging</h3>
                                            <p class="text-[10px] text-muted-foreground font-medium italic">These answers are saved to the user's permanent profile for future targeting.</p>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="addEnrichmentQuestion" 
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-amber-700 shadow-lg shadow-amber-600/20 transition-all active:scale-95"
                                    >
                                        <Plus class="w-4 h-4" />
                                        Add Profiling Question
                                    </button>
                                </div>

                                <div class="grid gap-4">
                                    <div v-for="(q, qIdx) in form.enrichment_questions" :key="'enr-'+qIdx" class="relative p-8 border border-amber-200 dark:border-amber-900/30 rounded-[2rem] bg-amber-50/10 animate-in zoom-in-95 duration-300">
                                        <button type="button" @click="removeEnrichmentQuestion(qIdx)" class="absolute top-6 right-6 p-2 text-amber-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                            <Trash2 class="w-4.5 h-4.5" />
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                                            <div class="md:col-span-4 space-y-4">
                                                <div>
                                                    <label class="block text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-1.5 ml-1">Database Key</label>
                                                    <input v-model="q.id" placeholder="e.g. car_owner" class="w-full rounded-xl border-amber-200 dark:border-amber-900/50 border p-3 bg-background text-foreground font-black text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all" />
                                                    <p class="text-[8px] text-amber-600/60 font-bold uppercase mt-1.5 ml-1 tracking-tighter">No spaces allowed</p>
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-1.5 ml-1">Label</label>
                                                    <input v-model="q.text" placeholder="e.g. Do you own a car?" class="w-full rounded-xl border-amber-200 dark:border-amber-900/50 border p-3 bg-background text-foreground font-bold text-sm focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all" />
                                                </div>
                                            </div>

                                            <div class="md:col-span-8">
                                                <div v-if="q.type === 'mcq'" class="space-y-3">
                                                    <label class="block text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-1.5 ml-1">Trait Values (Dropdown)</label>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                        <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-2 group">
                                                            <div class="flex-1 relative">
                                                                <input v-model="q.options[oIdx]" class="w-full rounded-xl border-amber-100 dark:border-amber-900/30 border p-2.5 bg-background text-foreground text-xs font-bold focus:border-amber-500 focus:ring-0 transition-all" />
                                                            </div>
                                                            <button type="button" @click="removeEnrichmentOption(qIdx, oIdx)" class="p-1.5 text-amber-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                                                <X class="w-3.5 h-3.5" />
                                                            </button>
                                                        </div>
                                                        <button type="button" @click="addEnrichmentOption(qIdx)" class="flex items-center justify-center gap-2 p-2.5 rounded-xl border-2 border-dashed border-amber-200 dark:border-amber-900/30 text-[10px] font-black uppercase tracking-widest text-amber-600 hover:bg-amber-50 transition-all">
                                                            <Plus class="w-3 h-3" /> Add Value
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!form.enrichment_questions.length" class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-border rounded-[3rem] bg-muted/5 opacity-50">
                                    <UserPlus class="w-10 h-10 text-muted-foreground/30 mb-3" />
                                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest text-center leading-relaxed">No profiling questions added.<br>Answers will only be saved to this survey's results.</p>
                                </div>
                            </section>

                            <!-- Section: Questions -->
                            <section class="space-y-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-1 bg-purple-600 rounded-full"></div>
                                        <h3 class="text-sm font-black uppercase tracking-widest text-foreground">Questions ({{ form.questions.length }})</h3>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1 bg-muted px-2 py-1 rounded-lg mr-2">
                                            <button type="button" @click="expandAllQuestions" class="text-[9px] font-black uppercase text-muted-foreground hover:text-indigo-600 transition-colors">Expand All</button>
                                            <span class="text-muted-foreground/30 px-1">|</span>
                                            <button type="button" @click="collapseAllQuestions" class="text-[9px] font-black uppercase text-muted-foreground hover:text-indigo-600 transition-colors">Collapse All</button>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="showBankModal = true" 
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-indigo-100 transition-colors"
                                        >                                            <BookOpen class="w-3.5 h-3.5" />
                                            Import from Bank
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="addQuestion" 
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-purple-200 transition-colors"
                                        >
                                            <Plus class="w-3.5 h-3.5" />
                                            Add Question
                                        </button>
                                    </div>
                                </div>
                                
                                <draggable 
                                    v-model="form.questions" 
                                    handle=".drag-handle" 
                                    item-key="id"
                                    class="space-y-4"
                                >
                                    <template #item="{ element: question, index: qIndex }">
                                        <div class="group relative border border-border rounded-[2rem] bg-card transition-all hover:shadow-lg focus-within:ring-2 focus-within:ring-purple-500/20 overflow-hidden">
                                            <!-- Collapsible Header -->
                                            <div 
                                                @click="toggleQuestionCollapse(question.id)" 
                                                class="flex items-center justify-between p-5 cursor-pointer hover:bg-muted/30 transition-colors border-b border-border/50"
                                            >
                                                <div class="flex items-center gap-4">
                                                    <div class="drag-handle p-1.5 -ml-2 cursor-grab active:cursor-grabbing text-muted-foreground/30 hover:text-indigo-500 transition-colors">
                                                        <GripVertical class="w-4 h-4" />
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-[10px] font-black text-purple-600 uppercase tracking-widest bg-purple-50 px-2 py-0.5 rounded">Q{{ qIndex + 1 }}</span>
                                                            <span class="text-sm font-black text-foreground truncate max-w-[300px]">{{ question.text || 'Untitled Question' }}</span>
                                                        </div>
                                                        <!-- Status Badges -->
                                                        <div class="flex items-center gap-2 mt-1.5">
                                                            <span class="text-[8px] font-black text-muted-foreground uppercase bg-muted px-1.5 py-0.5 rounded">{{ question.type }}</span>
                                                            <span v-if="hasBranching(question)" class="text-[8px] font-black text-white bg-indigo-500 uppercase tracking-[0.1em] px-1.5 py-0.5 rounded flex items-center gap-1">
                                                                <Activity class="w-2 h-2" /> Branching Active
                                                            </span>
                                                            <span v-if="hasVisibility(question)" class="text-[8px] font-black text-white bg-emerald-500 uppercase tracking-[0.1em] px-1.5 py-0.5 rounded flex items-center gap-1">
                                                                <Target class="w-2 h-2" /> Display Rules
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" @click.stop="removeQuestion(qIndex)" class="p-2 text-muted-foreground hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                                        <Trash2 class="w-4.5 h-4.5" />
                                                    </button>
                                                    <div class="p-2 text-muted-foreground">
                                                        <ChevronUp v-if="!collapsedQuestions[question.id]" class="w-5 h-5" />
                                                        <ChevronDown v-else class="w-5 h-5" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="!collapsedQuestions[question.id]" class="animate-in slide-in-from-top-2 duration-300">
                                                <!-- Dynamic Tab Switcher -->
                                                <div class="flex border-b border-border/50 bg-muted/20 p-1 m-4 rounded-xl">
                                                    <button 
                                                        type="button"
                                                        @click="setQuestionTab(question.id, 'content')"
                                                        :class="[(!activeTabs[question.id] || activeTabs[question.id] === 'content') ? 'bg-background shadow-sm text-purple-600' : 'text-muted-foreground hover:text-foreground']"
                                                        class="flex-1 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2"
                                                    >
                                                        <ClipboardList class="w-3 h-3" />
                                                        Content
                                                    </button>
                                                    <button 
                                                        type="button"
                                                        @click="setQuestionTab(question.id, 'logic')"
                                                        :class="[activeTabs[question.id] === 'logic' ? 'bg-background shadow-sm text-indigo-600' : 'text-muted-foreground hover:text-foreground']"
                                                        class="flex-1 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2"
                                                    >
                                                        <Activity class="w-3 h-3" />
                                                        Logic & Branching
                                                    </button>
                                                </div>

                                                <!-- Tab: Content -->
                                                <div v-if="!activeTabs[question.id] || activeTabs[question.id] === 'content'" class="p-6 pt-2 space-y-6">
                                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                                        <div class="md:col-span-3 space-y-4">
                                                            <div>
                                                                <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1.5 ml-1">Question Title</label>
                                                                <input v-model="question.text" type="text" placeholder="e.g. How often do you buy groceries?" class="w-full rounded-xl border-border border p-3.5 bg-muted/30 text-foreground font-bold focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all" required>
                                                            </div>
                                                            <div>
                                                                <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1.5 ml-1">Sub-label / Hint (Optional)</label>
                                                                <input v-model="question.hint" type="text" placeholder="Extra instructions for the respondent..." class="w-full rounded-xl border-border border p-3.5 bg-muted/30 text-foreground text-sm font-medium focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all">
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1.5 ml-1">Format</label>
                                                            <select v-model="question.type" @change="onQuestionTypeChange(question)" class="w-full rounded-xl border-border border p-3.5 bg-muted/30 text-foreground font-black text-sm focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all">
                                                                <option value="mcq">Multiple Choice</option>
                                                                <option value="scale">Linear Scale</option>
                                                                <option value="checkbox">Checkboxes</option>
                                                                <option value="text">Text Entry</option>
                                                                <option value="image_mcq">Image Choice</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- MCQ/Checkbox Options -->
                                                    <div v-if="question.type === 'mcq' || question.type === 'checkbox'" class="space-y-3 pl-4 border-l-4 border-purple-500/20">
                                                        <div class="flex items-center justify-between mb-4">
                                                            <label class="text-[10px] font-black uppercase tracking-widest text-foreground">Response Options</label>
                                                        </div>
                                                        <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-center gap-3 animate-in fade-in slide-in-from-left-2 duration-200">
                                                            <div class="flex-1 relative group">
                                                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-muted-foreground bg-muted w-5 h-5 flex items-center justify-center rounded-lg">{{ oIndex + 1 }}</div>
                                                                <input v-model="question.options[oIndex]" type="text" class="w-full rounded-xl border-border border py-3 pl-11 pr-4 bg-background text-foreground text-sm font-bold focus:border-purple-500 focus:ring-0 transition-all" required>
                                                            </div>
                                                            <button type="button" @click="removeOption(qIndex, oIndex)" class="p-2 text-muted-foreground hover:text-red-500 transition-colors">
                                                                <X class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                        <button 
                                                            type="button" 
                                                            @click="addOption(qIndex)" 
                                                            class="inline-flex items-center gap-2 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all mt-2"
                                                        >
                                                            <Plus class="w-3.5 h-3.5" />
                                                            Add Option
                                                        </button>
                                                    </div>

                                                    <!-- Image MCQ Options -->
                                                    <div v-if="question.type === 'image_mcq'" class="space-y-3 pl-4 border-l-4 border-purple-500/20">
                                                        <div class="flex items-center justify-between mb-4">
                                                            <label class="text-[10px] font-black uppercase tracking-widest text-foreground">Image Options</label>
                                                        </div>
                                                        <div
                                                            v-for="(option, oIndex) in question.options"
                                                            :key="oIndex"
                                                            class="flex items-center gap-3 animate-in fade-in slide-in-from-left-2 duration-200"
                                                        >
                                                            <button
                                                                type="button"
                                                                @click="uploadOptionImage(qIndex, oIndex)"
                                                                class="relative w-14 h-12 rounded-xl overflow-hidden border-2 border-dashed border-purple-300 flex-shrink-0 hover:border-purple-500 transition-all bg-purple-50 dark:bg-purple-900/20"
                                                                title="Click to upload image"
                                                            >
                                                                <img
                                                                    v-if="option.image_url"
                                                                    :src="option.image_url"
                                                                    :alt="option.label"
                                                                    class="w-full h-full object-cover"
                                                                />
                                                                <div v-else class="w-full h-full flex items-center justify-center">
                                                                    <Plus class="w-5 h-5 text-purple-400" />
                                                                </div>
                                                            </button>
                                                            <div class="flex-1 relative">
                                                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-muted-foreground bg-muted w-5 h-5 flex items-center justify-center rounded-lg">
                                                                    {{ oIndex + 1 }}
                                                                </div>
                                                                <input
                                                                    v-model="option.label"
                                                                    type="text"
                                                                    placeholder="Option label"
                                                                    class="w-full rounded-xl border-border border py-3 pl-11 pr-4 bg-background text-foreground text-sm font-bold focus:border-purple-500 focus:ring-0 transition-all"
                                                                    required
                                                                />
                                                            </div>
                                                            <button
                                                                type="button"
                                                                @click="removeImageOption(qIndex, oIndex)"
                                                                :disabled="question.options.length <= 2"
                                                                class="p-2 text-muted-foreground hover:text-red-500 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                                            >
                                                                <X class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            @click="addImageOption(qIndex)"
                                                            class="inline-flex items-center gap-2 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-purple-600 hover:bg-purple-50 rounded-xl transition-all mt-2"
                                                        >
                                                            <Plus class="w-3.5 h-3.5" />
                                                            Add Image Option
                                                        </button>
                                                    </div>

                                                    <!-- Scale Config -->
                                                    <div v-if="question.type === 'scale'" class="p-6 bg-amber-50/30 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 space-y-4">
                                                        <label class="block text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-2">Scale Range</label>
                                                        <div class="grid grid-cols-3 gap-6">
                                                            <div class="space-y-1.5">
                                                                <span class="text-[9px] font-black uppercase text-muted-foreground ml-1">Minimum</span>
                                                                <input v-model.number="question.min" type="number" class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-bold focus:ring-0">
                                                            </div>
                                                            <div class="space-y-1.5">
                                                                <span class="text-[9px] font-black uppercase text-muted-foreground ml-1">Maximum</span>
                                                                <input v-model.number="question.max" type="number" class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-bold focus:ring-0">
                                                            </div>
                                                            <div class="space-y-1.5">
                                                                <span class="text-[9px] font-black uppercase text-muted-foreground ml-1">Step</span>
                                                                <input v-model.number="question.step" type="number" class="w-full rounded-xl border-border border p-3 bg-background text-foreground font-bold focus:ring-0">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tab: Logic -->
                                                <div v-if="activeTabs[question.id] === 'logic'" class="p-8 space-y-10 animate-in fade-in duration-300">
                                                    <!-- Branching -->
                                                    <div v-if="question.type === 'mcq'" class="space-y-6">
                                                        <div class="flex items-center gap-3">
                                                            <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                                                <Activity class="w-5 h-5" />
                                                            </div>
                                                            <div>
                                                                <h4 class="text-sm font-black uppercase tracking-widest text-foreground">Skip Logic / Jumps</h4>
                                                                <p class="text-[10px] text-muted-foreground font-medium">Redirect users to specific questions based on their choice.</p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="space-y-3">
                                                            <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-2xl bg-muted/30 border border-border/50">
                                                                <div class="flex-1 text-xs font-black text-foreground">If respondent picks <span class="text-indigo-600">"{{ option }}"</span></div>
                                                                <div class="flex items-center gap-3">
                                                                    <ArrowRight class="w-4 h-4 text-muted-foreground" />
                                                                    <select 
                                                                        v-if="question.logic"
                                                                        v-model="question.logic[option]" 
                                                                        class="min-w-[200px] rounded-xl border-border border p-2.5 text-[10px] bg-background text-foreground font-black uppercase tracking-widest focus:ring-4 focus:ring-indigo-500/10 transition-all"
                                                                    >
                                                                        <option :value="undefined">Continue to Next</option>
                                                                        
                                                                        <!-- Relative Smart Jumps (Suggestion 3) -->
                                                                        <optgroup v-if="qIndex + 2 < form.questions.length" label="Smart Jumps">
                                                                            <option :value="form.questions[qIndex + 2]?.id">Skip next question</option>
                                                                            <option v-if="qIndex + 3 < form.questions.length" :value="form.questions[qIndex + 3]?.id">Skip next 2 questions</option>
                                                                            <option v-if="qIndex + 4 < form.questions.length" :value="form.questions[qIndex + 4]?.id">Skip next 3 questions</option>
                                                                        </optgroup>

                                                                        <optgroup label="Jump to specific">
                                                                            <template v-for="(q, idx) in form.questions" :key="q.id">
                                                                                <option v-if="idx > qIndex" :value="q.id">Q{{ idx + 1 }}: {{ q.text.length > 30 ? q.text.substring(0, 30) + '...' : q.text }}</option>
                                                                            </template>
                                                                            <option value="end">Submit Survey early</option>
                                                                        </optgroup>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Display Rules -->
                                                    <div class="space-y-6">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center gap-3">
                                                                <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                                                                    <Target class="w-5 h-5" />
                                                                </div>
                                                                <div>
                                                                    <h4 class="text-sm font-black uppercase tracking-widest text-foreground">Display Rules</h4>
                                                                    <p class="text-[10px] text-muted-foreground font-medium">Control who sees this question based on prior data.</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" @click="addVisibilityRule(qIndex)" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-100 transition-all">+ Add Rule</button>
                                                        </div>

                                                        <div v-if="question.visibility" class="space-y-4 p-6 bg-emerald-50/20 rounded-[2rem] border border-emerald-100/50">
                                                            <div class="flex items-center gap-3 mb-2">
                                                                <span class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Match requirement:</span>
                                                                <select v-model="question.visibility.logic" class="text-[10px] font-black uppercase bg-background border-border rounded-lg px-3 py-1.5 focus:ring-0">
                                                                    <option value="and">All Rules (AND)</option>
                                                                    <option value="or">Any Rule (OR)</option>
                                                                </select>
                                                            </div>

                                                            <div v-for="(rule, rIndex) in question.visibility.conditions" :key="rIndex" class="flex flex-wrap items-center gap-3 animate-in fade-in duration-300">
                                                                <div class="flex items-center gap-2 bg-background border border-border p-1.5 rounded-xl flex-1">
                                                                    <select v-model="rule.type" class="text-[10px] font-black uppercase bg-muted px-3 py-2 rounded-lg border-none focus:ring-0">
                                                                        <option value="answer">Prior Answer</option>
                                                                        <option value="trait">Profile Trait</option>
                                                                    </select>

                                                                    <select v-if="rule.type === 'answer'" v-model="rule.key" class="flex-1 text-[10px] font-black uppercase bg-transparent border-none focus:ring-0">
                                                                        <option value="">Select Question...</option>
                                                                        <template v-for="(prevQ, pIdx) in form.questions" :key="prevQ.id">
                                                                            <option v-if="pIdx < qIndex" :value="prevQ.id">Q{{ pIdx + 1 }}: {{ prevQ.text.substring(0, 40) }}...</option>
                                                                        </template>
                                                                    </select>
                                                                    <input v-else v-model="rule.key" placeholder="Trait (e.g. food_pref)" class="flex-1 text-[10px] font-black uppercase bg-transparent border-none focus:ring-0" />

                                                                    <select v-model="rule.operator" class="text-[10px] font-black uppercase bg-muted px-3 py-2 rounded-lg border-none focus:ring-0">
                                                                        <option value="eq">is</option>
                                                                        <option value="neq">is not</option>
                                                                    </select>

                                                                    <input v-model="rule.value" placeholder="Value..." class="flex-1 text-[10px] font-black uppercase bg-transparent border-none focus:ring-0" />
                                                                </div>

                                                                <button type="button" @click="removeCondition(qIndex, rIndex)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                                                    <Trash2 class="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div v-else class="flex flex-col items-center justify-center p-10 border-2 border-dashed border-border rounded-[2.5rem] bg-muted/10 opacity-60">
                                                            <Target class="w-8 h-8 text-muted-foreground/30 mb-2" />
                                                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest text-center leading-relaxed">No visibility rules set.<br>Visible to all matching respondents.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>

                                <!-- Add Question (End of list) -->
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 py-8 border-2 border-dashed border-border rounded-3xl bg-muted/10 mt-6">
                                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Add more content to your survey</p>
                                    <div class="flex items-center gap-2">
                                        <button 
                                            type="button" 
                                            @click="showBankModal = true" 
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-100 transition-all active:scale-95"
                                        >
                                            <BookOpen class="w-4 h-4" />
                                            From Bank
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="addQuestion" 
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-purple-700 shadow-lg shadow-purple-600/20 transition-all active:scale-95"
                                        >
                                            <Plus class="w-4 h-4" />
                                            Add Question
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-4 pt-6 border-t border-border">
                                <button type="button" @click="cancelForm" class="px-6 py-3 bg-secondary text-secondary-foreground rounded-xl text-sm font-bold uppercase tracking-widest hover:bg-muted transition-colors">Cancel</button>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing" 
                                    class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white rounded-xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all active:scale-95 disabled:opacity-50"
                                >
                                    <Save class="w-4 h-4" />
                                    {{ isEditing ? 'Update Campaign' : 'Publish Survey' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-black text-foreground">Manage Campaigns</h2>
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-widest bg-muted px-2 py-1 rounded-md">{{ surveys.length }} Surveys</span>
                </div>

                <div class="rounded-3xl border border-border bg-card shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-muted/50 border-b border-border">
                                    <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Survey Info</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Progress</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="survey in surveys" :key="survey.id" class="group hover:bg-muted/30 transition-colors">
                                    <td class="px-6 py-5">
                                        <div class="font-bold text-foreground group-hover:text-indigo-600 transition-colors">{{ survey.title }}</div>
                                        <div class="flex items-center gap-3 mt-1.5">
                                            <span class="flex items-center gap-1 text-[10px] font-black text-muted-foreground uppercase">
                                                <Coins class="w-3 h-3 text-amber-500" />
                                                {{ survey.reward_points }} pts
                                            </span>
                                            <span class="flex items-center gap-1 text-[10px] font-black text-muted-foreground uppercase">
                                                <Users class="w-3 h-3 text-indigo-500" />
                                                {{ survey.response_cap }} Cap
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tight"
                                            :class="survey.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-muted text-muted-foreground'"
                                        >
                                            <div class="w-1.5 h-1.5 rounded-full" :class="survey.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-muted-foreground'"></div>
                                            {{ survey.is_active ? 'Active' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-24 bg-muted rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-indigo-500 h-full rounded-full" :style="{ width: (survey.response_cap ? Math.min(100, (survey.responses_count / survey.response_cap) * 100) : 0) + '%' }"></div>
                                            </div>
                                            <span class="text-[10px] font-black text-muted-foreground">{{ survey.response_cap ? Math.round((survey.responses_count / survey.response_cap) * 100) + '%' : '∞' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button @click="toggleStatus(survey.id)" :title="survey.is_active ? 'Close Survey' : 'Activate Survey'" class="p-2 text-muted-foreground hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                                <ToggleRight v-if="survey.is_active" class="w-5 h-5" />
                                                <ToggleLeft v-else class="w-5 h-5" />
                                            </button>
                                            <button v-if="(survey.reward_type === 'prize_draw' || survey.reward_type === 'airtime') && !survey.draw_phase_active" @click="startDrawPhase(survey.id)" :title="survey.reward_type === 'airtime' ? 'Start Airtime Claim Phase' : 'Start Prize Draw Phase'" class="p-2 text-muted-foreground hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all">
                                                <Trophy class="w-5 h-5" />
                                            </button>
                                            <Link :href="'/admin/surveys/' + survey.id + '/report'" title="View Analytics" class="p-2 text-muted-foreground hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                                <BarChart3 class="w-5 h-5" />
                                            </Link>
                                            <a :href="'/surveys/' + survey.id" target="_blank" title="Preview Survey" class="p-2 text-muted-foreground hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all">
                                                <Eye class="w-5 h-5" />
                                            </a>
                                            <button @click="cloneSurvey(survey.id)" title="Clone Survey" class="p-2 text-muted-foreground hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all">
                                                <Copy class="w-5 h-5" />
                                            </button>
                                            <button @click="editSurvey(survey)" title="Edit Survey" class="p-2 text-muted-foreground hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                                <Edit2 class="w-5 h-5" />
                                            </button>
                                            <button @click="deleteSurvey(survey.id)" title="Delete Survey" class="p-2 text-muted-foreground hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                                <Trash2 class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="surveys.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="rounded-full bg-muted p-4 mb-3">
                                                <Activity class="h-6 w-6 text-muted-foreground" />
                                            </div>
                                            <h3 class="text-sm font-black text-foreground uppercase tracking-widest">No Campaigns Yet</h3>
                                            <p class="text-xs text-muted-foreground mt-1 font-bold">Start by creating your first survey campaign above.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Bank Modal -->
        <div v-if="showBankModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-card w-full max-w-2xl rounded-3xl border border-border shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <h3 class="text-xl font-black text-foreground uppercase tracking-tight">Question Bank</h3>
                    <button @click="showBankModal = false" class="p-2 hover:bg-muted rounded-full">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                    <div v-for="template in questionBank" :key="template.id" @click="importFromBank(template)" class="p-4 rounded-2xl border border-border hover:border-indigo-500 hover:bg-indigo-50/30 cursor-pointer transition-all group">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ template.category }}</span>
                            <span class="text-[10px] font-bold text-muted-foreground uppercase">{{ template.questions?.length || 0 }} Questions</span>
                        </div>
                        <p class="font-bold text-foreground group-hover:text-indigo-700">{{ template.name }}</p>
                        <div class="mt-2 space-y-1">
                            <p v-for="(q, idx) in template.questions?.slice(0, 2)" :key="idx" class="text-[10px] text-muted-foreground truncate italic">
                                • {{ q.text }}
                            </p>
                        </div>
                    </div>
                    <div v-if="questionBank.length === 0" class="text-center py-12 text-muted-foreground font-medium">
                        Your question bank is empty. Add templates from the <Link href="/admin/question-bank" class="text-indigo-600 underline">Question Bank page</Link>.
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Modal -->
        <div v-if="showQrModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md animate-in fade-in zoom-in-95 duration-200">
            <div class="bg-card w-full max-w-sm rounded-[2.5rem] border border-border shadow-2xl overflow-hidden p-8 text-center space-y-6">
                <div class="flex justify-between items-center">
                    <div class="w-10"></div>
                    <h3 class="text-xl font-black text-foreground uppercase tracking-tight">Mobile Test</h3>
                    <button @click="showQrModal = false" class="p-2 hover:bg-muted rounded-full">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="bg-white p-6 rounded-3xl inline-block mx-auto shadow-inner border-4 border-muted">
                    <qrcode-vue :value="previewUrl" :size="200" level="H" />
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-bold text-foreground">Scan to test on your phone</p>
                    <p class="text-xs text-muted-foreground leading-relaxed px-4">
                        Experience the survey exactly as your respondents will, including haptics and mobile-only interactions.
                    </p>
                </div>

                <div class="pt-2">
                    <button @click="showQrModal = false" class="w-full py-3 bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 transition-all active:scale-95">
                        Got it
                    </button>
                </div>
            </div>
        </div>

        <!-- Logic Simulator Modal (Task 4) -->
        <teleport to="body">
            <SurveySimulator 
                v-if="showSimulationModal"
                :questions="form.questions"
                :enrichment-questions="form.enrichment_questions"
                :title="form.title"
                :reward-type="form.reward_type"
                :prize-name="form.prize_name"
                :reward-points="form.reward_points"
                :reward-amount="form.reward_amount"
                @close="showSimulationModal = false"
            />
        </teleport>
    </AppLayout>
</template>
