import { onBeforeUnmount, onMounted, ref } from 'vue';

export type Orientation = 'landscape' | 'portrait';

export function useOrientation() {
    const isLandscape = ref<boolean>(false);
    const isPortrait = ref<boolean>(true);
    const isHydrated = ref<boolean>(false);

    let mql: MediaQueryList | null = null;

    const update = () => {
        if (mql) {
            isLandscape.value = mql.matches;
            isPortrait.value = !mql.matches;

            return;
        }

        if (typeof window !== 'undefined') {
            isLandscape.value = window.innerWidth > window.innerHeight;
            isPortrait.value = !isLandscape.value;
        }
    };

    const handler = () => update();

    onMounted(() => {
        isHydrated.value = true;

        if (typeof window === 'undefined') {
            return;
        }

        mql = window.matchMedia('(orientation: landscape)');
        update();

        // Modern browsers
        if (mql.addEventListener) {
            mql.addEventListener('change', handler);
        } else {
            // Safari fallback
            mql.addListener(handler);
        }

        window.addEventListener('resize', handler);
    });

    onBeforeUnmount(() => {
        if (!mql) {
            return;
        }

        if (mql.removeEventListener) {
            mql.removeEventListener('change', handler);
        } else {
            mql.removeListener(handler);
        }

        if (typeof window !== 'undefined') {
            window.removeEventListener('resize', handler);
        }
    });

    return { isLandscape, isPortrait, isHydrated };
}
