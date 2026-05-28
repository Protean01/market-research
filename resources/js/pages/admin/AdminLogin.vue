<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ShieldCheck, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('admin.login.store'), {
        onFinish: () => {
            if (form.errors.email || form.errors.password) {
                form.reset('password');
            }
        },
    });
}
</script>

<template>
    <Head title="Admin Login" />

    <div class="min-h-screen bg-gray-950 flex items-center justify-center px-4">
        <div class="w-full max-w-sm">

            <!-- Logo / branding -->
            <div class="flex flex-col items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center mb-4 shadow-lg shadow-indigo-600/30">
                    <ShieldCheck class="w-6 h-6 text-white" />
                </div>
                <h1 class="text-xl font-semibold text-white tracking-tight">Admin Portal</h1>
                <p class="text-sm text-gray-500 mt-1">Market Research Platform</p>
            </div>

            <!-- Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl">

                <!-- Global error -->
                <div
                    v-if="form.errors.email"
                    class="mb-5 flex items-start gap-2.5 rounded-lg bg-red-950/60 border border-red-800/50 px-3.5 py-3 text-sm text-red-400"
                >
                    <span>{{ form.errors.email }}</span>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-400 mb-1.5">
                            Email address
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            autofocus
                            placeholder="admin@example.com"
                            class="w-full rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-600 text-sm px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            :class="{ 'border-red-600 focus:ring-red-500': form.errors.email }"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-400 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-600 text-sm px-3.5 py-2.5 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                :class="{ 'border-red-600 focus:ring-red-500': form.errors.password }"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-300 transition"
                                @click="showPassword = !showPassword"
                            >
                                <Eye v-if="!showPassword" class="w-4 h-4" />
                                <EyeOff v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center gap-2 pt-0.5">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded border-gray-700 bg-gray-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-gray-900"
                        />
                        <label for="remember" class="text-xs text-gray-400 select-none cursor-pointer">
                            Keep me signed in
                        </label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-1 w-full flex items-center justify-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium px-4 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                    >
                        <span v-if="form.processing">Signing in…</span>
                        <span v-else>Sign in</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-gray-700 mt-6">
                Not an admin?
                <a href="/" class="text-gray-500 hover:text-gray-300 transition">Member login</a>
            </p>
        </div>
    </div>
</template>
