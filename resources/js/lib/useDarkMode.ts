import { ref } from 'vue';

export type Theme = 'light' | 'dark' | 'system';

function getInitialTheme(): Theme {
    if (typeof window === 'undefined') {
        return 'system';
    }

    const stored = localStorage.getItem('theme') as Theme | null;

    if (stored === 'light' || stored === 'dark' || stored === 'system') {
        return stored;
    }

    return 'system';
}

function resolveTheme(t: Theme): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    if (t === 'system') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    return t === 'dark';
}

function applyTheme(dark: boolean) {
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', dark);
        // Keep browser / PWA system chrome (status bar + nav bar) in sync.
        // Matches the slate-50 / gray-950 app background.
        // The manifest spec only allows one static theme_color, so swap the
        // linked manifest between the light (Vite-generated) and dark variants.
        const themeColor = dark ? '#030712' : '#f8fafc';
        document
            .querySelector('meta[name="theme-color"]:not([media])')
            ?.setAttribute('content', themeColor);
        document
            .querySelector('meta[name="msapplication-navbutton-color"]')
            ?.setAttribute('content', themeColor);
        document
            .querySelector('link#app-manifest')
            ?.setAttribute(
                'href',
                dark
                    ? '/manifest-dark.webmanifest'
                    : '/build/manifest.webmanifest',
            );
    }
}

const theme = ref<Theme>(getInitialTheme());
const isDark = ref<boolean>(resolveTheme(theme.value));

export function setTheme(t: Theme) {
    theme.value = t;
    isDark.value = resolveTheme(t);
    applyTheme(isDark.value);

    if (typeof window !== 'undefined') {
        localStorage.setItem('theme', t);
    }
}

// Initial apply
applyTheme(isDark.value);

if (typeof window !== 'undefined') {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', () => {
        if (theme.value === 'system') {
            isDark.value = resolveTheme('system');
            applyTheme(isDark.value);
        }
    });

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
}

export function useDarkMode() {
    function toggle() {
        const cycle: Theme[] = ['system', 'light', 'dark'];
        const next = cycle[(cycle.indexOf(theme.value) + 1) % 3];
        setTheme(next);
    }

    return { theme, isDark, toggle, setTheme };
}
