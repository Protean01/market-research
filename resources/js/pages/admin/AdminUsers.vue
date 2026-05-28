<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, Users, UserX, UserCheck, Mail, Shield, Activity, MapPin, Briefcase, Coins, Trash2, RotateCcw } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    users: {
        data: any[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        deleted?: boolean;
    };
    showDeleted: boolean;
}>();

const page = usePage();
const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.users', []), { search: value, deleted: props.showDeleted || undefined }, { preserveState: true, replace: true });
}, 500));

const toggleDeleted = () => {
    router.get(route('admin.users', []), { deleted: props.showDeleted ? undefined : '1' });
};

const toggleStatus = (userId: number) => {
    if (confirm('Are you sure you want to change this user\'s status?')) {
        router.post(route('admin.users.toggle', userId));
    }
};

const updateRole = (userId: number, role: string) => {
    router.post(route('admin.users.role', userId), { role });
};

const deleteUser = (userId: number, name: string) => {
    if (confirm(`Soft-delete ${name}? Their data is preserved and they can be restored.`)) {
        router.delete(route('admin.users.destroy', userId));
    }
};

const restoreUser = (userId: number, name: string) => {
    if (confirm(`Restore ${name}?`)) {
        router.post(route('admin.users.restore', userId));
    }
};
</script>

