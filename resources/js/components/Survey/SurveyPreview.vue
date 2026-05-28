<script setup lang="ts">
import { RotateCcw, MessageSquare, Trophy } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    survey: {
        title: string;
        description: string;
        questions: any[];
        reward_points: number;
    }
}>();

const currentStep = ref(0);
const history = ref<number[]>([]);

const allQuestions = computed(() => props.survey.questions || []);
const currentQuestion = computed(() => allQuestions.value[currentStep.value]);

function isQuestionVisible(question: any): boolean {
    if (!question.visibility || !question.visibility.conditions?.length) {
return true;
}

    // In preview mode, we might not have previous answers yet for all logic,
    // but we can simulate or just show all for builder clarity.
    // However, to be accurate, we'll try to check.
    return true; 
}

const next = () => {
    let nextIdx = currentStep.value + 1;
    
    while (nextIdx < allQuestions.value.length) {
        if (isQuestionVisible(allQuestions.value[nextIdx])) {
            history.value.push(currentStep.value);
            currentStep.value = nextIdx;

            return;
        }

        nextIdx++;
    }
    
    currentStep.value = 999; // Finished
};

const prev = () => {
    if (history.value.length > 0) {
        currentStep.value = history.value.pop()!;
    }
};

const reset = () => {
    currentStep.value = 0;
    history.value = [];
};

// Reset if questions change significantly
watch(() => props.survey.questions?.length, () => reset());
</script>

<template>
    <div class="flex flex-col h-full bg-zinc-100 dark:bg-zinc-950 rounded-[3rem] border-[8px] border-zinc-800 shadow-2xl relative overflow-hidden aspect-[9/19] max-h-[700px] mx-auto">
        <!-- Phone Notch -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-zinc-800 rounded-b-2xl z-20"></div>
        
        <!-- Screen Content -->
        <div class="flex-1 bg-background overflow-hidden flex flex-col pt-8">
            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                <!-- Welcome State -->
                <div v-if="currentStep === 0" class="space-y-4 pt-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white mb-4">
                        <MessageSquare class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-black text-foreground">{{ survey.title || 'Survey Title' }}</h3>
                    <p class="text-sm text-muted-foreground">{{ survey.description || 'No description provided.' }}</p>
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <p class="text-xs font-black text-indigo-600 uppercase">Reward: {{ survey.reward_points }} pts</p>
                    </div>
                    <button @click="next" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black uppercase text-xs tracking-widest mt-4">Start Survey</button>
                </div>

                <!-- Questions -->
                <div v-else-if="currentStep !== 999 && currentQuestion" class="space-y-6 pt-4">
                    <div class="flex justify-between items-center text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-2">
                        <span>Question {{ currentStep }} of {{ allQuestions.length }}</span>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-foreground leading-tight">{{ currentQuestion.text || 'Untitled Question' }}</p>
                        <p v-if="currentQuestion.hint" class="text-[10px] text-muted-foreground italic font-medium leading-relaxed">{{ currentQuestion.hint }}</p>
                    </div>
                    
                    <div v-if="currentQuestion.type === 'mcq' || currentQuestion.type === 'checkbox'" class="space-y-2">
                        <div v-for="(opt, idx) in currentQuestion.options" :key="idx" class="p-3 rounded-xl border border-border bg-muted/30 text-xs font-bold text-muted-foreground">
                            {{ opt }}
                        </div>
                    </div>
                    <div v-else-if="currentQuestion.type === 'scale'" class="flex justify-between gap-1">
                        <div v-for="i in 5" :key="i" class="w-8 h-8 rounded-lg border border-border flex items-center justify-center text-xs font-bold text-muted-foreground">
                            {{ i }}
                        </div>
                    </div>
                    <div v-else class="h-20 rounded-xl border border-border bg-muted/30 p-3 text-[10px] text-muted-foreground italic">
                        User input field ({{ currentQuestion.type }})
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <button @click="prev" class="text-[10px] font-black uppercase text-muted-foreground hover:text-foreground">Back</button>
                        <button @click="next" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-[10px] font-black uppercase tracking-widest">Next</button>
                    </div>
                </div>

                <!-- End State -->
                <div v-else-if="currentStep === 999" class="h-full flex flex-col items-center justify-center text-center space-y-4">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                        <Trophy class="w-8 h-8" />
                    </div>
                    <h3 class="font-black text-foreground">You're Done!</h3>
                    <p class="text-xs text-muted-foreground">Submit to claim rewards.</p>
                    <button @click="reset" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest flex items-center gap-1">
                        <RotateCcw class="w-3 h-3" /> Preview Again
                    </button>
                </div>
            </div>
        </div>

        <!-- Home Indicator -->
        <div class="h-1.5 w-32 bg-zinc-800 rounded-full mx-auto mb-2 absolute bottom-2 left-1/2 -translate-x-1/2"></div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
</style>
