export function useHaptics() {
    const vibrate = (pattern: number | number[] = 10) => {
        if (typeof window !== 'undefined' && 'vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    };

    const lightClick = () => vibrate(10);
    const mediumClick = () => vibrate(20);
    const heavyClick = () => vibrate(30);
    const success = () => vibrate([10, 50, 10]);
    const error = () => vibrate([50, 50, 50]);

    return {
        vibrate,
        lightClick,
        mediumClick,
        heavyClick,
        success,
        error,
    };
}
