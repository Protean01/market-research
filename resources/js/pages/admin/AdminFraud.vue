<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShieldAlert, AlertTriangle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    events: {
        data: any[];
        next_page_url: string | null;
        prev_page_url: string | null;
    };
}>();
</script>

<template>
    <Head title="Admin · Fraud Signals" />
    <AppLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6">
            <div class="flex items-center gap-3">
                <div class="p-3 rounded-2xl bg-red-600 text-white shadow-md">
                    <ShieldAlert class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-foreground">Fraud & Velocity Signals</h1>
                    <p class="text-sm text-muted-foreground">Blocks, multi-account hints, and device velocity events.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-border bg-card shadow-xl">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/40 text-xs uppercase tracking-widest text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left">When</th>
                            <th class="px-4 py-3 text-left">Level</th>
                            <th class="px-4 py-3 text-left">Action</th>
                            <th class="px-4 py-3 text-left">Device</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">IP</th>
                            <th class="px-4 py-3 text-left">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border text-sm">
                        <tr v-for="event in props.events.data" :key="event.id">
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ new Date(event.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3 font-bold">
                                <span :class="event.level === 'warning' ? 'text-amber-600' : 'text-red-600'">{{ event.level }}</span>
                            </td>
                            <td class="px-4 py-3">{{ event.action }}</td>
                            <td class="px-4 py-3 text-xs font-mono">{{ event.device_id?.slice(0,12) }}…</td>
                            <td class="px-4 py-3">{{ event.user?.id ?? '—' }}</td>
                            <td class="px-4 py-3">{{ event.ip ?? '—' }}</td>
                            <td class="px-4 py-3">{{ event.message }}</td>
                        </tr>
                        <tr v-if="!props.events.data.length">
                            <td colspan="7" class="px-4 py-6 text-center text-muted-foreground flex items-center justify-center gap-2">
                                <AlertTriangle class="w-4 h-4" />
                                No events captured yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between">
                <Link v-if="props.events.prev_page_url" :href="props.events.prev_page_url" class="text-sm font-bold text-indigo-600 hover:underline">Previous</Link>
                <span class="flex-1"></span>
                <Link v-if="props.events.next_page_url" :href="props.events.next_page_url" class="text-sm font-bold text-indigo-600 hover:underline">Next</Link>
            </div>
        </div>
    </AppLayout>
</template>
