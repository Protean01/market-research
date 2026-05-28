<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { 
    User, MapPin, 
    CheckCircle2, Loader2, LogOut, Calendar,
    ShieldCheck, Smartphone, ChevronRight,
    Heart, Utensils, Lock, Zap
} from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useHaptics } from '@/composables/useHaptics';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/authStore';
import { useProfileStore } from '@/stores/profileStore';
import { useSettingsStore } from '@/stores/settingsStore';

const props = defineProps({
    profile: {
        type: Object,
        default: () => ({}),
    },
});

const profileStore = useProfileStore();
const authStore = useAuthStore();
const settingsStore = useSettingsStore();
profileStore.hydrate(props.profile);
const { lightClick, success: successHaptic, error: errorHaptic } = useHaptics();

const saving = ref(false);
const changingPassword = ref(false);
const showPasswordForm = ref(false);
const showSuccess = ref(false);

const form = reactive({
    birth_year: props.profile?.birth_year || '',
    gender: props.profile?.gender || '',
    location: props.profile?.location || '',
    employment: props.profile?.employment || '',
    income_band: props.profile?.income_band || '',
    marital_status: props.profile?.marital_status || '',
    education: props.profile?.education || '',
    occupation: props.profile?.occupation || '',
    food_preference: props.profile?.food_preference || '',
});

const passwordForm = reactive({
    password: '',
    password_confirmation: '',
});



async function save() {
    lightClick();
    saving.value = true;

    router.post('/profile', form, {
        onSuccess: () => {
            successHaptic();
            showSuccess.value = true;
            setTimeout(() => showSuccess.value = false, 3000);
        },
        onError: (errors) => {
            errorHaptic();
            const firstError = Object.values(errors)[0] || 'Please check your inputs.';
            toast.error(String(firstError));
        },
        onFinish: () => {
            saving.value = false;
        }
    });
}

async function updatePassword() {
    if (!passwordForm.password) {
return;
}

    if (passwordForm.password !== passwordForm.password_confirmation) {
        toast.error('Passwords do not match.');

        return;
    }

    lightClick();
    changingPassword.value = true;

    router.post('/auth/set-password', passwordForm, {
        onSuccess: () => {
            successHaptic();
            toast.success('Password updated successfully.');
            passwordForm.password = '';
            passwordForm.password_confirmation = '';
            showPasswordForm.value = false;
        },
        onError: (errors) => {
            errorHaptic();
            const msg = errors.password?.[0] || 'Unable to update password.';
            toast.error(msg);
        },
        onFinish: () => {
            changingPassword.value = false;
        }
    });
}

