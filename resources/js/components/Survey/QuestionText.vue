<script setup lang="ts">
import { defineEmits, defineProps, ref } from 'vue';

const props = defineProps<{
    question: {
        id: string | number;
        text: string;
        hint?: string;
    };
}>();

const emit = defineEmits(['answer']);
const answerText = ref('');

function updateAnswer() {
    emit('answer', { questionId: props.question.id, answer: answerText.value });
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <p class="text-base font-bold text-foreground leading-snug">{{ question.text }}</p>
            <p v-if="question.hint" class="text-xs text-muted-foreground italic mt-1 leading-relaxed">{{ question.hint }}</p>
        </div>
        <div>
            <textarea
                v-model="answerText"
                @input="updateAnswer"
                rows="4"
                placeholder="Type your answer here..."
                class="w-full rounded-xl border-border border p-4 bg-background text-foreground font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none shadow-sm"
            ></textarea>
        </div>
    </div>
</template>
