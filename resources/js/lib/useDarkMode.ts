import { ref, watch, onMounted, onUnmounted } from 'vue';

type Theme = 'light' | 'dark' | 'system';

const theme = ref<Theme>('system');
const isDark = ref(false);
let initialized = false;
let mediaQuery: MediaQueryList | null = null;

function applyTheme(dark: boolean) {
    document.documentElement.classList.toggle('dark', dark);
}

function resolveTheme(t: Theme): boolean {
    if (t === 'system') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    return t === 'dark';
}

function handleSystemChange() {
    if (theme.value === 'system') {
        isDark.value = resolveTheme('system');
        applyTheme(isDark.value);
    }
}

export function useDarkMode() {
    if (!initialized) {
        initialized = true;

        onMounted(() => {
            const stored = localStorage.getItem('theme') as Theme | null;

            if (
                stored === 'light' ||
                stored === 'dark' ||
                stored === 'system'
            ) {
                theme.value = stored;
            } else {
                theme.value = 'system';
            }

            isDark.value = resolveTheme(theme.value);
            applyTheme(isDark.value);

            mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', handleSystemChange);

            window.addEventListener('storage', (e) => {
                if (e.key === 'theme') {
                    const val = e.newValue as Theme | null;

                    if (val === 'light' || val === 'dark' || val === 'system') {
                        theme.value = val;
                        isDark.value = resolveTheme(val);
                        applyTheme(isDark.value);
                    }
                }
            });
        });

        onUnmounted(() => {
            mediaQuery?.removeEventListener('change', handleSystemChange);
        });

        watch(theme, (t) => {
            isDark.value = resolveTheme(t);
            applyTheme(isDark.value);
            localStorage.setItem('theme', t);
        });
    }

    function toggle() {
        const cycle: Theme[] = ['system', 'light', 'dark'];
        const next = cycle[(cycle.indexOf(theme.value) + 1) % 3];
        theme.value = next;
    }

    return { theme, isDark, toggle };
}
