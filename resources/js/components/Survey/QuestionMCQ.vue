<script setup lang="ts">
import { defineEmits, defineProps, ref } from 'vue';
import { useHaptics } from '@/composables/useHaptics';
import type { Question } from '@/types/survey';


const props = defineProps<{
    question: Question;
}>();

const emit = defineEmits(['answer']);
const selected = ref<string | number | null>(null);
const { lightClick } = useHaptics();

function select(optionId: string | number) {
    lightClick();
    selected.value = optionId;
    emit('answer', { questionId: props.question.id, answer: optionId });
}

function getOptionId(opt: any) {
    return typeof opt === 'object' ? opt.id : opt;
}

function getOptionLabel(opt: any) {
    return typeof opt === 'object' ? opt.label : opt;
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <p class="text-base font-bold text-foreground leading-snug">{{ question.text }}</p>
            <p v-if="question.hint" class="text-xs text-muted-foreground italic mt-1 leading-relaxed">{{ question.hint }}</p>
        </div>
        <div class="grid gap-2.5">
            <button
                v-for="opt in question.options"
                :key="getOptionId(opt)"
                :class="[
                    'w-full rounded-xl border px-5 py-3 text-left font-bold transition-all duration-200',
                    selected === getOptionId(opt) 
                        ? 'border-primary bg-primary/10 text-primary shadow-sm' 
                        : 'border-border bg-card text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                ]"
                type="button"
                @click="select(getOptionId(opt))"
            >
                {{ getOptionLabel(opt) }}
            </button>
        </div>
    </div>
</template>
