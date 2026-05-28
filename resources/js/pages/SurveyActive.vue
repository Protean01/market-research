<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import { ArrowRight, CheckCircle2, MessageSquare, X, Trophy } from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import QuestionCheckbox from '@/components/Survey/QuestionCheckbox.vue';
import QuestionImageMCQ from '@/components/Survey/QuestionImageMCQ.vue';
import QuestionMCQ from '@/components/Survey/QuestionMCQ.vue';
import QuestionScale from '@/components/Survey/QuestionScale.vue';
import QuestionText from '@/components/Survey/QuestionText.vue';
import { useHaptics } from '@/composables/useHaptics';
import AppLayout from '@/layouts/AppLayout.vue';
import { useProfileStore } from '@/stores/profileStore';
import { useSettingsStore } from '@/stores/settingsStore';
import { useSurveyStore } from '@/stores/survey';

import type { Survey, Question } from '@/types/survey';

const props = defineProps<{
    survey: Survey;
}>();

const surveyStore = useSurveyStore();
const profileStore = useProfileStore();
const settingsStore = useSettingsStore();
const { lightClick, mediumClick, success: successHaptic, heavyClick } = useHaptics();

// Navigation state
const currentQuestionIndex = ref(0);
const history = ref<number[]>([]);
const isSurveyFinished = ref(false);
const startTime = ref(Date.now());
const isFlyingPoints = ref(false);
const isSubmitted = ref(false);

function fireConfetti() {
    if (settingsStore.dataSaver) {
return;
} // Respect data saver

    const duration = 3 * 1000;
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

    function randomInRange(min: number, max: number) {
        return Math.random() * (max - min) + min;
    }

    const interval: any = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } });
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } });
    }, 250);
}

onMounted(() => {
    startTime.value = Date.now();
    surveyStore.setActive(props.survey);

    if (usePage().props.auth?.profile) {
        profileStore.hydrate(usePage().props.auth.profile);
    }
});

const questions = computed(() => props.survey?.questions ?? []);

const enrichmentQuestions = computed(() => {
    const enrichment = props.survey?.enrichment_questions ?? [];

    return enrichment
        .filter((q: Question) => !(q.id in profileStore.enrichedAttributes))
        .slice(0, 2);
});

const allQuestions = computed(() => {
    return [...questions.value, ...enrichmentQuestions.value];
});

const currentQuestion = computed(() => {
    if (isSurveyFinished.value) {
return null;
}

    return allQuestions.value[currentQuestionIndex.value];
});

function isEnrichment(question: any) {
    return enrichmentQuestions.value.some((q: any) => q.id === question.id);
}

function handleAnswer(question: any, payload: any) {
    surveyStore.setAnswer(payload.questionId, payload.answer);

    if (isEnrichment(question)) {
        profileStore.storeEnrichment(question.id, payload.answer);
    }

    // Auto-advance for Story feel only for single-selects
    if (question.type === 'mcq' || question.type === 'MCQ' || question.type === 'scale' || question.type === 'image_mcq') {
        setTimeout(next, 400);
    }
}

function isQuestionVisible(question: any): boolean {
    if (!question.visibility || !question.visibility.conditions?.length) {
return true;
}

    const { logic, conditions } = question.visibility;
    const results = conditions.map((condition: any) => {
        let actualValue: any;

        if (condition.type === 'answer') {
            actualValue = surveyStore.answers[condition.key];
        } else {
            actualValue = profileStore.enrichedAttributes[condition.key] || (usePage().props.auth.user.profile as any)?.[condition.key];
        }

        if (actualValue === undefined || actualValue === null) {
return false;
}

        const targetValue = condition.value;

        switch (condition.operator) {
            case 'eq': return String(actualValue).toLowerCase() === String(targetValue).toLowerCase();
            case 'neq': return String(actualValue).toLowerCase() !== String(targetValue).toLowerCase();
            default: return false;
        }
    });

    return logic === 'or' ? results.some((r: boolean) => r) : results.every((r: boolean) => r);
}

