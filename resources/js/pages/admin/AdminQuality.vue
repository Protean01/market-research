<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, ShieldAlert, Activity, UserX, CheckCircle, Trash2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    flagged_responses: any[];
    low_trust_users: any[];
    stats: {
        total_flagged: number;
        flag_rate: number;
        avg_trust: number;
    };
}>();



const suspendUser = (id: number) => {
    if (confirm('Suspend this user account? They will no longer be able to log in or take surveys.')) {
        router.post(route('admin.quality.suspend', id));
    }
};

const reinstateUser = (id: number) => {
    if (confirm('Reinstate this user? Their trust score will be reset to 70%.')) {
        router.post(route('admin.quality.reinstate', id));
    }
};

const deleteResponse = (id: number) => {
    if (confirm('Delete this flagged response? This will free up capacity for another user to take the survey.')) {
        router.delete(route('admin.quality.delete-response', id));
    }
};
</script>

<template>
    <Head title="Admin · Quality Control" />
    <AppLayout>
        <div class="space-y-8 p-6 max-w-7xl mx-auto w-full">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3">
                    <ShieldAlert class="w-8 h-8 text-rose-600" />
                    Quality Control & Fraud
                </h1>
                <p class="text-base font-medium text-muted-foreground mt-1">
                    Monitor data integrity, speed traps, and respondent trust scores.
                </p>
            </div>

            <!-- Health Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="rounded-3xl border border-border bg-card p-6 shadow-sm flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-rose-600">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Flagged Responses</p>
                        <p class="text-3xl font-black tracking-tighter text-foreground">{{ stats.total_flagged }}</p>
                    </div>
                </div>
                <div class="rounded-3xl border border-border bg-card p-6 shadow-sm flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                        <Activity class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Rejection Rate</p>
                        <p class="text-3xl font-black tracking-tighter text-foreground">{{ stats.flag_rate }}%</p>
                    </div>
                </div>
                <div class="rounded-3xl border border-border bg-card p-6 shadow-sm flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                        <ShieldAlert class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">System Trust Avg</p>
                        <p class="text-3xl font-black tracking-tighter text-foreground">{{ stats.avg_trust }}%</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Low Trust Users -->
                <div class="rounded-3xl border border-border bg-card shadow-sm overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-border bg-muted/30">
                        <h2 class="text-lg font-black text-foreground uppercase tracking-tight flex items-center gap-2">
                            <UserX class="w-5 h-5 text-amber-500" />
                            Low Trust Respondents
                        </h2>
                        <p class="text-xs text-muted-foreground mt-1 font-medium">Users with a trust score below 70%.</p>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto max-h-[500px] p-6">
                        <div v-if="low_trust_users.length === 0" class="text-center py-12 text-muted-foreground font-medium">
                            <CheckCircle class="w-12 h-12 mx-auto mb-3 opacity-20" />
                            No low trust users found.
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="profile in low_trust_users" :key="profile.id" class="flex items-center justify-between p-4 rounded-2xl border border-border bg-background">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-sm">{{ profile.user?.phone_number }}</p>
                                        <span v-if="!profile.user?.is_active" class="text-[9px] bg-rose-100 text-rose-700 px-2 py-0.5 rounded uppercase font-black">Suspended</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="w-16 h-1.5 bg-muted rounded-full overflow-hidden">
                                            <div class="bg-rose-500 h-full" :style="{ width: profile.trust_score + '%' }"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-rose-600">{{ profile.trust_score }}% Trust</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button v-if="profile.user?.is_active" @click="suspendUser(profile.user_id)" class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold transition-colors">Suspend</button>
                                    <button v-else @click="reinstateUser(profile.user_id)" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-colors">Reinstate</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flagged Responses -->
                <div class="rounded-3xl border border-border bg-card shadow-sm overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-border bg-muted/30">
                        <h2 class="text-lg font-black text-foreground uppercase tracking-tight flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5 text-rose-500" />
                            Flagged Submissions
                        </h2>
                        <p class="text-xs text-muted-foreground mt-1 font-medium">Recent responses caught by the Speed Trap or quality filters.</p>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto max-h-[500px] p-6">
                        <div v-if="flagged_responses.length === 0" class="text-center py-12 text-muted-foreground font-medium">
                            <CheckCircle class="w-12 h-12 mx-auto mb-3 opacity-20" />
                            No flagged responses recently.
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="response in flagged_responses" :key="response.id" class="p-4 rounded-2xl border border-rose-200 bg-rose-50/50 dark:bg-rose-900/10 dark:border-rose-900/30">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-rose-600 bg-rose-100 px-2 py-0.5 rounded">{{ response.flag_reason }}</span>
                                    <button @click="deleteResponse(response.id)" class="text-rose-400 hover:text-rose-600" title="Delete Response">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                                <p class="text-sm font-bold text-foreground">{{ response.survey?.title }}</p>
                                <p class="text-xs text-muted-foreground mt-1">User: {{ response.user?.phone_number }}</p>
                                <div class="mt-3 flex items-center justify-between text-[10px] font-bold text-muted-foreground border-t border-rose-100 dark:border-rose-900/30 pt-2">
                                    <span>Time taken: {{ response.time_taken }}s</span>
                                    <span>Quality: {{ response.quality_score }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
