<script setup lang="ts">
import { X, MessageSquare, Trophy, Zap } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import QuestionMCQ from '@/components/Survey/QuestionMCQ.vue';
import QuestionScale from '@/components/Survey/QuestionScale.vue';
import { useHaptics } from '@/composables/useHaptics';

const props = defineProps<{
    questions: any[];
    enrichmentQuestions: any[];
    title: string;
    rewardType: string;
    prizeName?: string;
    rewardPoints?: number;
    rewardAmount?: number;
}>();

const emit = defineEmits(['close']);

const { lightClick, mediumClick, success: successHaptic } = useHaptics();

// Local simulation state
const currentQuestionIndex = ref(0);
const history = ref<number[]>([]);
const answers = ref<Record<string, any>>({});
const isFinished = ref(false);

const allQuestions = computed(() => {
    return [...props.questions, ...props.enrichmentQuestions];
});

const currentQuestion = computed(() => {
    if (isFinished.value) {
return null;
}

    return allQuestions.value[currentQuestionIndex.value];
});

function handleAnswer(question: any, payload: any) {
    answers.value[payload.questionId] = payload.answer;
    setTimeout(next, 400);
}

function isQuestionVisible(question: any): boolean {
    if (!question.visibility || !question.visibility.conditions?.length) {
return true;
}

    const { logic, conditions } = question.visibility;
    const results = conditions.map((condition: any) => {
        let actualValue: any;

        if (condition.type === 'answer') {
            actualValue = answers.value[condition.key];
        } else {
            // In simulation, we assume some default trait values or let them pass
            actualValue = 'simulation_value'; 
        }

        if (actualValue === undefined || actualValue === null) {
return false;
}
        
        switch (condition.operator) {
            case 'eq': return String(actualValue).toLowerCase() === String(condition.value).toLowerCase();
            case 'neq': return String(actualValue).toLowerCase() !== String(condition.value).toLowerCase();
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
    const answer = answers.value[q.id];
    
    // 1. Check simple jump logic
    if (q.logic && answer !== undefined) {
        const nextId = q.logic[answer];

        if (nextId === 'end') {
            isFinished.value = true;

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
    
    isFinished.value = true;
    successHaptic();
}

function prev() {
    lightClick();

    if (history.value.length > 0) {
        currentQuestionIndex.value = history.value.pop()!;
        isFinished.value = false;
    }
}

const totalCount = computed(() => allQuestions.value.length);
</script>

<template>
    <div class="fixed inset-0 z-[100] flex flex-col bg-background text-foreground animate-in fade-in duration-300 overflow-hidden">
        <!-- Simulator Header -->
        <div class="px-6 pt-8 pb-4 space-y-6 border-b border-border shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                        <Zap class="w-4 h-4 fill-current" />
                    </div>
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-indigo-600">Simulator Mode</span>
                        <h2 class="text-sm font-black text-foreground uppercase truncate max-w-[200px]">{{ title }}</h2>
                    </div>
                </div>
                <button @click="emit('close')" class="p-2 hover:bg-muted rounded-full transition-colors">
                    <X class="w-6 h-6 text-muted-foreground" />
                </button>
            </div>

            <!-- Progress Bar -->
            <div class="flex gap-1.5">
                <div 
                    v-for="i in totalCount" 
                    :key="i"
                    class="h-1.5 flex-1 rounded-full overflow-hidden bg-muted"
                >
                    <div 
                        class="h-full transition-all duration-500 ease-out"
                        :class="[
                            (i - 1) < currentQuestionIndex || isFinished ? 'bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.4)]' : 
                            (i - 1) === currentQuestionIndex ? 'bg-indigo-300 animate-pulse' : 'bg-transparent'
                        ]"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 relative bg-muted/5">
            <transition mode="out-in" enter-active-class="transition-all duration-500" enter-from-class="opacity-0 scale-95 translate-y-10" leave-to-class="opacity-0 scale-105">
                <div v-if="!isFinished && currentQuestion" :key="currentQuestion.id" class="absolute inset-0 flex flex-col px-8 py-12">
                    <div class="max-w-2xl mx-auto w-full flex-1 flex flex-col justify-center space-y-12">
                        <div class="space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-600">
                                <MessageSquare class="w-3.5 h-3.5" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Question Test</span>
                            </div>
                            <h3 class="text-3xl md:text-4xl font-black leading-[1.1] tracking-tight text-foreground">
                                {{ currentQuestion.text }}
                            </h3>
                            <p v-if="currentQuestion.hint" class="text-muted-foreground text-sm font-medium">{{ currentQuestion.hint }}</p>
                        </div>

                        <div class="simulator-components">
                            <QuestionMCQ
                                v-if="currentQuestion.type === 'mcq'"
                                :question="currentQuestion"
                                @answer="handleAnswer"
                            />
                            <QuestionScale
                                v-else-if="currentQuestion.type === 'scale'"
                                :question="currentQuestion"
                                @answer="handleAnswer"
                            />
                            <div v-else class="p-10 border-2 border-dashed border-border rounded-3xl text-center">
                                <p class="text-sm font-bold text-muted-foreground">Preview not available for this type ({{ currentQuestion.type }})</p>
                                <button @click="next" class="mt-4 text-indigo-600 font-black uppercase text-[10px] tracking-widest">Skip Preview →</button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Controls -->
                    <div class="max-w-2xl mx-auto w-full mt-auto flex items-center justify-between border-t border-border pt-8">
                        <button 
                            @click="prev" 
                            :disabled="history.length === 0"
                            class="text-muted-foreground font-black uppercase text-[10px] tracking-widest hover:text-foreground disabled:opacity-0 transition-all"
                        >
                            ← Back
                        </button>
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-muted-foreground font-black text-[10px] tracking-widest uppercase italic">Logic testing active</span>
                        </div>
                    </div>
                </div>

                <!-- Simulation Finish Screen -->
                <div v-else :key="'finish'" class="absolute inset-0 flex flex-col items-center justify-center p-10 text-center">
                    <div class="w-24 h-24 rounded-[2.5rem] bg-indigo-600 flex items-center justify-center text-white shadow-2xl mb-10 rotate-12">
                        <Trophy class="w-12 h-12" />
                    </div>

                    <div class="space-y-4 mb-12">
                        <h2 class="text-4xl font-black tracking-tighter uppercase leading-none">Flow Verified!</h2>
                        <p class="text-muted-foreground text-lg font-medium leading-relaxed max-w-sm mx-auto">
                            Your logic works perfectly. The respondent would have earned:
                            <br>
                            <span v-if="rewardType === 'prize_draw'" class="text-indigo-600 font-black underline">{{ prizeName }}</span>
                            <span v-else-if="rewardType === 'airtime'" class="text-indigo-600 font-black underline">K{{ rewardAmount }} Airtime</span>
                            <span v-else class="text-indigo-600 font-black underline">{{ rewardPoints }} Points</span>
                        </p>
                    </div>

                    <button
                        @click="emit('close')"
                        class="px-10 py-5 rounded-2xl bg-foreground text-background font-black uppercase tracking-widest text-xs shadow-xl hover:scale-105 transition-all active:scale-95"
                    >
                        Back to Builder
                    </button>
                </div>
            </transition>
        </div>
    </div>
</template>

<style scoped>
.simulator-components :deep(button) {
    background: var(--muted);
    border: 2px solid var(--border);
    padding: 1.25rem;
    border-radius: 1.25rem;
    font-size: 1rem;
    font-weight: 800;
    text-transform: uppercase;
    transition: all 0.2s;
}

.simulator-components :deep(button:hover) {
    border-color: #6366f1;
    background: rgba(99,102,241,0.05);
}
</style>
