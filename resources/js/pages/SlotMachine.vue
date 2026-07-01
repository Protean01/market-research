<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import confetti from 'canvas-confetti';
import {
    ArrowLeft, Coins, Trophy, Sparkles, Users,
    Cherry, Zap, Bell, Diamond, Star, Target, Heart, Circle
} from 'lucide-vue-next';
import { ref  } from 'vue';
import type {Component} from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    survey: any;
    prizeName: string;
    prizes: { name?: string; points?: number; amount?: number }[];
    prizesRemaining: number;
    winnersCount: number;
    rewardType: string;
    rewardAmount: number;
    hasSpun: boolean;
    entrantCount: number;
}>();

const spinning = ref(false);
const finished = ref(false);
const won = ref(false);
const prizeAmount = ref(0);
const consolationAmount = ref(0);
const airtimeAmount = ref<number | null>(null);
const wonPrizeName = ref<string | null>(null);
const errorMessage = ref('');

// ── Audio Engine (Web Audio API) ──────────────────────────────────────────────
let audioCtx: AudioContext | null = null;

function getAudioCtx(): AudioContext {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
    }

    return audioCtx;
}

function playSpinStart() {
    const ctx = getAudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(80, ctx.currentTime);
    osc.frequency.linearRampToValueAtTime(220, ctx.currentTime + 0.25);
    gain.gain.setValueAtTime(0.12, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.25);
}

function playTick() {
    const ctx = getAudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'square';
    osc.frequency.setValueAtTime(200, ctx.currentTime);
    gain.gain.setValueAtTime(0.05, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.035);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.035);
}

function playReelStop(reelIndex: number) {
    const ctx = getAudioCtx();
    const bufferSize = Math.floor(ctx.sampleRate * 0.12);
    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
    const data = buffer.getChannelData(0);

    for (let i = 0; i < bufferSize; i++) {
        data[i] = (Math.random() * 2 - 1) * Math.exp(-i / (bufferSize * 0.25));
    }

    const source = ctx.createBufferSource();
    source.buffer = buffer;
    const filter = ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.value = 500 + reelIndex * 150;
    const gain = ctx.createGain();
    gain.gain.setValueAtTime(0.55, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.14);
    source.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);
    source.start(ctx.currentTime);
}

function playWin() {
    const ctx = getAudioCtx();
    const notes = [523.25, 659.25, 783.99, 1046.5, 1318.5];
    const delays = [0, 0.12, 0.24, 0.36, 0.5];
    notes.forEach((freq, i) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        const t = ctx.currentTime + delays[i];
        osc.frequency.setValueAtTime(freq, t);
        gain.gain.setValueAtTime(0, t);
        gain.gain.linearRampToValueAtTime(0.22, t + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, t + 0.5);
        osc.start(t);
        osc.stop(t + 0.5);
    });
    // Tail shimmer
    setTimeout(() => {
        const ctx2 = getAudioCtx();
        const osc = ctx2.createOscillator();
        const gain = ctx2.createGain();
        osc.connect(gain);
        gain.connect(ctx2.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(1046.5, ctx2.currentTime);
        osc.frequency.linearRampToValueAtTime(2093, ctx2.currentTime + 0.25);
        gain.gain.setValueAtTime(0.15, ctx2.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx2.currentTime + 0.4);
        osc.start(ctx2.currentTime);
        osc.stop(ctx2.currentTime + 0.4);
    }, 650);
}

function playLose() {
    const ctx = getAudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(320, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(110, ctx.currentTime + 0.55);
    gain.gain.setValueAtTime(0.14, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.55);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.55);
}
// ─────────────────────────────────────────────────────────────────────────────

// Symbol definitions with Lucide icons
type SlotSymbol = {
    icon: Component;
    color: string;
    name: string;
};

const symbols: SlotSymbol[] = [
    { icon: Cherry, color: 'text-red-500', name: 'Cherry' },
    { icon: Zap, color: 'text-yellow-500', name: 'Lightning' },
    { icon: Bell, color: 'text-blue-500', name: 'Bell' },
    { icon: Diamond, color: 'text-purple-500', name: 'Diamond' },
    { icon: Star, color: 'text-amber-400', name: 'Star' },
    { icon: Target, color: 'text-red-600', name: 'Target' },
    { icon: Heart, color: 'text-pink-500', name: 'Heart' },
    { icon: Circle, color: 'text-green-500', name: 'Circle' },
];

