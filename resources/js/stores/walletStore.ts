import axios from 'axios';
import { defineStore } from 'pinia';

export const useWalletStore = defineStore('wallet', {
    state: () => ({
        balance: 0,
        points: 0,
        transactions: [],
        loading: false,
    }),
    actions: {
        async fetchWallet() {
            this.loading = true;

            try {
                const res = await axios.get('/wallet/json');
                this.balance = res.data.balance;
                this.points = res.data.points;
                this.transactions = res.data.transactions;
            } finally {
                this.loading = false;
            }
        },
        async redeemAirtime(amount: number) {
            this.loading = true;

            try {
                await axios.post('/wallet/redeem', { amount });
            } finally {
                this.loading = false;
            }
        },
    },
});
