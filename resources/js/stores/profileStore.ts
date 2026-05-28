import axios from 'axios';
import { defineStore } from 'pinia';

interface ProfileState {
    profile: any;
    enrichedAttributes: Record<string, any>;
    birthYear: string | number;
    ageBand: string;
    gender: string;
    location: string;
    employment: string;
    incomeBand: string;
    language: string;
    loading: boolean;
    error: string | null;
}

export const useProfileStore = defineStore('profile', {
    state: (): ProfileState => ({
        profile: null,
        enrichedAttributes: {},
        birthYear: '',
        ageBand: '',
        gender: '',
        location: '',
        employment: '',
        incomeBand: '',
        language: '',
        loading: false,
        error: null,
    }),
    actions: {
        hydrate(profile: any) {
            this.profile = profile;

            if (profile) {
                this.birthYear = profile.birth_year || '';
                this.ageBand = profile.age_band || '';
                this.gender = profile.gender || '';
                this.location = profile.location || '';
                this.employment = profile.employment || '';
                this.incomeBand = profile.income_band || '';
                this.language = profile.language || '';
                
                if (profile.meta?.enrichment) {
                    this.enrichedAttributes = profile.meta.enrichment;
                }
            }
        },
        storeEnrichment(questionId: string | number, answer: any) {
            this.enrichedAttributes[questionId.toString()] = answer;
        },
        async save(payload: any) {
            this.loading = true;

            try {
                await axios.post('/profile', payload);
            } catch (err) {
                this.error = 'Failed to save profile';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
    },
});
