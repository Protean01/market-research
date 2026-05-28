<script setup lang="ts">
import { defineEmits, defineProps, ref, watch } from 'vue';
import type { Question } from '@/types/survey';

const props = defineProps<{
    question: Question;
}>();

const emit = defineEmits(['answer']);
const selected = ref<(string | number)[]>([]);

function getOptionId(opt: any) {
    return typeof opt === 'object' ? opt.id : opt;
}

function getOptionLabel(opt: any) {
    return typeof opt === 'object' ? opt.label : opt;
}

watch(selected, (newVal) => {
    emit('answer', { questionId: props.question.id, answer: newVal });
});
</script>

<template>
    <div class="space-y-4">
        <div>
            <p class="text-base font-bold text-foreground leading-snug">{{ question.text }} <span class="text-xs text-muted-foreground font-normal">(Select all that apply)</span></p>
            <p v-if="question.hint" class="text-xs text-muted-foreground italic mt-1 leading-relaxed">{{ question.hint }}</p>
        </div>
        <div class="grid gap-2.5">
            <label
                v-for="opt in question.options"
                :key="getOptionId(opt)"
                class="flex items-center gap-3 w-full rounded-xl border px-5 py-3 text-left font-bold transition-all duration-200 cursor-pointer hover:bg-muted/50"
                :class="selected.includes(getOptionId(opt)) ? 'border-primary bg-primary/10 text-primary shadow-sm' : 'border-border bg-card text-muted-foreground'"
            >
                <input 
                    type="checkbox" 
                    :value="getOptionId(opt)" 
                    v-model="selected" 
                    class="w-5 h-5 rounded border-border text-primary focus:ring-primary/20"
                />
                {{ getOptionLabel(opt) }}
            </label>
        </div>
    </div>
</template>
