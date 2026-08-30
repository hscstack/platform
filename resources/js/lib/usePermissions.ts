import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();
    const permissions = computed<string[]>(
        () => (page.props.auth as any)?.permissions || [],
    );

    const can = (permission: string) => {
        return permissions.value.includes(permission);
    };

    return { permissions, can };
}
