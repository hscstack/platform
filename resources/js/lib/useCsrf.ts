export function getCsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const meta = document.querySelector(
        'meta[name="csrf-token"]',
    ) as HTMLMetaElement | null;

    if (meta?.content) {
        return meta.content;
    }

    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}
