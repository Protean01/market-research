<script setup lang="ts">
import { ref } from 'vue';
import { useHaptics } from '@/composables/useHaptics';
import type { Question } from '@/types/survey';

const props = defineProps<{
    question: Question;
}>();

const emit = defineEmits(['answer']);
const selected = ref<string | null>(null);
const { lightClick } = useHaptics();

function select(label: string) {
    lightClick();
    selected.value = label;
    emit('answer', { questionId: props.question.id, answer: label });
}
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <button
            v-for="opt in (question.options as any[])"
            :key="opt.label"
            type="button"
            @click="select(opt.label)"
            :class="[
                'rounded-2xl overflow-hidden border-2 transition-all duration-200 text-left bg-card',
                selected === opt.label
                    ? 'border-indigo-500 shadow-[0_0_0_3px_rgba(99,102,241,0.15)]'
                    : 'border-border hover:border-indigo-300',
                selected !== null && selected !== opt.label ? 'opacity-50' : '',
            ]"
        >
            <div class="relative bg-muted">
                <img
                    v-if="opt.image_url"
                    :src="opt.image_url"
                    :alt="opt.label"
                    class="w-full h-28 object-cover"
                />
                <div v-else class="w-full h-28 flex items-center justify-center">
                    <span class="text-xs text-muted-foreground font-bold">No image</span>
                </div>
                <div
                    v-if="selected === opt.label"
                    class="absolute top-2 right-2 w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center shadow-lg"
                >
                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <div class="px-3 py-2">
                <span
                    :class="[
                        'text-xs font-bold',
                        selected === opt.label ? 'text-indigo-600' : 'text-foreground',
                    ]"
                >
                    {{ opt.label }}
                </span>
            </div>
        </button>
    </div>
</template>
