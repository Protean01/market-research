<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { BookOpen, Plus, Trash2, Tag, User, Users, Edit2, Mail, Save, X, ChevronRight, Search, ListTree, ClipboardList } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';

const props = withDefaults(defineProps<{
    initialTemplates: any[];
    clients: any[];
    allSurveys?: any[];
}>(), {
    allSurveys: () => []
});



// UI State
const activeTab = ref<string | null>(null); // Client ID
const mainView = ref('bank'); // 'bank' or 'surveys'
const showTemplateForm = ref(false);
const showClientForm = ref(false);
const isEditingClient = ref(false);
const editingClientId = ref<number | null>(null);
const searchQuery = ref('');

// Initialize with first client if available
onMounted(() => {
    if (props.clients.length > 0) {
        activeTab.value = props.clients[0].id.toString();
    }
});

// Forms
const templateForm = useForm({
    name: '',
    category: 'general',
    client_id: '' as string | number,
    questions: [
        { text: '', type: 'mcq', options: ['Option 1', 'Option 2'] }
    ]
});

const clientForm = useForm({
    name: '',
    email: '',
    description: '',
    is_active: true
});

// Computed Data
const filteredTemplates = computed(() => {
    let list = props.initialTemplates.filter(t => t.client_id == activeTab.value);

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        list = list.filter(t => t.name.toLowerCase().includes(query) || t.category.toLowerCase().includes(query));
    }

    return list;
});

const clientSurveys = computed(() => {
    if (!activeTab.value) {
return [];
}

    // Filter surveys where client_id matches active tab
    return props.allSurveys.filter(s => s.client_id == activeTab.value);
});

const activeClient = computed(() => {
    return props.clients.find(c => c.id == activeTab.value);
});

// Actions: Templates
const addQuestionToTemplate = () => {
    templateForm.questions.push({ text: '', type: 'mcq', options: ['Option 1', 'Option 2'] });
};

const removeQuestionFromTemplate = (idx: number) => {
    templateForm.questions.splice(idx, 1);
};

const addTemplateOption = (qIdx: number) => {
    templateForm.questions[qIdx].options.push(`Option ${templateForm.questions[qIdx].options.length + 1}`);
};

const removeTemplateOption = (qIdx: number, oIdx: number) => {
    templateForm.questions[qIdx].options.splice(oIdx, 1);
};

const submitTemplate = () => {
    templateForm.client_id = activeTab.value!;

    templateForm.post(route('admin.question-bank.store', undefined as any), {
        onSuccess: () => {
            showTemplateForm.value = false;
            templateForm.reset();
        }
    });
};

const deleteTemplate = (id: number) => {
    if (confirm('Delete this template set from the library?')) {
        templateForm.delete(route('admin.question-bank.destroy', { template: id } as any));
    }
};

// Actions: Clients
const openCreateClient = () => {
    isEditingClient.value = false;
    clientForm.reset();
    showClientForm.value = true;
};

const openEditClient = (client: any) => {
    isEditingClient.value = true;
    editingClientId.value = client.id;
    clientForm.name = client.name;
    clientForm.email = client.email;
    clientForm.description = client.description;
    clientForm.is_active = !!client.is_active;
    showClientForm.value = true;
};

const submitClient = () => {
    if (isEditingClient.value && editingClientId.value) {
        clientForm.put(route('admin.clients.update', { client: editingClientId.value } as any), {
            onSuccess: () => showClientForm.value = false
        });
    } else {
        clientForm.post(route('admin.clients.store', undefined as any), {
            onSuccess: () => {
                showClientForm.value = false;
            }
        });
    }
};

const deleteClient = (id: number) => {
    if (confirm('Delete this client and all their associations?')) {
        clientForm.delete(route('admin.clients.destroy', { client: id } as any), {
            onSuccess: () => {
                if (props.clients.length > 0) {
activeTab.value = props.clients[0].id.toString();
} else {
activeTab.value = null;
}
            }
        });
    }
};
</script>