async function handleLogout() {
    lightClick();

    if (confirm('Are you sure you want to sign out?')) {
        router.post('/logout', {}, {
            onFinish: () => {
                authStore.token = null;
                authStore.user = null;
                localStorage.removeItem('pwa_token');
            }
        });
    }
}
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-10 p-6 max-w-4xl mx-auto w-full pb-32">
            
            <!-- Profile Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-foreground uppercase">Your Identity</h1>
                    <p class="mt-1 text-muted-foreground font-medium text-lg">
                        Manage your personal data and preferences.
                    </p>
                </div>
                
                <div v-if="showSuccess" class="flex items-center gap-2 text-emerald-600 font-black bg-emerald-50 dark:bg-emerald-900/20 px-6 py-2.5 rounded-2xl border border-emerald-100 dark:border-emerald-900/50 animate-in fade-in slide-in-from-top-2 uppercase text-[10px] tracking-widest">
                    <CheckCircle2 class="w-4 h-4" />
                    Updates Saved
                </div>
            </div>

            <div class="grid gap-10">
                <!-- Trust Card -->
                <div class="rounded-[2.5rem] bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-700 p-8 text-white shadow-2xl border border-white/15 relative overflow-hidden">
                    <div class="relative z-10 flex items-start gap-6">
                        <div class="rounded-2xl bg-white/10 p-4 text-white backdrop-blur-xl border border-white/20 shadow-lg">
                            <ShieldCheck class="w-8 h-8 text-white" />
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-xl font-black uppercase tracking-tight text-white">Your data is secure</h3>
                            <p class="text-sm text-white/80 font-medium leading-relaxed max-w-md">We use your profile only to match you with relevant surveys. Your identity is always anonymized before being shared.</p>
                        </div>
                    </div>
                    <div class="absolute -bottom-10 -right-10 opacity-10">
                        <User class="w-48 h-48 text-white" />
                    </div>
                </div>

                <!-- Personal Details Form -->
                <div class="rounded-[3rem] border border-border bg-card p-10 shadow-sm">
                    <h2 class="text-xs font-black text-muted-foreground uppercase tracking-[0.3em] mb-10 border-b border-border pb-4">Personal Details</h2>
                    
                    <div class="grid gap-8 md:grid-cols-2">
                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                                <Calendar class="w-3 h-3 text-indigo-500" />
                                Birth Year
                            </label>
                            <input v-model="form.birth_year" type="number" class="w-full rounded-2xl border border-border bg-muted/30 px-5 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all" />
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                                <User class="w-3 h-3 text-indigo-500" />
                                Gender
                            </label>
                            <select v-model="form.gender" class="w-full rounded-2xl border border-border bg-muted/30 px-5 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                <option value="">Select Gender</option>
                                <option>Female</option>
                                <option>Male</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                                <MapPin class="w-3 h-3 text-indigo-500" />
                                Location
                            </label>
                            <select v-model="form.location" class="w-full rounded-2xl border border-border bg-muted/30 px-5 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                <option value="">Select City / Province</option>
                                <option>Lusaka</option>
                                <option>Ndola</option>
                                <option>Kitwe</option>
                                <option>Livingstone</option>
                                <option>Kabwe</option>
                                <option>Chipata</option>
                                <option>Solwezi</option>
                                <option>Kasama</option>
                                <option>Mansa</option>
                                <option>Mongu</option>
                                <option>Chingola</option>
                                <option>Mufulira</option>
                                <option>Luanshya</option>
                                <option>Mazabuka</option>
                                <option>Choma</option>
                            </select>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                                <Heart class="w-3 h-3 text-indigo-500" />
                                Marital Status
                            </label>
                            <select v-model="form.marital_status" class="w-full rounded-2xl border border-border bg-muted/30 px-5 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                <option value="">Select Status</option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Divorced</option>
                            </select>
                        </div>

                        <div class="space-y-3 md:col-span-2">
                            <label class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                                <Utensils class="w-3 h-3 text-indigo-500" />
                                Food Preference
                            </label>
                            <input v-model="form.food_preference" placeholder="e.g. Vegetarian, No Pork" class="w-full rounded-2xl border border-border bg-muted/30 px-5 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all" />
                        </div>
                    </div>

                    <div class="mt-12 pt-10 border-t border-border/50">
                        <button @click="save" :disabled="saving" class="w-full flex items-center justify-center gap-3 rounded-2xl bg-indigo-600 px-8 py-5 font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/20 hover:bg-indigo-500 active:scale-95 transition-all disabled:opacity-50">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            <span v-else>Update Profile</span>
                        </button>
                    </div>
                </div>

                <!-- Account & Security Section -->
                <div class="space-y-4">
                    <h2 class="text-xs font-black text-muted-foreground uppercase tracking-[0.3em] px-4">Account & Security</h2>
                    <div class="rounded-[2.5rem] border border-border bg-card overflow-hidden divide-y divide-border/50 shadow-sm">
                        <!-- Change Password Dropdown -->
                        <div class="w-full">
                            <button @click="showPasswordForm = !showPasswordForm" class="w-full flex items-center justify-between p-6 hover:bg-muted/30 transition-all text-left">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-xl bg-muted flex items-center justify-center text-muted-foreground">
                                        <Lock class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-foreground uppercase tracking-tight">Change Password</p>
                                        <p class="text-[10px] text-muted-foreground font-medium uppercase tracking-widest">Update your login credentials</p>
                                    </div>
                                </div>
                                <ChevronRight :class="[showPasswordForm ? 'rotate-90' : '']" class="w-5 h-5 text-muted-foreground transition-transform" />
                            </button>

                            <div v-if="showPasswordForm" class="p-6 pt-0 bg-muted/10 animate-in slide-in-from-top-2 duration-300">
                                <div class="space-y-4 pt-4 border-t border-border/50">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <input v-model="passwordForm.password" type="password" placeholder="New Password" class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" />
                                        <input v-model="passwordForm.password_confirmation" type="password" placeholder="Confirm Password" class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" />
                                    </div>
                                    <button @click="updatePassword" :disabled="changingPassword || !passwordForm.password" class="w-full py-4 rounded-xl bg-indigo-600 text-white font-black uppercase tracking-widest text-xs hover:bg-indigo-500 transition-all active:scale-95 disabled:opacity-50">
                                        {{ changingPassword ? 'Updating...' : 'Confirm New Password' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Phone Number (Read Only) -->
                        <div class="w-full flex items-center justify-between p-6">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-muted flex items-center justify-center text-muted-foreground">
                                    <Smartphone class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-foreground uppercase tracking-tight">Verified Phone</p>
                                    <p class="text-[10px] text-muted-foreground font-medium uppercase tracking-widest">{{ authStore.user?.phone_number }}</p>
                                </div>
                            </div>
                            <ShieldCheck class="w-5 h-5 text-emerald-500" />
                        </div>

                        <!-- Data Saver -->
                        <div class="w-full flex items-center justify-between p-6">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600">
                                    <Zap class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-foreground uppercase tracking-tight">Data Saver</p>
                                    <p class="text-[10px] text-muted-foreground font-medium uppercase tracking-widest">Optimized for low bandwidth</p>
                                </div>
                            </div>
                            <button @click="settingsStore.toggleDataSaver()" :class="[settingsStore.dataSaver ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700']" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                <span :class="[settingsStore.dataSaver ? 'translate-x-6' : 'translate-x-1']" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
                            </button>
                        </div>

                        <!-- Logout -->
                        <button @click="handleLogout" class="w-full flex items-center justify-between p-6 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-all group">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-600">
                                    <LogOut class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-rose-600 uppercase tracking-tight group-hover:underline">Sign Out</p>
                                    <p class="text-[10px] text-rose-400 font-medium uppercase tracking-widest">End your session</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