function next() {
    const q = currentQuestion.value;

    if (!q) {
return;
}

    mediumClick();
    const answer = surveyStore.answers[q.id];
    
    // 1. Check simple jump logic
    if (q.logic && answer !== undefined) {
        const nextId = q.logic[answer];

        if (nextId === 'end') {
            isSurveyFinished.value = true;

            return;
        } else if (nextId) {
            const nextIdx = allQuestions.value.findIndex(question => question.id == nextId);

            if (nextIdx !== -1) {
                history.value.push(currentQuestionIndex.value);
                currentQuestionIndex.value = nextIdx;

                return;
            }
        }
    }

    // 2. Default next
    let nextIdx = currentQuestionIndex.value + 1;

    while (nextIdx < allQuestions.value.length) {
        if (isQuestionVisible(allQuestions.value[nextIdx])) {
            history.value.push(currentQuestionIndex.value);
            currentQuestionIndex.value = nextIdx;

            return;
        }

        nextIdx++;
    }
    
    isSurveyFinished.value = true;
    successHaptic();
}

function prev() {
    lightClick();

    if (history.value.length > 0) {
        currentQuestionIndex.value = history.value.pop()!;
        isSurveyFinished.value = false;
    }
}

async function submit() {
    if (isSubmitted.value || surveyStore.loading) {
return;
}

    heavyClick();
    const enrichmentAnswers = enrichmentQuestions.value.map((q: any) => ({
        questionId: q.id,
        answer: surveyStore.answers[q.id],
    }));

    const durationSeconds = Math.round((Date.now() - startTime.value) / 1000);

    try {
        const result = await surveyStore.submitSurvey(props.survey.id, enrichmentAnswers, durationSeconds);

        isSubmitted.value = true;
        isFlyingPoints.value = true;
        toast.success('Reward earned!');
        fireConfetti();

        setTimeout(() => {
            router.visit(result?.slot_machine_url || '/wallet');
        }, 2500);
    } catch (err: any) {
        toast.error(err.response?.data?.error || 'Submission failed');
    }
}

const totalCount = computed(() => allQuestions.value.length);
</script>