<template>
    <Head title="Admin · User Management" />
    <AppLayout>
        <div class="space-y-8 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-foreground flex items-center gap-3">
                        <Users class="w-8 h-8 text-indigo-600" />
                        User Management
                    </h1>
                    <p class="text-base font-medium text-muted-foreground mt-1">
                        Monitor and manage responder accounts.
                    </p>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button
                        @click="toggleDeleted"
                        :class="showDeleted ? 'bg-red-600 text-white border-red-600' : 'bg-background text-foreground border-border hover:bg-muted'"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-black uppercase tracking-widest transition-all shrink-0"
                    >
                        <Trash2 class="w-4 h-4" />
                        {{ showDeleted ? 'Deleted Users' : 'Show Deleted' }}
                    </button>
                    <div class="relative flex-1 sm:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search users..."
                            class="w-full pl-10 pr-4 py-2 bg-background border border-border rounded-xl text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                        >
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-3xl border border-border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-muted/50 border-b border-border">
                                <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">User Details</th>
                                <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Profile Info</th>
                                <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Reputation</th>
                                <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Wallet</th>
                                <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest">Role</th>
                                <th class="px-6 py-4 text-[10px] font-black text-muted-foreground uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in users.data" :key="user.id" class="group hover:bg-muted/30 transition-colors" :class="user.deleted_at ? 'opacity-60' : ''">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 font-bold text-lg">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-foreground group-hover:text-indigo-600 transition-colors flex items-center gap-2" :class="user.deleted_at ? 'line-through' : ''">
                                                {{ user.name }}
                                                <Shield v-if="user.role === 'admin'" class="w-3.5 h-3.5 text-amber-500" title="Administrator" />
                                                <Activity v-else-if="user.role === 'researcher'" class="w-3.5 h-3.5 text-indigo-500" title="Researcher" />
                                                <span v-if="user.deleted_at" class="text-[9px] font-black uppercase tracking-widest bg-red-100 text-red-600 px-1.5 py-0.5 rounded border border-red-200 no-underline" style="text-decoration: none;">Deleted</span>
                                            </div>
                                            <div class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
                                                <Mail class="w-3 h-3" />
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div v-if="user.profile" class="space-y-1">
                                        <div class="text-xs font-bold text-foreground flex items-center gap-1.5">
                                            <MapPin class="w-3 h-3 text-muted-foreground" />
                                            {{ user.profile.location || 'N/A' }}
                                        </div>
                                        <div class="text-[10px] font-medium text-muted-foreground flex items-center gap-1.5 uppercase tracking-wider">
                                            <Briefcase class="w-3 h-3" />
                                            {{ user.profile.employment?.replace('_', ' ') || 'No Job Info' }}
                                        </div>
                                    </div>
                                    <div v-else class="text-[10px] font-bold text-muted-foreground italic uppercase">No Profile</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div v-if="user.profile" class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <div class="w-12 h-1.5 bg-muted rounded-full overflow-hidden">
                                                <div class="h-full bg-indigo-500" :style="{ width: (user.profile.trust_score ?? 100) + '%' }"></div>
                                            </div>
                                            <span class="text-[10px] font-black text-foreground">{{ user.profile.trust_score ?? 100 }}%</span>
                                        </div>
                                        <div class="text-[9px] font-black uppercase text-amber-600 flex items-center gap-1">
                                            <Zap class="w-3 h-3 fill-current" />
                                            {{ user.profile.current_streak ?? 0 }} Day Streak
                                        </div>
                                    </div>
                                    <div v-else class="text-[10px] font-bold text-muted-foreground italic uppercase">N/A</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div v-if="user.wallet" class="flex items-center gap-2 text-amber-600 font-black">
                                        <Coins class="w-4 h-4" />
                                        {{ user.wallet.points }}
                                    </div>
                                    <div v-else class="text-xs font-bold text-muted-foreground">0 pts</div>
                                </td>
                                <td class="px-6 py-5">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tight"
                                        :class="user.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                    >
                                        <div class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-red-500'"></div>
                                        {{ user.is_active ? 'Active' : 'Suspended' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <select 
                                        :value="user.role" 
                                        @change="updateRole(user.id, ($event.target as HTMLSelectElement).value)"
                                        class="bg-muted/50 border-none rounded-lg text-[10px] font-black uppercase tracking-widest px-2 py-1 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <option value="member">Member</option>
                                        <option value="researcher">Researcher</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </td>
                                <td class="px-6 py-5 text-right flex items-center justify-end gap-2">
                                    <template v-if="user.deleted_at">
                                        <button
                                            @click="restoreUser(user.id, user.name)"
                                            title="Restore user"
                                            class="p-2 rounded-lg text-emerald-600 hover:text-white hover:bg-emerald-600 transition-all"
                                        >
                                            <RotateCcw class="w-5 h-5" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            @click="toggleStatus(user.id)"
                                            :title="user.is_active ? 'Suspend User' : 'Activate User'"
                                            class="p-2 rounded-lg transition-all"
                                            :class="user.is_active ? 'text-muted-foreground hover:text-red-600 hover:bg-red-50' : 'text-red-600 bg-red-50 hover:bg-emerald-50 hover:text-emerald-600'"
                                        >
                                            <UserX v-if="user.is_active" class="w-5 h-5" />
                                            <UserCheck v-else class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="deleteUser(user.id, user.name)"
                                            title="Delete user"
                                            class="p-2 rounded-lg text-red-600 hover:text-white hover:bg-red-600 transition-all"
                                            :disabled="user.id === page.props.auth.user.id"
                                        >
                                            <Trash2 class="w-5 h-5" />
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="rounded-full bg-muted p-4 mb-3">
                                            <Activity class="h-6 w-6 text-muted-foreground" />
                                        </div>
                                        <h3 class="text-sm font-black text-foreground uppercase tracking-widest">No Users Found</h3>
                                        <p class="text-xs text-muted-foreground mt-1 font-bold">No users match your current search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="users.meta && users.meta.last_page > 1" class="p-4 border-t border-border flex items-center justify-between bg-muted/20">
                    <div class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                        Page {{ users.meta.current_page }} of {{ users.meta.last_page }}
                    </div>
                    <div class="flex gap-2">
                        <component
                            :is="link.url ? 'Link' : 'span'"
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url"
                            class="px-3 py-1 text-xs font-bold rounded-lg border border-border"
                            :class="{ 
                                'bg-indigo-600 text-white border-indigo-600': link.active,
                                'bg-background text-foreground hover:bg-muted': !link.active && link.url,
                                'opacity-50 cursor-not-allowed': !link.url
                            }"
                        >
                            <span v-html="link.label"></span>
                        </component>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
