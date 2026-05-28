<script setup lang="ts">
import { defineEmits, defineProps, ref, watch, computed, onMounted } from 'vue';

import { useHaptics } from '@/composables/useHaptics';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['answer']);
const { lightClick, mediumClick } = useHaptics();

const min = computed(() => Number(props.question?.min ?? 1));
const max = computed(() => Number(props.question?.max ?? 5));
const step = computed(() => Number(props.question?.step ?? 1));

const value = ref(min.value);

const progress = computed(() =>
    ((value.value - min.value) / (max.value - min.value)) * 100
);

onMounted(() => {
    value.value = min.value;
});

function confirmAnswer() {
    mediumClick();
    emit('answer', { questionId: props.question.id, answer: Number(value.value) });
}

watch(
    () => value.value,
    () => {
        lightClick();
    },
);
</script>

<template>
    <div class="space-y-8">
        <div class="space-y-6">
            <div class="flex items-center justify-between px-2">
                <span class="text-xs font-black text-muted-foreground uppercase tracking-widest">{{ min }}</span>
                <span class="text-xs font-black text-muted-foreground uppercase tracking-widest">{{ max }}</span>
            </div>

            <div class="relative px-2">
                <input
                    v-model="value"
                    :min="min"
                    :max="max"
                    :step="step"
                    type="range"
                    class="range-slider w-full h-3 rounded-full appearance-none cursor-pointer"
                    :style="{
                        background: `linear-gradient(to right, #6366f1 0%, #6366f1 ${progress}%, #cbd5e1 ${progress}%, #cbd5e1 100%)`
                    }"
                />
            </div>

            <div class="text-center">
                <div class="inline-flex flex-col items-center justify-center p-6 bg-indigo-50 dark:bg-indigo-500/10 rounded-[2rem] border-2 border-indigo-200 dark:border-indigo-500/20 min-w-[100px]">
                    <span class="text-5xl font-black text-indigo-600 dark:text-indigo-400 leading-none">{{ value }}</span>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-400 mt-2">Selection</span>
                </div>
            </div>
        </div>

        <button
            @click="confirmAnswer"
            class="w-full py-5 rounded-[1.5rem] bg-indigo-600 text-white font-black uppercase tracking-widest text-xs shadow-xl shadow-indigo-600/20 active:scale-95 transition-all"
        >
            Confirm & Continue
        </button>
    </div>
</template>

<style scoped>
.range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #6366f1;
    cursor: pointer;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #6366f1, 0 4px 12px rgba(99, 102, 241, 0.4);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.range-slider::-webkit-slider-thumb:hover {
    transform: scale(1.15);
    box-shadow: 0 0 0 3px #6366f1, 0 6px 16px rgba(99, 102, 241, 0.5);
}

.range-slider::-moz-range-thumb {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #6366f1;
    cursor: pointer;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #6366f1, 0 4px 12px rgba(99, 102, 241, 0.4);
}
</style>