<template>
    <Head title="Admin · Client Library" />
    <AppLayout>
        <div class="h-full flex flex-col xl:flex-row overflow-hidden bg-background">
            <!-- Sidebar: Clients List -->
            <aside class="w-full xl:w-80 border-r border-border bg-card/50 flex flex-col">
                <div class="p-6 border-b border-border bg-muted/30">
                    <div class="flex items-center justify-between">
                        <h1 class="text-xl font-black text-foreground flex items-center gap-2">
                            <Users class="w-5 h-5 text-indigo-600" />
                            Clients
                        </h1>
                        <button @click="openCreateClient" class="p-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/20">
                            <Plus class="w-4 h-4" />
                        </button>
                    </div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mt-2">Research Partners</p>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    <button 
                        v-for="client in clients" 
                        :key="client.id"
                        @click="activeTab = client.id.toString()"
                        :class="['w-full flex items-center justify-between p-4 rounded-2xl text-sm font-black transition-all group border-2', activeTab == client.id ? 'bg-indigo-600 text-white border-indigo-600 shadow-xl shadow-indigo-600/20' : 'text-muted-foreground hover:bg-muted border-transparent']"
                    >
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div :class="['w-2 h-2 rounded-full shrink-0', client.is_active ? (activeTab == client.id ? 'bg-white' : 'bg-emerald-400') : 'bg-red-400']"></div>
                            <span class="truncate">{{ client.name }}</span>
                        </div>
                        <ChevronRight v-if="activeTab == client.id" class="w-4 h-4 text-white/50" />
                    </button>

                    <div v-if="clients.length === 0" class="py-12 text-center">
                        <User class="w-8 h-8 text-muted-foreground mx-auto mb-2 opacity-20" />
                        <p class="text-xs font-bold text-muted-foreground uppercase">No Clients</p>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main v-if="activeClient" class="flex-1 flex flex-col overflow-hidden">
                <!-- Client Header -->
                <header class="px-8 py-6 border-b border-border bg-card/30">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 font-black text-3xl shadow-sm">
                                {{ activeClient.name.charAt(0) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-3xl font-black text-foreground tracking-tight">{{ activeClient.name }}</h2>
                                    <button @click="openEditClient(activeClient)" class="p-2 hover:bg-muted rounded-xl transition-colors">
                                        <Edit2 class="w-4 h-4 text-muted-foreground" />
                                    </button>
                                </div>
                                <div class="flex items-center gap-4 mt-1">
                                    <span class="text-sm font-medium text-muted-foreground flex items-center gap-1.5">
                                        <Mail class="w-3.5 h-3.5" /> {{ activeClient.email }}
                                    </span>
                                    <span :class="['text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full', activeClient.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                        {{ activeClient.is_active ? 'Active Partner' : 'Suspended' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-navigation -->
                        <div class="flex bg-muted/50 p-1.5 rounded-2xl border border-border">
                            <button 
                                @click="mainView = 'bank'"
                                :class="['px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all', mainView === 'bank' ? 'bg-background text-indigo-600 shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                            >
                                <div class="flex items-center gap-2">
                                    <BookOpen class="w-3.5 h-3.5" />
                                    Question Bank
                                </div>
                            </button>
                            <button 
                                @click="mainView = 'surveys'"
                                :class="['px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all', mainView === 'surveys' ? 'bg-background text-indigo-600 shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                            >
                                <div class="flex items-center gap-2">
                                    <ClipboardList class="w-3.5 h-3.5" />
                                    Active Surveys
                                </div>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Dynamic Viewport -->
                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    
                    <!-- View: Question Bank -->
                    <div v-if="mainView === 'bank'" class="space-y-8 animate-in fade-in duration-300">
                        <div class="flex items-center justify-between">
                            <div class="relative w-64">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <input v-model="searchQuery" type="text" placeholder="Search templates..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border-border border bg-card text-sm focus:ring-2 focus:ring-indigo-500/20" />
                            </div>
                            <button 
                                @click="showTemplateForm = true"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all active:scale-95"
                            >
                                <Plus class="w-4 h-4" />
                                Add New Template
                            </button>
                        </div>

                        <!-- Template Creator (Inline) -->
                        <div v-if="showTemplateForm" class="rounded-3xl border-2 border-indigo-500/30 bg-indigo-50/5 p-8 shadow-2xl animate-in zoom-in-95 duration-200">
                            <form @submit.prevent="submitTemplate" class="space-y-8">
                                <div class="flex items-center justify-between pb-4 border-b border-border">
                                    <h3 class="font-black text-foreground uppercase tracking-tight">Create Library Template</h3>
                                    <button type="button" @click="showTemplateForm = false" class="text-muted-foreground hover:text-foreground"><X class="w-5 h-5" /></button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                    <div class="md:col-span-8">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-2">Set Name</label>
                                        <input v-model="templateForm.name" type="text" placeholder="e.g. Basic Brand Awareness" class="w-full rounded-xl border-border border p-3 bg-background font-bold focus:ring-2 focus:ring-indigo-500/20" required>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-2">Category</label>
                                        <select v-model="templateForm.category" class="w-full rounded-xl border-border border p-3 bg-background font-bold text-sm">
                                            <option value="general">General</option>
                                            <option value="demographic">Demographic</option>
                                            <option value="behavior">Behavioral</option>
                                            <option value="satisfaction">Satisfaction</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-black uppercase tracking-widest text-indigo-600">Questions in this Template</h4>
                                        <button type="button" @click="addQuestionToTemplate" class="text-[10px] font-black uppercase text-indigo-600 hover:underline flex items-center gap-1">
                                            <Plus class="w-3 h-3" /> Add Question
                                        </button>
                                    </div>

                                    <div v-for="(q, qIdx) in templateForm.questions" :key="qIdx" class="p-6 border border-border rounded-2xl bg-background/50 relative group/q">
                                        <button v-if="templateForm.questions.length > 1" type="button" @click="removeQuestionFromTemplate(qIdx)" class="absolute top-4 right-4 text-red-400">
                                            <X class="w-4 h-4" />
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                            <div class="md:col-span-8">
                                                <input v-model="q.text" placeholder="Question text" class="w-full rounded-lg border-border border p-2 text-sm" required />
                                            </div>
                                            <div class="md:col-span-4">
                                                <select v-model="q.type" class="w-full rounded-lg border-border border p-2 text-sm bg-background">
                                                    <option value="mcq">MCQ</option>
                                                    <option value="checkbox">Checkbox</option>
                                                    <option value="scale">Scale</option>
                                                    <option value="text">Text</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="q.type === 'mcq' || q.type === 'checkbox'" class="mt-4 pl-4 border-l-2 border-indigo-100 space-y-2">
                                            <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-2">
                                                <input v-model="q.options[oIdx]" class="flex-1 rounded-md border-border border p-1.5 text-[10px]" required />
                                                <button type="button" @click="removeTemplateOption(qIdx, oIdx)" class="text-red-400 p-1"><X class="w-3 h-3" /></button>
                                            </div>
                                            <button type="button" @click="addTemplateOption(qIdx)" class="text-[9px] font-bold text-indigo-600">+ Add Option</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4 gap-3">
                                    <button type="button" @click="showTemplateForm = false" class="px-6 py-3 text-sm font-black text-muted-foreground uppercase">Cancel</button>
                                    <button type="submit" :disabled="templateForm.processing" class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white rounded-xl text-sm font-black uppercase shadow-lg hover:bg-indigo-700 transition-all">
                                        <Save class="w-4 h-4" />
                                        Save to {{ activeClient.name }} Bank
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Template Cards -->
                        <div v-if="filteredTemplates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="template in filteredTemplates" :key="template.id" class="group p-6 rounded-3xl border border-border bg-card hover:border-indigo-500/30 hover:shadow-xl transition-all relative overflow-hidden flex flex-col">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tight bg-muted text-muted-foreground">
                                        <Tag class="w-3 h-3" />
                                        {{ template.category }}
                                    </span>
                                    <button @click="deleteTemplate(template.id)" class="opacity-0 group-hover:opacity-100 p-1 text-muted-foreground hover:text-red-500 transition-all">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <ListTree class="w-4 h-4 text-indigo-500" />
                                    <h3 class="font-black text-foreground leading-tight">{{ template.name }}</h3>
                                </div>
                                <div class="space-y-2 mb-6">
                                    <p v-for="(q, idx) in template.questions?.slice(0, 2)" :key="idx" class="text-xs text-muted-foreground truncate">
                                        {{ idx + 1 }}. {{ q.text }}
                                    </p>
                                    <p v-if="template.questions?.length > 2" class="text-[10px] text-indigo-500 font-bold uppercase">+ {{ template.questions.length - 2 }} more questions</p>
                                </div>
                                <div class="mt-auto pt-4 border-t border-border flex items-center justify-between">
                                    <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">{{ template.questions?.length || 0 }} Questions</span>
                                    <ChevronRight class="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-20 text-center opacity-50">
                            <BookOpen class="w-16 h-16 mb-4 text-muted-foreground mx-auto" />
                            <h3 class="text-xl font-black text-foreground uppercase">No Templates in this Bank</h3>
                            <p class="text-sm font-medium text-muted-foreground mt-1">Start building a reusable question set for {{ activeClient.name }}.</p>
                        </div>
                    </div>

                    <!-- View: All Surveys -->
                    <div v-if="mainView === 'surveys'" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-for="survey in clientSurveys" :key="survey.id" class="p-6 rounded-3xl border border-border bg-card hover:shadow-lg transition-all group">
                                <div class="flex justify-between items-start mb-4">
                                    <div :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', survey.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground']">
                                        {{ survey.is_active ? 'Live Now' : 'Closed' }}
                                    </div>
                                    <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">{{ new Date(survey.created_at).toLocaleDateString() }}</span>
                                </div>
                                <h3 class="text-lg font-black text-foreground group-hover:text-indigo-600 transition-colors">{{ survey.title }}</h3>
                                <p class="text-sm text-muted-foreground mt-1 line-clamp-2">{{ survey.description }}</p>
                                
                                <div class="mt-6 flex items-center gap-6">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Responses</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg font-black text-foreground">{{ survey.responses_count }}</span>
                                            <span class="text-xs font-bold text-muted-foreground">/ {{ survey.response_cap }}</span>
                                        </div>
                                    </div>
                                    <div class="h-8 w-px bg-border"></div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Rewards</span>
                                        <span class="text-lg font-black text-amber-600">{{ survey.reward_points }} pts</span>
                                    </div>
                                </div>

                                <div class="mt-6 pt-6 border-t border-border flex justify-end gap-2">
                                    <Link :href="route('admin.surveys.report', { survey: survey.id } as any)" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all">Analytics</Link>
                                    <a :href="'/surveys/' + survey.id" target="_blank" class="px-4 py-2 bg-muted text-muted-foreground rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-border transition-all">Preview</a>
                                </div>
                            </div>
                        </div>
                        <div v-if="clientSurveys.length === 0" class="py-20 text-center opacity-50">
                            <ClipboardList class="w-16 h-16 mb-4 text-muted-foreground mx-auto" />
                            <h3 class="text-xl font-black text-foreground uppercase tracking-tight">No Campaigns Found</h3>
                            <p class="text-sm font-medium text-muted-foreground mt-1">This client has not launched any research campaigns yet.</p>
                        </div>
                    </div>

                </div>
            </main>

            <!-- Empty State (No Client Selected) -->
            <main v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center opacity-50">
                <Users class="w-20 h-20 mb-6 text-muted-foreground" />
                <h3 class="text-2xl font-black text-foreground uppercase tracking-tight">Select a Research Client</h3>
                <p class="text-base font-medium text-muted-foreground max-w-sm mt-2">Choose a client from the sidebar to manage their specialized question sets and view their active surveys.</p>
            </main>
        </div>

        <!-- Client Form Modal -->
        <div v-if="showClientForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-card w-full max-w-xl rounded-3xl border border-border shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <h3 class="text-xl font-black text-foreground uppercase tracking-tight">{{ isEditingClient ? 'Edit Client' : 'New Research Client' }}</h3>
                    <button @click="showClientForm = false" class="p-2 hover:bg-muted rounded-full">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitClient" class="p-8 space-y-6">
                    <div class="grid gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-muted-foreground ml-1">Company Name</label>
                            <input v-model="clientForm.name" type="text" class="w-full rounded-xl border-border border p-3 bg-background font-bold focus:ring-2 focus:ring-indigo-500/20" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-muted-foreground ml-1">Partner Email</label>
                            <input v-model="clientForm.email" type="email" class="w-full rounded-xl border-border border p-3 bg-background font-medium focus:ring-2 focus:ring-indigo-500/20" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-muted-foreground ml-1">Internal Notes</label>
                            <textarea v-model="clientForm.description" rows="2" class="w-full rounded-xl border-border border p-3 bg-background text-sm font-medium focus:ring-2 focus:ring-indigo-500/20"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-4">
                        <button v-if="isEditingClient" type="button" @click="deleteClient(editingClientId!)" class="text-red-500 text-xs font-black uppercase tracking-widest hover:underline">Delete Client</button>
                        <div v-else></div>
                        <div class="flex gap-3">
                            <button type="button" @click="showClientForm = false" class="px-6 py-3 text-sm font-black text-muted-foreground uppercase">Cancel</button>
                            <button type="submit" :disabled="clientForm.processing" class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white rounded-xl text-sm font-black uppercase shadow-lg hover:bg-indigo-700 transition-all">
                                <Save class="w-4 h-4" />
                                Save Client
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 10px;
}
</style>