const winSymbol: SlotSymbol = { icon: Trophy, color: 'text-amber-400', name: 'Trophy' };

// Reels now store symbol indices instead of emoji strings
const reel1 = ref(3); // Diamond
const reel2 = ref(0); // Cherry
const reel3 = ref(4); // Star

const reel1Spinning = ref(false);
const reel2Spinning = ref(false);
const reel3Spinning = ref(false);

const reel1Settled = ref(false);
const reel2Settled = ref(false);
const reel3Settled = ref(false);

let intervalIds: any[] = [];

function randomSymbol(): number {
    return Math.floor(Math.random() * symbols.length);
}

function stopReel(
    reelRef: typeof reel1,
    spinRef: typeof reel1Spinning,
    settledRef: typeof reel1Settled,
    symbolIndex: number
) {
    return new Promise<void>(resolve => {
        spinRef.value = false;
        // Brief bounce effect then settle
        setTimeout(() => {
            reelRef.value = symbolIndex;
            settledRef.value = true;
            resolve();
        }, 120);
    });
}

const spin = async () => {
    if (spinning.value || props.hasSpun || finished.value) {
return;
}

    spinning.value = true;
    errorMessage.value = '';
    reel1Settled.value = false;
    reel2Settled.value = false;
    reel3Settled.value = false;

    // All three reels start spinning simultaneously
    reel1Spinning.value = true;
    reel2Spinning.value = true;
    reel3Spinning.value = true;

    playSpinStart();

    let t1 = 0, t2 = 0, t3 = 0;
    const interval1 = setInterval(() => {
 reel1.value = randomSymbol();

 if (t1++ % 3 === 0) {
playTick();
} 
}, 80);
    const interval2 = setInterval(() => {
 reel2.value = randomSymbol();

 if (t2++ % 3 === 0) {
playTick();
} 
}, 100);
    const interval3 = setInterval(() => {
 reel3.value = randomSymbol();

 if (t3++ % 3 === 0) {
playTick();
} 
}, 120);
    intervalIds = [interval1, interval2, interval3];

    try {
        const { data } = await axios.post(`/surveys/${props.survey.id}/spin`);

        // For win, use special index -1 to indicate trophy; otherwise random different symbols
        // Airtime spins always win
        const finalSymbols = data.win
            ? [-1, -1, -1] // -1 = Trophy (win symbol)
            : [0, 1, 2]; // Different symbols for losing

        // Stagger: reel 1 stops at 2s, reel 2 at 2.8s, reel 3 at 3.6s
        setTimeout(async () => {
            clearInterval(interval1);
            playReelStop(0);
            await stopReel(reel1, reel1Spinning, reel1Settled, finalSymbols[0]);
        }, 2000);

        setTimeout(async () => {
            clearInterval(interval2);
            playReelStop(1);
            await stopReel(reel2, reel2Spinning, reel2Settled, finalSymbols[1]);
        }, 2800);

        setTimeout(async () => {
            clearInterval(interval3);
            playReelStop(2);
            await stopReel(reel3, reel3Spinning, reel3Settled, finalSymbols[2]);

            // All done
            spinning.value = false;
            finished.value = true;
            won.value = data.win;
            prizeAmount.value = data.prizePoints ?? 0;
            consolationAmount.value = data.consolationPoints ?? 0;
            airtimeAmount.value = data.airtimeAmount ?? null;
            wonPrizeName.value = data.prizeName ?? null;

            if (data.win) {
                triggerWinEffects();
                playWin();
            } else {
                playLose();
            }
        }, 3600);

    } catch (error: any) {
        intervalIds.forEach(clearInterval);
        reel1Spinning.value = false;
        reel2Spinning.value = false;
        reel3Spinning.value = false;
        spinning.value = false;
        errorMessage.value = error.response?.data?.error || 'Something went wrong. Please try again.';
    }
};

