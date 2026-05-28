<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight, Clock, RefreshCw, Sparkles, Star,
    Users, Bell, CloudOff, X,
    Zap, Trophy, Smartphone
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { subscribeToPush } from '@/composables/usePush';
import AppLayout from '@/layouts/AppLayout.vue';
import { survey as surveyRoute } from '@/routes';
import { useSurveyStore } from '@/stores/survey';

import type { Survey } from '@/types/survey';

const props = defineProps<{
    surveys: Survey[];
}>();

const surveyStore = useSurveyStore();
const showPushPrompt = ref(false);
const requestingPush = ref(false);

onMounted(async () => {
    if (props.surveys && props.surveys.length > 0) {
        surveyStore.available = props.surveys;
    } else {
        await surveyStore.fetchSurveys();
    }

    if ('Notification' in window && Notification.permission === 'default') {
        setTimeout(() => showPushPrompt.value = true, 3000);
    }
});

async function handleEnablePush() {
    requestingPush.value = true;

    try {
        await subscribeToPush();
        showPushPrompt.value = false;
    } finally {
        requestingPush.value = false;
    }
}

const isLoading = computed(() => surveyStore.loading);
const errorMessage = computed(() => surveyStore.error);

function getEstimatedTime(survey: Survey) {
    return survey.estimated_time ? `${survey.estimated_time}m` : '5m';
}

function isNew(survey: Survey) {
    const diff = new Date().getTime() - new Date(survey.created_at).getTime();

    return diff < 24 * 60 * 60 * 1000;
}
</script>

