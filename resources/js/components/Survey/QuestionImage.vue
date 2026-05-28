<script setup lang="ts">
import { UploadCloud, Image as ImageIcon, X } from 'lucide-vue-next';
import { defineEmits, defineProps, ref } from 'vue';

const props = defineProps<{
    question: {
        id: string | number;
        text: string;
    };
}>();

const emit = defineEmits(['answer']);
const isUploading = ref(false);
const uploadedFile = ref<string | null>(null);

function simulateUpload(event: Event) {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        isUploading.value = true;
        // Simulate network delay
        setTimeout(() => {
            uploadedFile.value = target.files![0].name;
            isUploading.value = false;
            // Emit a mock URL or file blob path to represent the answer
            emit('answer', { questionId: props.question.id, answer: `mock_url_to_${uploadedFile.value}` });
        }, 1500);
    }
}

function clearUpload() {
    uploadedFile.value = null;
    emit('answer', { questionId: props.question.id, answer: null });
}
</script>

<template>
    <div class="space-y-4">
        <p class="text-base font-bold text-foreground leading-snug">{{ question.text }}</p>
        
        <div v-if="!uploadedFile" class="relative block w-full rounded-2xl border-2 border-dashed border-border p-12 text-center hover:bg-muted/50 hover:border-primary/50 transition-all cursor-pointer">
            <input 
                type="file" 
                accept="image/*" 
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                @change="simulateUpload"
                :disabled="isUploading"
            />
            <div class="flex flex-col items-center justify-center space-y-3 pointer-events-none">
                <div class="rounded-full bg-primary/10 p-3">
                    <UploadCloud v-if="!isUploading" class="w-8 h-8 text-primary" />
                    <UploadCloud v-else class="w-8 h-8 text-primary animate-bounce" />
                </div>
                <div v-if="!isUploading">
                    <p class="text-sm font-bold text-foreground">Tap to upload a photo</p>
                    <p class="text-xs font-medium text-muted-foreground mt-1">PNG, JPG up to 5MB</p>
                </div>
                <div v-else>
                    <p class="text-sm font-bold text-primary">Uploading image...</p>
                </div>
            </div>
        </div>

        <div v-else class="flex items-center justify-between rounded-xl border border-primary/20 bg-primary/5 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                    <ImageIcon class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <p class="text-sm font-bold text-foreground truncate max-w-[200px] sm:max-w-xs">{{ uploadedFile }}</p>
                    <p class="text-xs text-primary font-bold">Uploaded successfully</p>
                </div>
            </div>
            <button @click="clearUpload" class="p-2 text-muted-foreground hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                <X class="w-5 h-5" />
            </button>
        </div>
    </div>
</template>
