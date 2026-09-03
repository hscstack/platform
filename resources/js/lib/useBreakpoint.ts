import { onBeforeUnmount, onMounted, ref } from 'vue';

export function useBreakpoint(breakpointPx = 1024) {
    const isMobile = ref(false);
    const isDesktop = ref(true);
    const isHydrated = ref(false);

    let mql: MediaQueryList | null = null;

    const update = () => {
        if (mql) {
            isMobile.value = mql.matches;
            isDesktop.value = !mql.matches;

            return;
        }

        if (typeof window !== 'undefined') {
            isMobile.value = window.innerWidth < breakpointPx;
            isDesktop.value = !isMobile.value;
        }
    };

    const handler = () => update();

    onMounted(() => {
        isHydrated.value = true;

        if (typeof window === 'undefined') {
            return;
        }

        mql = window.matchMedia(`(max-width: ${breakpointPx - 1}px)`);
        update();

        if (mql.addEventListener) {
            mql.addEventListener('change', handler);
        } else {
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

    return { isMobile, isDesktop, isHydrated };
}
