<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    User, ArrowRight, Coins, Zap, Trophy,
    CheckCircle2, Clock,
    Smartphone, UserPlus, ShieldCheck,
    Sparkles, History, Heart, GraduationCap, Utensils,
    Star, Loader2, MapPin, Info
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    stats: {
        surveys_done: number;
        total_earned: number;
        total_redeemed: number;
        current_points: number;
        current_streak: number;
        longest_streak: number;
    };
    recent_activity: any[];
    featured_surveys: any[];
    ready_to_spin: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const profile = computed(() => page.props.auth.profile);

// --- Tier System (based on surveys completed) ---
const TIERS = [
    { name: 'Bronze',   min: 0,  next: 5,  multiplier: '1.0×', color: 'text-orange-400', icon: Zap },
    { name: 'Silver',   min: 5,  next: 15, multiplier: '1.05×', color: 'text-slate-400',  icon: ShieldCheck },
    { name: 'Gold',     min: 15, next: 30, multiplier: '1.10×', color: 'text-amber-400',  icon: Star },
    { name: 'Platinum', min: 30, next: null, multiplier: '1.15×', color: 'text-indigo-400', icon: Trophy },
];

const reputationTier = computed(() => {
    const count = props.stats.surveys_done;

    for (let i = TIERS.length - 1; i >= 0; i--) {
        if (count >= TIERS[i].min) {
return TIERS[i];
}
    }

    return TIERS[0];
});

const tierProgress = computed(() => {
    const count = props.stats.surveys_done;
    const tier = reputationTier.value;

    if (!tier.next) {
return { pct: 100, done: count, needed: null };
}

    const within = count - tier.min;
    const range  = tier.next - tier.min;

    return { pct: Math.round((within / range) * 100), done: within, needed: range - within };
});

// --- Task 5: Quick-Fire Onboarding ---
const profileFields = [
    { key: 'gender', label: 'What is your gender?', icon: User, options: ['Female', 'Male', 'Other'] },
    { key: 'marital_status', label: 'Marital Status?', icon: Heart, options: ['Single', 'Married', 'Divorced'] },
    { key: 'education', label: 'Highest Education?', icon: GraduationCap, options: ['Secondary', 'Tertiary', 'University'] },
    { key: 'food_preference', label: 'Food Preference?', icon: Utensils, type: 'text', placeholder: 'e.g. Vegetarian' },
    { key: 'location', label: 'Where do you live?', icon: MapPin, type: 'text', placeholder: 'City / Province' },
];

const nextMissingField = computed(() => {
    if (!profile.value) {
return null;
}

    return profileFields.find(f => !profile.value[f.key]);
});

const showReputationInfo = ref(false);

const quickFireValue = ref('');
const isSubmittingQuickFire = ref(false);

async function submitQuickFire() {
    if (!quickFireValue.value || !nextMissingField.value) {
return;
}
    
    isSubmittingQuickFire.value = true;
    router.post('/profile', {
        [nextMissingField.value.key]: quickFireValue.value
    }, {
        onSuccess: () => {
            toast.success('Profile updated! +5 bonus pts');
            quickFireValue.value = '';
        },
        onFinish: () => {
            isSubmittingQuickFire.value = false;
        }
    });
}

const dashboardStats = computed(() => [
    { label: 'Tier', value: reputationTier.value.name, icon: ShieldCheck, color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-900/20', border: 'border-emerald-100 dark:border-emerald-900/30' },
    { label: 'Streak', value: props.stats.current_streak + 'd', icon: Zap, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-900/20', border: 'border-amber-100 dark:border-amber-900/30' },
    { label: 'Surveys', value: props.stats.surveys_done, icon: CheckCircle2, color: 'text-indigo-500', bg: 'bg-indigo-50 dark:bg-indigo-900/20', border: 'border-indigo-100 dark:border-indigo-900/30' },
]);

function getTimeAgo(dateString: string) {
    const seconds = Math.floor((new Date().getTime() - new Date(dateString).getTime()) / 1000);

    if (seconds < 60) {
return 'Just now';
}

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
return `${minutes}m ago`;
}

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
return `${hours}h ago`;
}

    return `${Math.floor(hours / 24)}d ago`;
}

