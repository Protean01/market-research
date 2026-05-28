<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Settings, Save, AlertCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    settings: {
        reward_daily_cap: number;
        reward_weekly_cap: number;
    };
}>();



const form = useForm({
    reward_daily_cap: props.settings.reward_daily_cap ?? 0,
    reward_weekly_cap: props.settings.reward_weekly_cap ?? 0,
});

const submit = () => {
    form.post(route('admin.settings.update', []));
};
</script>

<template>
    <Head title="Admin · Settings" />
    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-8">
            <div class="flex items-center gap-3">
                <div class="p-3 rounded-2xl bg-indigo-600 text-white shadow-md">
                    <Settings class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-foreground">Platform Settings</h1>
                    <p class="text-sm text-muted-foreground">Reward caps and controls visible to admins only.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="rounded-3xl border border-border bg-card shadow-xl p-8 space-y-6">
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-muted-foreground mb-2">Daily Earning Cap (points)</label>
                    <input v-model.number="form.reward_daily_cap" type="number" min="0" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <p v-if="form.errors.reward_daily_cap" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1">
                        <AlertCircle class="w-3 h-3" /> {{ form.errors.reward_daily_cap }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-muted-foreground mb-2">Weekly Earning Cap (points)</label>
                    <input v-model.number="form.reward_weekly_cap" type="number" min="0" class="w-full rounded-xl border border-border p-3 bg-background text-foreground font-semibold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <p v-if="form.errors.reward_weekly_cap" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1">
                        <AlertCircle class="w-3 h-3" /> {{ form.errors.reward_weekly_cap }}
                    </p>
                </div>

                <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 active:scale-95 disabled:opacity-50">
                    <Save class="w-4 h-4" />
                    Save Settings
                </button>
            </form>
        </div>
    </AppLayout>
</template>
