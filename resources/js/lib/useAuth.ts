import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const showAuthModal = ref(false);
const authModalMessage = ref('Please sign in to continue.');

export function useAuth() {
    const page = usePage();

    const user = computed(() => (page.props.auth as any)?.user || null);
    const isAuthenticated = computed(() => !!user.value);
    const userId = computed<number | null>(() => user.value?.id ?? null);
    const permissions = computed<string[]>(
        () => (page.props.auth as any)?.permissions || [],
    );

    const can = (permission: string) => {
        return permissions.value.includes(permission);
    };

    /**
     * Executes action if authenticated; otherwise opens AuthModal with the specified message.
     * Returns true if user is authenticated, false otherwise.
     */
    const requireAuth = (
        messageOrAction:
            | string
            | (() => void) = 'Please sign in to perform this action.',
        actionOrMessage?: string | (() => void),
    ): boolean => {
        let message = 'Please sign in to perform this action.';
        let action: (() => void) | undefined;

        if (typeof messageOrAction === 'string') {
            message = messageOrAction;

            if (typeof actionOrMessage === 'function') {
                action = actionOrMessage;
            }
        } else if (typeof messageOrAction === 'function') {
            action = messageOrAction;

            if (typeof actionOrMessage === 'string') {
                message = actionOrMessage;
            }
        }

        if (!isAuthenticated.value) {
            authModalMessage.value = message;
            showAuthModal.value = true;

            return false;
        }

        if (action) {
            action();
        }

        return true;
    };

    const closeAuthModal = () => {
        showAuthModal.value = false;
    };

    return {
        user,
        isAuthenticated,
        userId,
        permissions,
        can,
        showAuthModal,
        authModalMessage,
        requireAuth,
        closeAuthModal,
    };
}