<template>
    <Head :title="survey.title" />

    <AppLayout>
        <div class="fixed inset-0 z-[60] flex flex-col bg-gradient-to-b from-white via-slate-50 to-indigo-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-black text-foreground dark:text-white animate-in fade-in duration-500 overflow-hidden">
            <!-- Story Header (Segmented Progress) -->
            <div class="px-4 pt-6 pb-4 space-y-4">
                <div class="flex items-center justify-between px-1">
                    <button @click="router.visit('/surveys')" class="p-2 -ml-2 text-muted-foreground hover:text-foreground transition-colors">
                        <X class="w-6 h-6" />
                    </button>
                    <div class="flex flex-col items-center">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-indigo-500 dark:text-indigo-400">MRP Research</span>
                        <h2 class="text-[10px] font-bold text-muted-foreground uppercase truncate max-w-[150px]">{{ survey.title }}</h2>
                    </div>
                    <div class="w-10"></div> <!-- Spacer -->
                </div>

                <div class="flex gap-1.5 px-1">
                    <div 
                        v-for="i in totalCount" 
                        :key="i"
                        class="h-1 flex-1 rounded-full overflow-hidden bg-black/10 dark:bg-white/10"
                    >
                        <div 
                            class="h-full transition-all duration-500 ease-out"
                            :class="[
                                (i - 1) < currentQuestionIndex || isSurveyFinished ? 'bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.6)]' : 
                                (i - 1) === currentQuestionIndex ? 'bg-indigo-400 dark:bg-white animate-pulse' : 'bg-transparent'
                            ]"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Story Body -->
            <div class="flex-1 relative">
                <transition 
                    mode="out-in" 
                    enter-active-class="transition-all duration-500 cubic-bezier(0.23, 1, 0.32, 1)"
                    enter-from-class="opacity-0 scale-95 translate-y-10"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-400 cubic-bezier(0.23, 1, 0.32, 1)"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-105"
                >
                    <div
                        v-if="!isSurveyFinished && currentQuestion"
                        :key="currentQuestion.id"
                        class="absolute inset-0 flex flex-col px-8 py-10 bg-white/95 dark:bg-zinc-900/70 border border-border shadow-xl rounded-[2.5rem] max-w-5xl mx-auto"
                    >
                        <div class="flex-1 flex flex-col justify-center space-y-10">
                            <div class="space-y-4">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                                    <MessageSquare class="w-3.5 h-3.5" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">{{ isEnrichment(currentQuestion) ? 'Profile Insight' : 'Question' }}</span>
                                </div>
                                <h3 class="text-3xl md:text-4xl font-black leading-[1.1] tracking-tight">
                                    {{ currentQuestion.text }}
                                </h3>
                                <p v-if="currentQuestion.hint" class="text-zinc-500 text-sm font-medium">{{ currentQuestion.hint }}</p>
                            </div>

                            <div class="animate-in fade-in slide-in-from-bottom-4 delay-200 duration-700">
                                <QuestionMCQ
                                    v-if="currentQuestion.type === 'mcq' || currentQuestion.type === 'MCQ'"
                                    :question="currentQuestion"
                                    class="story-mode-mcq"
                                    @answer="(payload) => handleAnswer(currentQuestion, payload)"
                                />
                                <QuestionScale
                                    v-else-if="currentQuestion.type === 'scale'"
                                    :question="currentQuestion"
                                    class="story-mode-scale"
                                    @answer="(payload) => handleAnswer(currentQuestion, payload)"
                                />
                                <QuestionCheckbox
                                    v-else-if="currentQuestion.type === 'checkbox'"
                                    :question="currentQuestion"
                                    class="story-mode-checkbox"
                                    @answer="(payload) => handleAnswer(currentQuestion, payload)"
                                />
                                <QuestionText
                                    v-else-if="currentQuestion.type === 'text'"
                                    :question="currentQuestion"
                                    class="story-mode-text"
                                    @answer="(payload) => handleAnswer(currentQuestion, payload)"
                                />
                                <QuestionImageMCQ
                                    v-else-if="currentQuestion.type === 'image_mcq'"
                                    :question="currentQuestion"
                                    class="story-mode-image-mcq"
                                    @answer="(payload) => handleAnswer(currentQuestion, payload)"
                                />
                            </div>
                        </div>

                        <!-- Mini Footer -->
                        <div class="mt-auto flex items-center justify-between pt-10">
                            <button 
                                @click="prev" 
                                :disabled="history.length === 0"
                                class="text-zinc-600 font-black uppercase text-[10px] tracking-widest hover:text-foreground disabled:opacity-0 transition-all"
                            >
                                ← Previous
                            </button>
                            <span v-if="currentQuestion.type === 'mcq' || currentQuestion.type === 'MCQ' || currentQuestion.type === 'scale'" class="text-foreground/70 font-black text-[10px] tracking-widest uppercase italic">Tap to select</span>
                            <button 
                                v-else
                                @click="next" 
                                :disabled="currentQuestion.required && (!surveyStore.answers[currentQuestion.id] || (Array.isArray(surveyStore.answers[currentQuestion.id]) && surveyStore.answers[currentQuestion.id].length === 0))"
                                class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-[1rem] font-black uppercase text-[10px] tracking-widest hover:bg-indigo-500 disabled:opacity-50 transition-all shadow-md active:scale-95"
                            >
                                Continue →
                            </button>
                        </div>
                    </div>

                    <!-- Story Finish Screen -->
                    <div v-else :key="'finish'" class="absolute inset-0 flex flex-col items-center justify-center p-10 text-center">
                        <div class="relative mb-10">
                            <div class="w-24 h-24 rounded-[2rem] bg-indigo-600 flex items-center justify-center text-white shadow-2xl rotate-12 animate-bounce">
                                <Trophy class="w-12 h-12" />
                            </div>
                            <div class="absolute -top-4 -right-4 w-12 h-12 rounded-full bg-emerald-500 flex items-center justify-center text-white shadow-lg -rotate-12">
                                <CheckCircle2 class="w-6 h-6" />
                            </div>
                        </div>

                        <div class="space-y-4 mb-12">
                            <h2 class="text-4xl font-black tracking-tighter uppercase leading-none">Perfect!</h2>
                            <p class="text-muted-foreground text-lg font-medium leading-relaxed">
                                <template v-if="survey.reward_type === 'prize_draw'">
                                    You've earned an entry into the <span class="text-indigo-600 dark:text-white font-black underline">{{ survey.prize_name || 'Grand Prize Draw' }}</span>!
                                </template>
                                <template v-else-if="survey.reward_type === 'airtime'">
                                    <span v-if="survey.draw_phase_active">Spinning now — claim your <span class="text-indigo-600 dark:text-white font-black underline">Airtime Prize</span> on the slot machine!</span>
                                    <span v-else>You've been entered into the <span class="text-indigo-600 dark:text-white font-black underline">Airtime Prize Draw</span>. You'll be notified when the draw opens.</span>
                                </template>
                                <template v-else>
                                     You've unlocked <span class="text-indigo-600 dark:text-white font-black underline">{{ survey.reward_points ?? 50 }} points</span>!
                                </template>
                            </p>
                        </div>

                        <div class="w-full max-w-sm space-y-4">
                            <button
                                class="w-full group py-6 rounded-[2rem] bg-white dark:bg-zinc-800 text-black dark:text-white font-black uppercase tracking-[0.2em] text-sm shadow-2xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all active:scale-95 disabled:opacity-50"
                                :disabled="surveyStore.loading || isSubmitted"
                                @click="submit()"
                            >
                                <span v-if="surveyStore.loading">Processing...</span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    {{ survey.draw_phase_active ? 'Spin Now!' : (survey.reward_type === 'prize_draw' || survey.reward_type === 'airtime') ? 'Enter Draw' : 'Claim Reward' }}
                                    <ArrowRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                                </span>
                            </button>
                        </div>

                        <!-- Points Flying Animation Overlay -->
                        <div v-if="isFlyingPoints" class="absolute inset-0 z-[70] pointer-events-none overflow-hidden">
                            <div v-for="i in 12" :key="i" class="point-particle absolute" :style="{ '--delay': (i*0.1)+'s', '--x': (Math.random()*100)+'%' }">
                                <div class="bg-amber-400 rounded-full w-4 h-4 shadow-[0_0_15px_#fbbf24]"></div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.point-particle {
    bottom: 20%;
    animation: fly-up 2s ease-in forwards;
    animation-delay: var(--delay);
    opacity: 0;
}

