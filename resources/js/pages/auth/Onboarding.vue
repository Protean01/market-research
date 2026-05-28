<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowRight, CheckCircle2, Circle, Loader2, Smartphone, Eye, EyeOff, Lock } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { subscribeToPush } from '@/composables/usePush';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const props = defineProps<{
    step?: number;
    phone?: string;
    must_create_password?: boolean;
}>();

const DEFAULT_DIAL_CODE = '+260';
const phone = ref(props.phone || DEFAULT_DIAL_CODE);
const otp = ref('');
const password = ref('');
const password_confirmation = ref('');
const step = ref(props.step || 1); // 1: Phone, 2: OTP, 3: Password Login, 4: Create Password
const loading = ref(false);
const error = ref('');
const showPassword = ref(false);
const showConfirmPassword = ref(false);

// Password strength — length-focused per NIST SP 800-63B
const passwordLengthOk = computed(() => password.value.length >= 12);
const passwordsMatch = computed(() => password.value.length > 0 && password.value === password_confirmation.value);
const passwordReady = computed(() => passwordLengthOk.value && passwordsMatch.value);

const strengthPercent = computed(() => {
    const len = password.value.length;
    if (len === 0) return 0;
    if (len < 8)  return 15;
    if (len < 12) return 35;
    if (len < 16) return 60;
    if (len < 20) return 80;
    return 100;
});

const strengthLabel = computed(() => {
    const len = password.value.length;
    if (len === 0)  return '';
    if (len < 8)    return 'Too short';
    if (len < 12)   return 'Weak';
    if (len < 16)   return 'Good';
    if (len < 20)   return 'Strong';
    return 'Very strong';
});

const strengthColor = computed(() => {
    const len = password.value.length;
    if (len < 8)    return 'bg-red-500';
    if (len < 12)   return 'bg-amber-400';
    if (len < 16)   return 'bg-emerald-400';
    return 'bg-emerald-500';
});

async function sendOTP(ignorePassword: boolean = false) {
    if (!phone.value) {
return;
}

    loading.value = true;
    error.value = '';

    try {
        // If called from a click event without args, ignorePassword will be an Event; coerce to boolean
        if (typeof ignorePassword !== 'boolean') {
            ignorePassword = false;
        }

        const response = await axios.post('/auth/otp/send', { 
            phone_number: phone.value,
            ignore_password: ignorePassword,
            _t: Date.now() // Cache buster
        }, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.data.skip_otp || response.data.status === 'skip_otp') {
            router.visit('/dashboard');

            return;
        }

        if (response.data.status === 'require_password') {
            step.value = 3;
        } else if (response.data.status === 'otp_sent') {
            step.value = 2;
        }
    } catch (err: any) {
        error.value = err.response?.data?.error || 'Unable to send OTP. Please check the number.';
    } finally {
        loading.value = false;
    }
}

