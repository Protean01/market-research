import axios from 'axios';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        loading: false,
        error: null as string | null,
        user: null as any,
        token: localStorage.getItem('pwa_token'),
    }),
    actions: {
        async logout() {
            this.loading = true;

            try {
                await axios.post('/logout');
            } catch (err) {
                console.error('Logout error:', err);
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('pwa_token');
                this.loading = false;
            }
        },
        async requestOTP(phoneNumber: string) {
            this.loading = true;

            try {
                const response = await axios.post('/auth/otp/send', { phone_number: phoneNumber });
                const data = response.data;

                if (data.skip_otp) {
                    this.token = data.token;
                    this.user = data.user;
                    localStorage.setItem('pwa_token', data.token);
                }

                return data; // Return data so component can check for skip_otp
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Failed to send OTP';

                throw err;
            } finally {
                this.loading = false;
            }
        },
        async verifyOTP(phoneNumber: string, otp: string, language?: string) {
            this.loading = true;

            try {
                const response = await axios.post('/auth/otp/verify', { phone_number: phoneNumber, otp, language });
                const { token, user } = response.data;
                this.token = token;
                this.user = user;
                localStorage.setItem('pwa_token', token);

                return response.data;
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Invalid OTP';

                throw err;
            } finally {
                this.loading = false;
            }
        },
    },
});