@keyframes fly-up {
    0% { transform: translateY(0) scale(1); opacity: 0; }
    20% { opacity: 1; }
    100% { transform: translateY(-80vh) translateX(var(--x)) scale(0.5); opacity: 0; }
}

:deep(.story-mode-mcq) button {
    background: rgba(0,0,0,0.02);
    border: 2px solid rgba(0,0,0,0.06);
    color: var(--foreground);
    padding: 1.5rem;
    border-radius: 1.5rem;
    font-size: 1.1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

:deep(.story-mode-mcq) button:hover {
    background: rgba(99,102,241,0.08);
    border-color: #6366f1;
}

:deep(.story-mode-mcq) button.bg-primary {
    background: #6366f1 !important;
    border-color: #6366f1 !important;
}

:deep(.story-mode-scale) input[type=\"range\"] {
    height: 14px;
    background: #e5e7eb;
    border-radius: 9999px;
}

.dark :deep(.story-mode-scale) input[type=\"range\"] {
    background: rgba(255,255,255,0.12);
}

:deep(.story-mode-scale) input[type=\"range\"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    height: 22px;
    width: 22px;
    border-radius: 9999px;
    background: #4f46e5;
    border: 3px solid #eef2ff;
    box-shadow: 0 6px 14px rgba(79,70,229,0.3);
}

:deep(.story-mode-scale) input[type=\"range\"]::-moz-range-thumb {
    height: 22px;
    width: 22px;
    border-radius: 9999px;
    background: #4f46e5;
    border: 3px solid #eef2ff;
    box-shadow: 0 6px 14px rgba(79,70,229,0.3);
}

.dark :deep(.story-mode-mcq) button {
    background: rgba(255,255,255,0.05);
    border: 2px solid rgba(255,255,255,0.1);
    color: white;
}

.dark :deep(.story-mode-mcq) button:hover {
    background: rgba(255,255,255,0.1);
}

.dark :deep(.story-mode-scale) input[type=\"range\"] {
    background: rgba(255,255,255,0.1);
}
</style>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 10px;
}
</style>