function triggerWinEffects() {
    // First burst — from bottom center
    confetti({
        particleCount: 120,
        spread: 80,
        origin: { x: 0.5, y: 0.8 },
        colors: ['#f59e0b', '#fbbf24', '#fde68a', '#ffffff', '#6366f1', '#a78bfa'],
        zIndex: 9999,
    });

    // Left cannon
    setTimeout(() => {
        confetti({
            particleCount: 80,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0.6 },
            colors: ['#f59e0b', '#fbbf24', '#fde68a', '#10b981'],
            zIndex: 9999,
        });
    }, 300);

    // Right cannon
    setTimeout(() => {
        confetti({
            particleCount: 80,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0.6 },
            colors: ['#f59e0b', '#fbbf24', '#fde68a', '#6366f1'],
            zIndex: 9999,
        });
    }, 500);

    // Finale burst from top
    setTimeout(() => {
        confetti({
            particleCount: 200,
            spread: 120,
            startVelocity: 45,
            origin: { x: 0.5, y: 0.2 },
            colors: ['#f59e0b', '#fbbf24', '#ec4899', '#6366f1', '#10b981', '#ffffff'],
            zIndex: 9999,
        });
    }, 800);
}
</script>

<template>
    <Head title="Prize Draw Slot Machine" />
    <AppLayout>
        <div class="min-h-[85vh] flex flex-col items-center justify-center p-6 bg-gradient-to-br from-indigo-900 via-purple-900 to-slate-900 rounded-[3rem] mx-4 my-6 shadow-2xl relative overflow-hidden">

            <!-- Animated Background Orbs -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/20 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full mix-blend-screen filter blur-3xl animate-pulse" style="animation-delay: 1.5s"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-amber-500/10 rounded-full mix-blend-screen filter blur-3xl animate-pulse" style="animation-delay: 3s"></div>
            </div>

            <div class="w-full max-w-lg z-10">
                <!-- Back link -->
                <Link href="/dashboard" class="inline-flex items-center gap-2 text-indigo-300 hover:text-white transition-colors mb-8 text-sm font-bold uppercase tracking-widest">
                    <ArrowLeft class="w-4 h-4" /> Back to Dashboard
                </Link>

                <!-- Header -->
                <div class="text-center space-y-3 mb-10">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-white text-xs font-black uppercase tracking-widest border border-white/20 backdrop-blur-sm">
                        <Trophy class="w-4 h-4 text-amber-400" />
                        Prize Draw
                    </div>
                    <h1 class="text-4xl sm:text-5xl font-black text-white tracking-tight drop-shadow-md">
                        {{ survey.title }}
                    </h1>
                    <p class="text-lg font-medium text-indigo-200">
                        Spin to win <strong class="text-amber-400">{{ prizeName }}</strong>
                    </p>

                    <!-- Multi-prize list -->
                    <div v-if="prizes.length > 1" class="flex flex-col items-center gap-2 mt-2">
                        <p class="text-xs font-black uppercase tracking-widest text-indigo-300">
                            {{ prizesRemaining }} of {{ prizes.length }} prize{{ prizes.length === 1 ? '' : 's' }} remaining
                        </p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <span
                                v-for="(prize, i) in prizes"
                                :key="i"
                                class="px-3 py-1 rounded-full text-xs font-bold border"
                                :class="i < winnersCount
                                    ? 'bg-white/5 border-white/10 text-white/30 line-through'
                                    : 'bg-amber-400/10 border-amber-400/30 text-amber-300'"
                            >
                                {{ prize.name || `Prize ${i + 1}` }}
                                <span v-if="rewardType === 'airtime'"> · {{ prize.amount }}</span>
                                <span v-else-if="prize.points"> · {{ prize.points }} pts</span>
                            </span>
                        </div>
                    </div>

                    <p v-if="entrantCount > 0 && !finished" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 text-indigo-200 text-xs font-bold">
                        <Users class="w-3.5 h-3.5" />
                        You are 1 of {{ entrantCount }} entrant{{ entrantCount === 1 ? '' : 's' }}
                    </p>
                </div>

                <!-- Already played -->
                <div v-if="props.hasSpun && !finished" class="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-3xl text-center">
                    <p class="text-xl font-bold text-white mb-2">You have already played!</p>
                    <p class="text-indigo-200 text-sm">Thanks for participating. Check your notifications if you won!</p>
                </div>

                <div v-else class="space-y-8">
                    <!-- Slot Machine Cabinet -->
                    <div class="bg-slate-800 p-4 sm:p-6 rounded-[2.5rem] border-[6px] border-slate-700 shadow-[0_0_60px_rgba(0,0,0,0.6),inset_0_1px_0_rgba(255,255,255,0.08)] relative">

                        <!-- Top light bar -->
                        <div
                            class="absolute -top-3 left-1/2 -translate-x-1/2 w-36 h-3 rounded-full transition-all duration-500"
                            :class="won
                                ? 'bg-amber-400 shadow-[0_0_30px_10px_rgba(251,191,36,0.8)] animate-pulse'
                                : spinning
                                    ? 'bg-red-500 shadow-[0_0_20px_5px_rgba(239,68,68,0.7)] animate-pulse'
                                    : 'bg-slate-600'"
                        ></div>

                        <!-- Reels -->
                        <div class="grid grid-cols-3 gap-3 sm:gap-5 bg-slate-900 p-4 sm:p-6 rounded-3xl shadow-inner border border-slate-950">

                            <!-- Reel 1 -->
                            <div class="aspect-square bg-white dark:bg-zinc-100 rounded-2xl flex items-center justify-center shadow-inner relative overflow-hidden"
                                :class="reel1Settled ? 'ring-4 ring-amber-400/60' : ''">
                                <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/20 pointer-events-none z-10"></div>
                                <component
                                    :is="reel1 === -1 ? winSymbol.icon : symbols[reel1].icon"
                                    :class="[
                                        'w-16 h-16 sm:w-24 sm:h-24 transition-transform duration-300',
                                        reel1 === -1 ? winSymbol.color : symbols[reel1].color,
                                        {
                                            'animate-reel-spin': reel1Spinning,
                                            'scale-125 drop-shadow-[0_0_12px_rgba(251,191,36,0.9)]': reel1Settled && won,
                                        }
                                    ]"
                                />
                            </div>

                            <!-- Reel 2 -->
                            <div class="aspect-square bg-white dark:bg-zinc-100 rounded-2xl flex items-center justify-center shadow-inner relative overflow-hidden"
                                :class="reel2Settled ? 'ring-4 ring-amber-400/60' : ''">
                                <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/20 pointer-events-none z-10"></div>
                                <component
                                    :is="reel2 === -1 ? winSymbol.icon : symbols[reel2].icon"
                                    :class="[
                                        'w-16 h-16 sm:w-24 sm:h-24 transition-transform duration-300',
                                        reel2 === -1 ? winSymbol.color : symbols[reel2].color,
                                        {
                                            'animate-reel-spin': reel2Spinning,
                                            'scale-125 drop-shadow-[0_0_12px_rgba(251,191,36,0.9)]': reel2Settled && won,
                                        }
                                    ]"
                                />
                            </div>

                            <!-- Reel 3 -->
                            <div class="aspect-square bg-white dark:bg-zinc-100 rounded-2xl flex items-center justify-center shadow-inner relative overflow-hidden"
                                :class="reel3Settled ? 'ring-4 ring-amber-400/60' : ''">
                                <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/20 pointer-events-none z-10"></div>
                                <component
                                    :is="reel3 === -1 ? winSymbol.icon : symbols[reel3].icon"
                                    :class="[
                                        'w-16 h-16 sm:w-24 sm:h-24 transition-transform duration-300',
                                        reel3 === -1 ? winSymbol.color : symbols[reel3].color,
                                        {
                                            'animate-reel-spin': reel3Spinning,
                                            'scale-125 drop-shadow-[0_0_12px_rgba(251,191,36,0.9)]': reel3Settled && won,
                                        }
                                    ]"
                                />
                            </div>
                        </div>

                        <!-- Reel status dots -->
                        <div class="flex justify-center gap-6 mt-4">
                            <div class="w-2 h-2 rounded-full transition-all duration-300"
                                :class="reel1Settled ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.9)]' : 'bg-slate-600'"></div>
                            <div class="w-2 h-2 rounded-full transition-all duration-300"
                                :class="reel2Settled ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.9)]' : 'bg-slate-600'"></div>
                            <div class="w-2 h-2 rounded-full transition-all duration-300"
                                :class="reel3Settled ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.9)]' : 'bg-slate-600'"></div>
                        </div>
                    </div>

                    <!-- SPIN button / Results -->
                    <div class="text-center min-h-[120px] flex flex-col items-center justify-center gap-4">

                        <!-- Spin button -->
                        <button
                            v-if="!finished"
                            @click="spin"
                            :disabled="spinning"
                            class="relative group disabled:opacity-70 disabled:cursor-not-allowed"
                        >
                            <div class="absolute inset-0 bg-amber-400 rounded-full blur-2xl opacity-40 group-hover:opacity-70 transition-opacity duration-300 group-disabled:opacity-20"></div>
                            <div class="relative px-14 py-5 bg-gradient-to-b from-amber-300 to-amber-500 text-amber-950 rounded-full font-black text-2xl uppercase tracking-widest shadow-[0_10px_0_rgb(180,83,9)] group-active:translate-y-2 group-active:shadow-[0_2px_0_rgb(180,83,9)] transition-all duration-100">
                                <span v-if="!spinning">SPIN NOW</span>
                                <span v-else class="flex items-center gap-3">
                                    <svg class="animate-spin h-6 w-6" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                    Spinning...
                                </span>
                            </div>
                        </button>

                        <!-- Win result -->
                        <div v-else-if="won" class="animate-in zoom-in slide-in-from-bottom-4 duration-700 text-center space-y-4">
                            <div class="inline-flex items-center justify-center gap-3 bg-amber-400 text-amber-950 px-8 py-4 rounded-2xl font-black text-2xl shadow-[0_0_60px_rgba(251,191,36,0.7)] animate-pulse">
                                <Sparkles class="w-7 h-7" />
                                JACKPOT! YOU WON!
                                <Sparkles class="w-7 h-7" />
                            </div>
                            <p v-if="wonPrizeName" class="text-amber-300 font-black text-lg uppercase tracking-widest">
                                {{ wonPrizeName }}
                            </p>
                            <p v-if="rewardType === 'airtime'" class="text-white text-lg font-bold flex items-center justify-center gap-2">
                                <Zap class="w-5 h-5 text-amber-400" />
                                {{ airtimeAmount ?? rewardAmount }} airtime will be sent to your phone!
                            </p>
                            <p v-else class="text-white text-lg font-bold flex items-center justify-center gap-2">
                                <Coins class="w-5 h-5 text-amber-400" />
                                +{{ prizeAmount }} Points added to your balance!
                            </p>
                            <Link href="/wallet" class="inline-flex items-center gap-2 mt-2 px-6 py-3 rounded-2xl bg-white/10 border border-white/20 text-white font-black text-sm uppercase tracking-widest hover:bg-white/20 transition-all">
                                View Rewards →
                            </Link>
                        </div>

                        <!-- Lose result -->
                        <div v-else class="animate-in fade-in slide-in-from-bottom-4 duration-500 text-center space-y-4">
                            <div class="inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-white/10 text-white border border-white/20 font-black text-xl">
                                Better luck next time!
                            </div>
                            <div v-if="consolationAmount > 0" class="flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-indigo-500/20 border border-indigo-400/30">
                                <Coins class="w-5 h-5 text-indigo-300" />
                                <span class="text-indigo-200 font-bold text-sm">+{{ consolationAmount }} consolation points added to your balance</span>
                            </div>
                            <p class="text-indigo-200 font-medium text-sm">Thanks for participating. Keep completing surveys to build your streak and tier bonus!</p>
                            <Link href="/dashboard" class="inline-flex items-center gap-2 mt-1 px-6 py-3 rounded-2xl bg-white/10 border border-white/20 text-white font-black text-sm uppercase tracking-widest hover:bg-white/20 transition-all">
                                Find More Surveys →
                            </Link>
                        </div>

                        <!-- Error -->
                        <p v-if="errorMessage" class="text-red-400 font-bold text-sm mt-2 bg-red-500/10 px-4 py-2 rounded-xl border border-red-500/20">
                            {{ errorMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes reelSpin {
    0%   { transform: translateY(-40%) scaleY(0.7); opacity: 0.3; }
    50%  { transform: translateY(0%)   scaleY(1);   opacity: 1;   }
    100% { transform: translateY(40%)  scaleY(0.7); opacity: 0.3; }
}

.animate-reel-spin {
    animation: reelSpin 0.15s linear infinite;
}
</style>
