import { useLocalStorage } from '@vueuse/core';
import axios from 'axios';
import { defineStore } from 'pinia';
import type { Survey } from '@/types/survey';

interface SurveyState {
    available: Survey[];
    active: Survey | null;
    answers: Record<string, any>;
    loading: boolean;
    error: string | null;
    isOnline: boolean;
    _syncTimeout: any;
}

interface OfflineSubmission {
    surveyId: number;
    answers: Record<string, any>;
    enrichment_answers: any[];
    timestamp: number;
}



export const useSurveyStore = defineStore('survey', {
    state: (): SurveyState => ({
        available: [],
        active: null,
        answers: {},
        loading: false,
        error: null,
        isOnline: navigator.onLine,
        _syncTimeout: null,
    }),
    getters: {
        offlineQueue: () => useLocalStorage<OfflineSubmission[]>('survey-offline-queue', []).value,
    },
    actions: {
        initialize() {
            if (typeof window === 'undefined') {
return;
}

            const updateOnlineStatus = () => {
                const wasOffline = !this.isOnline;
                this.isOnline = navigator.onLine;

                if (this.isOnline && wasOffline) {
                    this.debounceSync();
                }
            };

            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
            
            // Initial check
            this.isOnline = navigator.onLine;
        },
        debounceSync() {
            // Don't sync if we are on the onboarding page
            if (window.location.pathname.includes('onboarding') || window.location.pathname === '/') {
                return;
            }

            if (this._syncTimeout) {
clearTimeout(this._syncTimeout);
}
            
            this._syncTimeout = setTimeout(() => {
                this.syncOfflineSubmissions();
            }, 3000);
        },
        setActive(survey: Survey) {
            this.active = survey;
            this.answers = {};
        },
        setAnswer(questionId: string, answer: any) {
            this.answers[questionId] = answer;
        },
        async fetchSurveys() {
            this.loading = true;

            try {
                const res = await axios.get('/surveys/json');
                this.available = res.data;
            } catch (err) {
                this.error = 'Failed to fetch surveys';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
        async submitSurvey(surveyId: number, enrichment: any[] = [], timeTaken?: number) {
            this.loading = true;

            const submission = {
                surveyId,
                answers: { ...this.answers },
                enrichment_answers: enrichment,
                time_taken: timeTaken,
            };

            const queueRef = useLocalStorage<OfflineSubmission[]>('survey-offline-queue', []);

            if (!this.isOnline) {
                queueRef.value.push({ ...submission, timestamp: Date.now() });
                this.answers = {};
                this.loading = false;

                return { offline: true };
            }

            try {
                const { data } = await axios.post(`/surveys/${surveyId}/submit`, submission);
                this.answers = {};

                return data;
            } catch (err) {
                this.error = 'Failed to submit survey';
                console.error(err);

                throw err;
            } finally {
                this.loading = false;
            }
        },
        async syncOfflineSubmissions() {
            const queueRef = useLocalStorage<OfflineSubmission[]>('survey-offline-queue', []);
            
            if (!this.isOnline || queueRef.value.length === 0) {
return;
}

            const queue = [...queueRef.value];
            queueRef.value = []; // Clear queue optimistically

            for (const sub of queue) {
                try {
                    await axios.post(`/surveys/${sub.surveyId}/submit`, {
                        answers: sub.answers,
                        enrichment_answers: sub.enrichment_answers,
                    });
                } catch (err) {
                    console.error('Failed to sync offline submission', sub, err);

                    // Re-add to queue if it's a network error
                    if (!axios.isAxiosError(err) || !err.response) {
                        queueRef.value.push(sub);
                    }
                }
            }
        },
        async enterPrizeDraw(surveyId: number) {
            this.loading = true;

            try {
                await axios.post(`/surveys/${surveyId}/prize-draw`);
            } catch (err) {
                this.error = 'Failed to enter prize draw';
                console.error(err);

                throw err;
            } finally {
                this.loading = false;
            }
        },
    },
});