async function loginWithPassword() {
    if (!password.value) {
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const response = await axios.post('/auth/login-password', {
            phone_number: phone.value,
            password: password.value,
        }, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (response.data.status === 'authenticated') {
            router.visit('/dashboard');
        }
    } catch (err: any) {
        error.value = err.response?.data?.error || 'Invalid password.';
        loading.value = false;
    }
}

async function verify() {
    if (otp.value.length < 4) {
return;
}

    loading.value = true;
    error.value = '';

    axios.post('/auth/otp/verify', {
        phone_number: phone.value,
        otp: otp.value
    }).then(res => {
        if (res.data?.status === 'require_password_creation' || res.data?.require_password_creation) {
            step.value = 4;
            loading.value = false;

            return;
        }

        router.visit('/dashboard');
    }).catch(err => {
        error.value = err.response?.data?.error || 'Invalid code.';
        loading.value = false;
    });
}

async function createPassword() {
    if (!passwordLengthOk.value) {
        error.value = 'Password must be at least 12 characters.';
        return;
    }
    if (password.value !== password_confirmation.value) {
        error.value = 'Passwords do not match.';
        return;
    }

    loading.value = true;
    error.value = '';

    router.post('/auth/set-password', {
        password: password.value,
        password_confirmation: password_confirmation.value
    }, {
        onSuccess: async () => {
            try {
 await subscribeToPush();
} catch {
    // Ignore push notification errors
}
        },
        onError: (err: any) => {
            error.value = err.password?.[0] || 'Unable to set password.';
            loading.value = false;
        }
    });
}
</script>

<template>
    <Head title="Login" />

    <AuthSimpleLayout title="MR Platform" description="Join thousands earning daily rewards.">
        <div class="space-y-8 max-w-sm mx-auto">
            <!-- Header Icon -->
            <div class="flex justify-center">
                <div class="rounded-3xl bg-indigo-600 p-4 text-white shadow-xl">
                    <Smartphone v-if="step === 1 || step === 2" class="w-8 h-8" />
                    <Lock v-else class="w-8 h-8" />
                </div>
            </div>

            <!-- Content -->
            <div class="text-center space-y-2">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    <template v-if="step === 1">Get Started</template>
                    <template v-else-if="step === 2">Verify it's you</template>
                    <template v-else-if="step === 3">Welcome Back</template>
                    <template v-else-if="step === 4">Secure Your Account</template>
                </h2>
                <p class="text-sm text-gray-500">
                    <template v-if="step === 1">Enter your phone number to begin.</template>
                    <template v-else-if="step === 2">Enter the code sent to {{ phone }}</template>
                    <template v-else-if="step === 3">Enter your password to continue.</template>
                    <template v-else-if="step === 4">Create a password for future logins.</template>
                </p>
            </div>

            <!-- Error -->
            <div v-if="error" class="bg-red-50 text-red-800 p-4 rounded-xl text-sm border border-red-100">
                {{ error }}
            </div>

            <div class="space-y-4">
                <!-- Step 1: Phone -->
                <div v-if="step === 1" class="space-y-6">
                    <input
                        v-model="phone"
                        type="tel"
                        class="w-full rounded-2xl border border-input bg-background shadow-sm py-4 pl-4 text-lg font-semibold text-foreground placeholder:text-muted-foreground focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white dark:placeholder:text-zinc-400"
                        placeholder="+260 7xx xxx xxx"
                        @keyup.enter="sendOTP(false)"
                    />
                    <button :disabled="loading" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-md hover:bg-indigo-500 transition-colors" @click="sendOTP(false)">
                        <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                        <span v-else>Continue</span>
                        <ArrowRight v-if="!loading" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Step 2: OTP -->
                <div v-if="step === 2" class="space-y-6">
                    <input
                        v-model="otp"
                        maxlength="6"
                        class="w-full rounded-2xl border border-input bg-background shadow-sm py-4 text-center text-4xl font-black tracking-widest text-foreground placeholder:text-muted-foreground focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white dark:placeholder:text-zinc-400"
                        @keyup.enter="verify"
                    />
                    <div class="space-y-3">
                        <button :disabled="loading" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-bold shadow-md hover:bg-emerald-500 transition-colors" @click="verify">
                            <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                            <span v-else>Verify & Continue</span>
                        </button>
                        <button class="w-full text-sm text-muted-foreground font-bold" @click="step = 1">Change Number</button>
                    </div>
                </div>

                <!-- Step 3: Password Login -->
                <div v-if="step === 3" class="space-y-6">
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" v-model="password" autocomplete="current-password" class="w-full rounded-2xl border border-input bg-background shadow-sm py-4 px-4 text-foreground placeholder:text-muted-foreground focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white dark:placeholder:text-zinc-400" placeholder="Your password" @keyup.enter="loginWithPassword" />
                        <button @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground dark:text-zinc-400 hover:text-foreground dark:hover:text-zinc-200">
                            <Eye v-if="!showPassword" class="w-5 h-5" />
                            <EyeOff v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="space-y-3">
                        <button :disabled="loading" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-md hover:bg-indigo-500 transition-colors" @click="loginWithPassword">
                            <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                            <span v-else>Login</span>
                        </button>
                        <button :disabled="loading" class="w-full text-sm text-indigo-600 font-bold hover:text-indigo-500" @click="sendOTP(true)">
                            Login with OTP instead
                        </button>
                    </div>
                </div>

                <!-- Step 4: Create Password -->
                <div v-if="step === 4" class="space-y-5">
                    <!-- Requirements checklist -->
                    <div class="rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/30 p-4 space-y-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mb-3">Requirements</p>
                        <div class="flex items-center gap-2 text-sm" :class="passwordLengthOk ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
                            <CheckCircle2 v-if="passwordLengthOk" class="w-4 h-4 shrink-0" />
                            <Circle v-else class="w-4 h-4 shrink-0" />
                            <span>At least 12 characters</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm" :class="passwordsMatch ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
                            <CheckCircle2 v-if="passwordsMatch" class="w-4 h-4 shrink-0" />
                            <Circle v-else class="w-4 h-4 shrink-0" />
                            <span>Passwords match</span>
                        </div>
                    </div>

                    <!-- Strength bar -->
                    <div v-if="password.length > 0" class="space-y-1">
                        <div class="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="strengthColor"
                                :style="{ width: strengthPercent + '%' }"
                            />
                        </div>
                        <p class="text-xs text-right font-medium text-muted-foreground">{{ strengthLabel }}</p>
                    </div>

                    <div class="space-y-3">
                        <!-- Password field with show/hide -->
                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="password"
                                class="w-full rounded-2xl border border-input bg-background shadow-sm py-4 px-4 pr-12 text-foreground placeholder:text-muted-foreground focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white dark:placeholder:text-zinc-400"
                                placeholder="New password"
                                autocomplete="new-password"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground dark:hover:text-zinc-200 transition-colors">
                                <Eye v-if="!showPassword" class="w-5 h-5" />
                                <EyeOff v-else class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Confirm field with show/hide -->
                        <div class="relative">
                            <input
                                :type="showConfirmPassword ? 'text' : 'password'"
                                v-model="password_confirmation"
                                class="w-full rounded-2xl border border-input bg-background shadow-sm py-4 px-4 pr-12 text-foreground placeholder:text-muted-foreground focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-zinc-800 dark:border-zinc-700 dark:text-white dark:placeholder:text-zinc-400"
                                placeholder="Confirm password"
                                autocomplete="new-password"
                            />
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground dark:hover:text-zinc-200 transition-colors">
                                <Eye v-if="!showConfirmPassword" class="w-5 h-5" />
                                <EyeOff v-else class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <button
                        :disabled="loading || !passwordReady"
                        class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-md hover:bg-indigo-500 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        @click="createPassword"
                    >
                        <Loader2 v-if="loading" class="w-5 h-5 animate-spin mx-auto" />
                        <span v-else>Create Password</span>
                    </button>

                    <p class="text-xs text-center text-muted-foreground">
                        Use a long, unique phrase. Longer is stronger.
                    </p>
                </div>
            </div>
        </div>
    </AuthSimpleLayout>
</template>