<template>
    <Head title="Available Surveys" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-8 p-6 max-w-6xl mx-auto w-full pb-32">
            <!-- Push Notification Prompt -->
            <transition enter-active-class="transition duration-500" enter-from-class="opacity-0 -translate-y-4" leave-to-class="opacity-0 scale-95">
                <div v-if="showPushPrompt" class="relative overflow-hidden rounded-[2.5rem] bg-indigo-600 p-6 text-white shadow-2xl">
                    <button @click="showPushPrompt = false" class="absolute right-4 top-4 p-2 text-indigo-200 hover:text-white transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="h-16 w-16 rounded-2xl bg-white/10 flex items-center justify-center text-white backdrop-blur-sm shadow-inner">
                            <Bell class="h-8 w-8" />
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h3 class="text-lg font-black uppercase tracking-tight">Earn faster!</h3>
                            <p class="text-sm font-medium text-indigo-100 opacity-90">Get instant alerts when new high-paying surveys match your profile.</p>
                        </div>
                        <button @click="handleEnablePush" :disabled="requestingPush" class="bg-white text-indigo-600 px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-50 shadow-lg transition-all active:scale-95">
                            {{ requestingPush ? 'ENABLING...' : 'Enable Now' }}
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Title Section -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3 uppercase">
                        Available Surveys
                        <Sparkles class="w-6 h-6 text-amber-500 fill-amber-500/10" />
                    </h2>
                    <p class="text-muted-foreground mt-1 font-medium text-lg">Pick a survey below to start earning.</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <button 
                        @click="surveyStore.fetchSurveys()" 
                        class="p-3 rounded-2xl bg-card border border-border text-muted-foreground hover:text-indigo-600 transition-all shadow-sm active:rotate-180 duration-500"
                        title="Refresh List"
                    >
                        <RefreshCw class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Offline Sync -->
            <div v-if="surveyStore.offlineQueue.length > 0" class="flex items-center justify-between rounded-2xl bg-amber-500 p-4 text-white shadow-lg shadow-amber-500/20">
                <div class="flex items-center gap-3">
                    <CloudOff class="w-5 h-5" />
                    <span class="text-sm font-black uppercase tracking-tight">{{ surveyStore.offlineQueue.length }} tasks pending sync</span>
                </div>
                <button v-if="surveyStore.isOnline" @click="surveyStore.syncOfflineSubmissions()" class="bg-white text-amber-600 px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm">
                    <CloudOff class="w-4 h-4" />
                </button>
            </div>

            <!-- Survey Grid -->
            <div v-if="isLoading" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="i in 6" :key="i" class="rounded-[2.5rem] border border-border p-8 bg-card space-y-6">
                    <div class="flex justify-between"><Skeleton class="h-6 w-24 rounded-full" /><Skeleton class="h-6 w-12 rounded-full" /></div>
                    <Skeleton class="h-10 w-full rounded-xl" /><Skeleton class="h-16 w-full rounded-2xl" />
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="errorMessage" class="p-12 text-center bg-rose-50 dark:bg-rose-950/20 rounded-[2.5rem] border border-rose-100 dark:border-rose-900/30">
                <CloudOff class="w-12 h-12 text-rose-500 mx-auto mb-4" />
                <h3 class="text-lg font-black text-rose-900 dark:text-rose-400 uppercase">Unable to load surveys</h3>
                <p class="text-sm text-rose-700 dark:text-rose-500/70 mt-1">{{ errorMessage }}</p>
                <button @click="surveyStore.fetchSurveys()" class="mt-6 px-8 py-3 bg-rose-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-rose-600/20">Try Again</button>
            </div>

            <div v-else-if="surveyStore.available.length === 0" class="flex flex-col items-center justify-center rounded-[3rem] border border-dashed border-border p-20 text-center bg-muted/5">
                <div class="h-24 w-24 rounded-full bg-muted flex items-center justify-center mb-8">
                    <Star class="h-10 w-10 text-muted-foreground/30" />
                </div>
                <h3 class="text-2xl font-black text-foreground uppercase tracking-tight mb-3">All Caught Up!</h3>
                <p class="text-muted-foreground max-w-sm font-medium leading-relaxed mb-10">We're looking for more surveys that match your profile. Check back in a few hours or invite a friend!</p>
                <Link href="/profile" class="bg-indigo-600 text-white px-10 py-4 rounded-[1.5rem] text-sm font-black uppercase tracking-widest shadow-xl shadow-indigo-600/20 hover:scale-105 transition-all active:scale-95">Update Profile</Link>
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="survey in surveyStore.available"
                    :key="survey.id"
                    :href="surveyRoute(survey.id).url"
                    class="group relative flex flex-col justify-between rounded-[2.5rem] border border-border bg-card p-8 shadow-sm transition-all hover:shadow-2xl hover:border-indigo-500/30 hover:-translate-y-1 overflow-hidden"
                >
                    <div v-if="isNew(survey)" class="absolute top-0 right-0">
                        <div class="bg-indigo-600 text-white text-[9px] font-black px-6 py-1.5 rounded-bl-3xl uppercase tracking-[0.2em] shadow-lg shadow-indigo-600/20">NEW SURVEY</div>
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <!-- Points Reward -->
                            <div v-if="survey.reward_type === 'points' || !survey.reward_type" class="flex items-center gap-1.5 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tight border border-emerald-100 dark:border-emerald-900/30">
                                <Zap class="w-3.5 h-3.5 fill-current" />
                                {{ survey.reward_points ?? Math.round((survey.reward_amount ?? 0) * 10) }} PTS
                            </div>
                            <!-- Airtime Reward -->
                            <div v-else-if="survey.reward_type === 'airtime'" class="flex items-center gap-1.5 rounded-2xl bg-blue-50 dark:bg-blue-900/20 px-4 py-2 text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-tight border border-blue-100 dark:border-blue-900/30">
                                <Smartphone class="w-3.5 h-3.5" />
                                K{{ survey.reward_amount }} Airtime
                            </div>
                            <!-- Prize Draw Reward -->
                            <div v-else-if="survey.reward_type === 'prize_draw'" class="flex items-center gap-1.5 rounded-2xl bg-amber-50 dark:bg-amber-900/20 px-4 py-2 text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-tight border border-amber-100 dark:border-amber-900/30">
                                <Trophy class="w-3.5 h-3.5" />
                                Win: {{ survey.prize_name || 'Grand Prize' }}
                            </div>

                            <div class="flex items-center gap-1.5 rounded-2xl bg-muted px-4 py-2 text-[10px] font-black text-muted-foreground uppercase tracking-[0.1em]">
                                <Clock class="w-3 h-3" />
                                {{ getEstimatedTime(survey) }}
                            </div>
                        </div>
                        <h3 class="text-2xl font-black text-foreground mb-4 group-hover:text-indigo-600 transition-colors leading-[1.1] uppercase tracking-tight">
                            {{ survey.title }}
                        </h3>
                        <p class="text-sm text-muted-foreground line-clamp-3 leading-relaxed font-medium mb-8 opacity-80">
                            {{ survey.description }}
                        </p>
                    </div>
                    
                    <div class="mt-auto flex items-center justify-between pt-8 border-t border-border/50">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-[9px] font-black text-muted-foreground uppercase tracking-widest">
                                <Users class="w-3 h-3 text-indigo-500" />
                                <span>{{ survey.response_count ?? 0 }} / {{ survey.response_cap ?? 'UNLIMITED' }}</span>
                            </div>
                            <div class="w-24 h-1 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" :style="{ width: ((survey.response_count / (survey.response_cap || 1000)) * 100) + '%' }"></div>
                            </div>
                        </div>
                        <div class="h-12 w-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                            <ArrowRight class="w-6 h-6" />
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
