import { useLocalStorage } from '@vueuse/core';
import { defineStore } from 'pinia';

export const useSettingsStore = defineStore('settings', {
    state: () => ({
        dataSaver: useLocalStorage('mrp_data_saver', false),
    }),
    actions: {
        toggleDataSaver() {
            this.dataSaver = !this.dataSaver;
        }
    },
});
