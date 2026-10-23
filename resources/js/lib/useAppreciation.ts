import { reactive } from 'vue';

// Shared reactive map of userId -> is_appreciated boolean across all components
const appreciationMap = reactive<Record<number, boolean>>({});

export function useAppreciation() {
    const isAppreciated = (
        userId?: number | null,
        fallback?: boolean,
    ): boolean => {
        if (!userId) {
            return false;
        }

        if (appreciationMap[userId] === undefined && fallback !== undefined) {
            appreciationMap[userId] = Boolean(fallback);
        }

        return Boolean(appreciationMap[userId]);
    };

    const setAppreciation = (userId: number, status: boolean): void => {
        appreciationMap[userId] = status;
    };

    const initAppreciation = (
        userId?: number | null,
        initial?: boolean,
    ): void => {
        if (!userId) {
            return;
        }

        if (appreciationMap[userId] === undefined && initial !== undefined) {
            appreciationMap[userId] = Boolean(initial);
        }
    };

    return {
        appreciationMap,
        isAppreciated,
        setAppreciation,
        initAppreciation,
    };
}
