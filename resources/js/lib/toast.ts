export function showToast(
    message: string,
    type: 'success' | 'error' | 'info' = 'info',
) {
    if (typeof window !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('app-toast', { detail: { message, type } }),
        );
    }
}