const quickActions = computed(() => [
    { title: 'Surveys', href: '/surveys', icon: Zap, color: 'bg-indigo-600' },
    { title: 'Points', href: '/wallet', icon: Coins, color: 'bg-emerald-600' },
    { title: 'Profile', href: '/profile', icon: User, color: 'bg-amber-600' },
    { title: 'Support', href: 'https://wa.me/260972829811', icon: Smartphone, color: 'bg-rose-600' },
]);
</script>

<template>
    <Head title="Home" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 md:gap-12 p-4 md:p-8 max-w-7xl mx-auto w-full pb-32 overflow-x-hidden">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 md:gap-6 px-1 md:px-2">
                <div>
                    <h2 class="text-2xl md:text-4xl font-black tracking-tight text-foreground flex items-center gap-3">
                        Welcome, {{ user.phone_number || user.name?.split(' ')?.[0] || 'there' }}
                        <Sparkles class="w-5 h-5 md:w-6 md:h-6 text-amber-500 fill-amber-500/10" />
                    </h2>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-muted-foreground font-medium text-sm md:text-lg">Your market research headquarters.</p>
                        <span class="hidden sm:inline-block h-1 w-1 rounded-full bg-border"></span>
                        <div :class="[reputationTier.color, 'text-[10px] font-black uppercase tracking-widest flex items-center gap-1']">
                            <component :is="reputationTier.icon" class="w-3 h-3" />
                            {{ reputationTier.name }} Status
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div v-for="stat in dashboardStats" :key="stat.label" class="hidden sm:flex items-center gap-2 px-4 py-2 bg-card border border-border rounded-2xl shadow-sm">
                        <div :class="['p-1.5 rounded-lg', stat.bg, stat.border, 'border']">
                            <component :is="stat.icon" :class="['h-3 w-3', stat.color]" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-foreground leading-none">{{ stat.value }}</span>
                            <span class="text-[8px] font-black text-muted-foreground uppercase tracking-widest">{{ stat.label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero: Balance & Reputation -->
            <div class="grid gap-6 lg:grid-cols-12">
                <div class="lg:col-span-8 relative overflow-hidden rounded-[2.5rem] md:rounded-[3rem] bg-gradient-to-br from-indigo-600 to-violet-700 dark:from-indigo-950 dark:to-slate-950 p-6 md:p-10 text-white shadow-2xl border border-white/10 dark:border-indigo-500/20">
                    <div class="absolute top-0 right-0 p-8 md:p-12 opacity-10">
                        <Coins class="w-32 h-32 md:w-64 md:h-64" />
                    </div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6 md:gap-8">
                        <div class="flex flex-col md:flex-row md:items-center gap-8 md:gap-12">
                            <div class="space-y-1">
                                <p class="text-[9px] font-black tracking-[0.4em] text-white/60 uppercase">Points Balance</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl sm:text-6xl font-black tracking-tighter leading-none text-amber-400">{{ stats.current_points }}</span>
                                    <span class="text-xs font-bold text-white/60 uppercase tracking-widest">pts</span>
                                </div>
                            </div>
                            <div class="hidden md:block w-px h-12 bg-white/20"></div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-black tracking-[0.4em] text-white/60 uppercase">Surveys Done</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl sm:text-4xl font-black tracking-tighter leading-none opacity-80">{{ stats.surveys_done }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 w-full md:w-auto">
                            <Link href="/wallet" class="flex items-center justify-center gap-3 p-3 px-6 rounded-2xl md:rounded-3xl bg-white/10 border border-white/10 hover:bg-white/20 backdrop-blur-md transition-all group">
                                <Coins class="w-4 h-4 text-white group-hover:scale-110 transition-transform" />
                                <span class="text-[9px] font-black uppercase tracking-widest text-white">Tier Points</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Reputation Meter (Task 3) -->
                <div class="lg:col-span-4 bg-card border border-border rounded-[2.5rem] md:rounded-[3rem] p-8 flex flex-col justify-between relative overflow-hidden shadow-sm">
                    <button
                        @click="showReputationInfo = true"
                        class="absolute top-6 right-6 z-20 text-muted-foreground hover:text-foreground transition-colors"
                        aria-label="How reputation works"
                    >
                        <Info class="w-5 h-5" />
                    </button>

                    <div class="space-y-1 relative z-10">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Reputation Level</h3>
                        <p :class="[reputationTier.color, 'text-2xl font-black uppercase tracking-tight']">{{ reputationTier.name }}</p>
                    </div>

                    <div class="mt-8 space-y-4 relative z-10">
                        <div class="flex justify-between items-end">
                            <span class="text-[9px] font-black text-muted-foreground uppercase">Tier Progress</span>
                            <span class="text-lg font-black text-foreground">{{ tierProgress.pct }}%</span>
                        </div>
                        <div class="h-4 w-full bg-muted rounded-full p-1 border border-border overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-400 transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(79,70,229,0.3)]"
                                :style="{ width: tierProgress.pct + '%' }"
                            ></div>
                        </div>
                        <p v-if="tierProgress.needed" class="text-[10px] text-muted-foreground font-medium leading-relaxed italic">
                            {{ tierProgress.needed }} more survey{{ tierProgress.needed === 1 ? '' : 's' }} to next tier · {{ reputationTier.multiplier }} bonus active
                        </p>
                        <p v-else class="text-[10px] text-muted-foreground font-medium leading-relaxed italic">
                            Max tier reached — {{ reputationTier.multiplier }} point bonus on every survey.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center gap-4 relative z-10">
                        <div class="text-center">
                            <p class="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Current Streak</p>
                            <p class="text-xl font-black text-foreground flex items-center gap-1">
                                <Zap class="w-4 h-4 text-amber-400" />{{ stats.current_streak }}d
                            </p>
                        </div>
                        <div class="w-px h-8 bg-border"></div>
                        <div class="text-center">
                            <p class="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Best Streak</p>
                            <p class="text-xl font-black text-foreground">{{ stats.longest_streak }}d</p>
                        </div>
                    </div>
                    
                    <component :is="reputationTier.icon" class="absolute -right-4 -bottom-4 w-32 h-32 opacity-[0.03] rotate-12" />
                </div>
            </div>

            <!-- Quick-Fire Onboarding (Task 5) -->
            <transition enter-active-class="transition duration-500" enter-from-class="opacity-0 scale-95" leave-to-class="opacity-0 scale-95">
                <div v-if="nextMissingField" class="relative group overflow-hidden rounded-[2.5rem] bg-amber-50 dark:bg-amber-900/10 border-2 border-amber-200/50 dark:border-amber-900/30 p-8 shadow-xl shadow-amber-500/5">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-8">
                        <div class="h-16 w-16 rounded-[1.5rem] bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/20 group-hover:scale-110 transition-transform">
                            <component :is="nextMissingField.icon" class="w-8 h-8" />
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest bg-white dark:bg-amber-900/40 px-2 py-0.5 rounded-full">+5 Bonus PTS</span>
                                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Profile Boost</span>
                            </div>
                            <h3 class="text-xl font-black text-foreground uppercase tracking-tight">{{ nextMissingField.label }}</h3>
                        </div>
                        <div class="w-full md:w-auto flex flex-wrap items-center gap-3">
                            <template v-if="nextMissingField.options">
                                <button 
                                    v-for="opt in nextMissingField.options" 
                                    :key="opt"
                                    @click="quickFireValue = opt; submitQuickFire();"
                                    :disabled="isSubmittingQuickFire"
                                    class="px-6 py-3 rounded-xl bg-white dark:bg-zinc-800 border border-border text-xs font-black uppercase tracking-widest hover:border-amber-500 transition-all active:scale-95 disabled:opacity-50 shadow-sm"
                                >
                                    {{ opt }}
                                </button>
                            </template>
                            <template v-else>
                                <input 
                                    v-model="quickFireValue" 
                                    :placeholder="nextMissingField.placeholder"
                                    class="px-6 py-3 rounded-xl bg-white dark:bg-zinc-800 border border-border text-xs font-bold w-48 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all outline-none"
                                />
                                <button 
                                    @click="submitQuickFire"
                                    :disabled="!quickFireValue || isSubmittingQuickFire"
                                    class="bg-amber-500 text-white p-3 rounded-xl shadow-lg hover:bg-amber-600 transition-all active:scale-95 disabled:opacity-50"
                                >
                                    <ArrowRight v-if="!isSubmittingQuickFire" class="w-5 h-5" />
                                    <Loader2 v-else class="w-5 h-5 animate-spin" />
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="absolute -right-10 -bottom-10 opacity-[0.02]">
                        <UserPlus class="w-64 h-64" />
                    </div>
                </div>
            </transition>

            <!-- Content Layout -->
            <div class="grid gap-8 md:gap-10 lg:grid-cols-12">

                <!-- Main Content: Jobs & Actions -->
                <div class="lg:col-span-7 space-y-8 md:space-y-10">
                    
                    <!-- Ready to Spin (Prize Draws) -->
                    <section v-if="ready_to_spin && ready_to_spin.length > 0" class="space-y-4 animate-in fade-in slide-in-from-top-4 duration-700">
                        <div class="flex items-center gap-2 px-1 md:px-2">
                            <span class="flex h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <h3 class="text-[10px] md:text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-[0.3em]">
                                Jackpot Opportunity!
                            </h3>
                        </div>
                        
                        <div class="grid gap-3">
                            <Link
                                v-for="draw in ready_to_spin"
                                :key="draw.id"
                                :href="'/surveys/' + draw.id + '/slot-machine'"
                                class="group flex items-center gap-4 p-5 rounded-[2rem] bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-xl shadow-amber-500/20 hover:scale-[1.02] transition-all active:scale-95 border-b-4 border-amber-700"
                            >
                                <div class="h-12 w-12 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0 shadow-inner">
                                    <Trophy class="w-7 h-7 animate-bounce" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-lg font-black uppercase tracking-tight leading-none mb-1">
                                        {{ draw.reward_type === 'airtime' ? 'CLAIM YOUR AIRTIME!' : 'READY TO SPIN!' }}
                                    </h4>
                                    <p class="text-[10px] font-bold text-white/80 uppercase tracking-widest truncate">{{ draw.title }}</p>
                                </div>
                                <div class="px-5 py-2 rounded-xl bg-white dark:bg-amber-900 text-amber-600 dark:text-amber-100 text-[10px] font-black uppercase tracking-widest shadow-lg">
                                    SPIN NOW
                                </div>
                            </Link>
                        </div>
                    </section>

                    <!-- Featured Jobs -->
                    <section v-if="Array.isArray(featured_surveys) && featured_surveys.length > 0" class="space-y-4 md:space-y-6">
                        <div class="flex items-center justify-between px-1 md:px-2">
                            <h3 class="text-[10px] md:text-xs font-black text-muted-foreground uppercase tracking-[0.3em] flex items-center gap-2">
                                <Trophy class="w-3.5 h-3.5 md:w-4 md:h-4 text-indigo-500" />
                                Recommended For You
                            </h3>
                            <Link href="/surveys" class="text-[9px] md:text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">View All</Link>
                        </div>
                        
                        <div class="grid gap-3 md:gap-4">
                            <Link
                                v-for="survey in featured_surveys"
                                :key="survey.id"
                                :href="'/surveys/' + survey.id"
                                class="group flex items-center gap-4 md:gap-6 p-4 md:p-6 rounded-[1.5rem] md:rounded-[2.5rem] border border-border bg-card shadow-sm hover:shadow-xl hover:border-indigo-500/30 transition-all active:scale-[0.98] min-w-0 overflow-hidden"
                            >
                                <div class="h-12 w-12 md:h-16 md:w-16 rounded-2xl md:rounded-3xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 group-hover:scale-110 transition-transform">
                                    <Zap class="w-6 h-6 md:w-8 md:h-8 fill-current opacity-80" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 md:gap-3 mb-1 md:mb-2">
                                        <div class="flex items-center gap-1 rounded-lg md:rounded-xl bg-emerald-50 dark:bg-emerald-900/20 px-2 md:px-3 py-1 text-[9px] md:text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-tight border border-emerald-100 dark:border-emerald-900/30">
                                            {{ survey.reward_points }} PTS
                                        </div>
                                        <div class="flex items-center gap-1 rounded-lg md:rounded-xl bg-muted px-2 md:px-3 py-1 text-[8px] md:text-[9px] font-black text-muted-foreground uppercase tracking-widest">
                                            <Clock class="w-2.5 h-2.5 md:w-3 md:h-3" />
                                            {{ survey.estimated_time || 5 }} MIN
                                        </div>
                                    </div>
                                    <h4 class="text-base md:text-xl font-black text-foreground truncate uppercase tracking-tight leading-none group-hover:text-indigo-600 transition-colors">{{ survey.title }}</h4>
                                </div>
                                <div class="h-10 w-10 md:h-12 md:w-12 rounded-xl md:rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shrink-0 group-hover:translate-x-1 transition-transform">
                                    <ArrowRight class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </Link>
                        </div>
                    </section>

                    <!-- Quick Shortcuts -->
                    <section class="space-y-4 md:space-y-6">
                        <h3 class="text-[10px] md:text-xs font-black text-muted-foreground uppercase tracking-[0.3em] px-1 md:px-2">Quick Access</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4">
                            <Link
                                v-for="action in quickActions"
                                :key="action.title"
                                :href="action.href"
                                class="group flex flex-col items-center gap-2 p-4 md:p-5 rounded-[1.5rem] md:rounded-[2rem] border border-border bg-card hover:bg-muted/50 transition-all active:scale-95 shadow-sm text-center min-w-0 overflow-hidden"
                            >
                                <div :class="[action.color, 'h-10 w-10 md:h-12 md:w-12 rounded-xl md:rounded-2xl flex items-center justify-center text-white shadow-lg group-hover:rotate-6 transition-transform shrink-0']">
                                    <component :is="action.icon" class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                                <div class="flex flex-col items-center">
                                    <span class="text-[11px] md:text-xs font-black text-foreground uppercase tracking-tight leading-none">{{ action.title }}</span>
                                    <span class="text-[7px] md:text-[8px] font-black text-muted-foreground uppercase tracking-widest mt-1">Open</span>
                                </div>
                            </Link>
                        </div>
                    </section>
                </div>

                <!-- Right Side: Activity & Community -->
                <div class="lg:col-span-5 flex flex-col gap-8 md:gap-10 lg:justify-between">
                    <!-- Activity Stream -->
                    <section class="space-y-4 md:space-y-6">
                        <div class="flex items-center justify-between px-1 md:px-2">
                            <h3 class="text-[10px] md:text-xs font-black text-muted-foreground uppercase tracking-[0.3em] flex items-center gap-2">
                                <History class="w-3.5 h-3.5 md:w-4 md:h-4" />
                                Live Activity
                            </h3>
                            <Link href="/wallet" class="text-[9px] md:text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">Full History</Link>
                        </div>

                        <div class="rounded-[2rem] md:rounded-[3rem] border border-border bg-card overflow-hidden shadow-sm">
                            <div v-if="recent_activity.length > 0" class="divide-y divide-border/50">
                                <div v-for="tx in recent_activity" :key="tx.id" class="group flex items-center justify-between p-4 md:p-6 hover:bg-muted/30 transition-all">
                                    <div class="flex items-center gap-3 md:gap-4">
                                        <div :class="[
                                            'flex h-10 w-10 md:h-12 md:w-12 items-center justify-center rounded-xl transition-transform group-hover:scale-110',
                                            tx.type === 'earn' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20' : 'bg-rose-50 text-rose-600 dark:bg-rose-900/20'
                                        ]">
                                            <Zap v-if="tx.type === 'earn'" class="h-5 w-5 md:h-6 md:w-6 fill-current" />
                                            <Smartphone v-else class="h-5 w-5 md:h-6 md:w-6" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] md:text-[11px] font-black text-foreground uppercase truncate tracking-tight">{{ tx.type === 'earn' ? (tx.meta?.survey_title || 'Reward') : 'Payout' }}</p>
                                            <p class="text-[8px] md:text-[9px] text-muted-foreground font-bold uppercase">{{ getTimeAgo(tx.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div :class="[tx.type === 'earn' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400', 'text-xs md:text-sm font-black tracking-tighter']">
                                        {{ tx.type === 'earn' ? '+' : '-' }}{{ tx.points }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-12 md:p-16 text-center text-muted-foreground">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-40 italic">No activity yet</p>
                            </div>
                        </div>
                    </section>

                    <!-- Referral Card -->
                    <div class="group relative overflow-hidden rounded-[2rem] md:rounded-[3rem] bg-zinc-900 dark:bg-black p-8 md:p-10 text-white shadow-2xl transition-all">
                        <div class="relative z-10">
                            <div class="h-12 w-12 md:h-14 md:w-14 rounded-xl md:rounded-2xl bg-white/5 flex items-center justify-center mb-4 md:mb-6 border border-white/10 group-hover:scale-110 transition-transform">
                                <UserPlus class="w-8 h-8 text-indigo-400" />
                            </div>
                            <h3 class="font-black uppercase tracking-tight text-xl md:text-2xl mb-1.5 md:mb-2">Share the love</h3>
                            <p class="text-zinc-500 text-xs md:text-sm font-medium mb-6 md:mb-8 leading-relaxed">Refer a friend and earn <span class="text-white font-black">50 points</span> when they join.</p>
                            <button class="w-full py-4 md:py-5 rounded-xl md:rounded-2xl bg-indigo-600 text-white font-black uppercase tracking-widest text-[10px] md:text-xs hover:bg-indigo-500 transition-all active:scale-95 shadow-xl shadow-indigo-600/20">
                                Copy Invite Link
                            </button>
                        </div>
                        <div class="absolute -bottom-10 -right-10 opacity-5">
                            <UserPlus class="w-40 h-40 md:w-48 md:h-48 group-hover:scale-125 transition-transform duration-700" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>

    <!-- Reputation Info Modal -->
    <Dialog v-model:open="showReputationInfo">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="text-lg font-black uppercase tracking-tight">How Reputation Works</DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Complete surveys to climb tiers and earn bigger point bonuses.
                </DialogDescription>
            </DialogHeader>

            <div class="mt-2 space-y-2">
                <div
                    v-for="tier in TIERS"
                    :key="tier.name"
                    :class="[
                        'flex items-center gap-4 px-4 py-3 rounded-xl transition-colors',
                        tier.name === reputationTier.name ? 'bg-muted' : ''
                    ]"
                >
                    <component :is="tier.icon" :class="['w-5 h-5 shrink-0', tier.color]" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black uppercase tracking-tight" :class="tier.color">{{ tier.name }}</p>
                        <p class="text-[10px] text-muted-foreground font-medium">
                            {{ tier.min === 0 ? 'Starting tier' : tier.min + '+ surveys completed' }}
                        </p>
                    </div>
                    <span class="text-xs font-black text-foreground shrink-0">{{ tier.multiplier }} bonus</span>
                    <span v-if="tier.name === reputationTier.name" class="text-[9px] font-black text-indigo-500 uppercase tracking-widest shrink-0">You</span>
                </div>
            </div>

            <p class="mt-4 text-[10px] text-muted-foreground italic leading-relaxed">
                Your streak resets if you miss a day — keep it going for consistent bonuses.
            </p>
        </DialogContent>
    </Dialog>
</template>
