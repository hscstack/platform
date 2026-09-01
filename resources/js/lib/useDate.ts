export function formatTimeAgo(
    dateInput?: string | number | Date | null,
): string {
    if (!dateInput) {
        return '';
    }

    const date =
        typeof dateInput === 'object' ? dateInput : new Date(dateInput);

    if (isNaN(date.getTime())) {
        return typeof dateInput === 'string' ? dateInput : '';
    }

    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days}d ago`;
    }

    const months = Math.floor(days / 30);

    if (months < 12) {
        return `${months}mo ago`;
    }

    const years = Math.floor(days / 365);

    return `${years}y ago`;
}

export function formatDate(
    dateInput?: string | number | Date | null,
    options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    },
): string {
    if (!dateInput) {
        return '';
    }

    const date =
        typeof dateInput === 'object' ? dateInput : new Date(dateInput);

    if (isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleDateString('en-US', options);
}

export function formatDateTime(
    dateInput?: string | number | Date | null,
): string {
    if (!dateInput) {
        return '';
    }

    const date =
        typeof dateInput === 'object' ? dateInput : new Date(dateInput);

    if (isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleString([], {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatTime(dateInput?: string | number | Date | null): string {
    if (!dateInput) {
        return '';
    }

    const date =
        typeof dateInput === 'object' ? dateInput : new Date(dateInput);

    if (isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatDateDivider(
    dateInput?: string | number | Date | null,
): string {
    if (!dateInput) {
        return '';
    }

    const date =
        typeof dateInput === 'object' ? dateInput : new Date(dateInput);

    if (isNaN(date.getTime())) {
        return '';
    }

    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    }

    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year:
            date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined,
    });
}
