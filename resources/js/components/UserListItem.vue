<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';

const props = withDefaults(
    defineProps<{
        user: {
            id?: number;
            name: string;
            username?: string;
            image_url?: string | null;
            image_path?: string | null;
            institution?: string | null;
            title?: string | null;
            is_verified?: boolean;
            roles?: Array<{ id?: number; name: string }>;
        };
        theme?: 'indigo' | 'emerald' | 'rose';
        subtitle?: string | null;
    }>(),
    {
        theme: 'indigo',
        subtitle: null,
    },
);

const profileUrl = computed(() => {
    return props.user.username ? `/u/${props.user.username}` : '#';
});

const isVerified = computed(() => {
    return Boolean(
        props.user.is_verified ??
        (props.user.roles && props.user.roles.length > 0),
    );
});

const avatarBgClass = computed(() => {
    if (props.theme === 'rose') {
        return 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300 group-hover/user:ring-rose-400';
    }

    if (props.theme === 'emerald') {
        return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 group-hover/user:ring-emerald-400';
    }

    return 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300 group-hover/user:ring-indigo-400';
});
</script>

<template>
    <Link
        :href="profileUrl"
        class="group/user flex items-center gap-3 py-2.5 transition"
    >
        <div
            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold transition group-hover/user:ring-2"
            :class="avatarBgClass"
        >
            <img
                v-if="user.image_url || user.image_path"
                :src="
                    user.image_url ||
                    (user.image_path?.startsWith('http')
                        ? user.image_path
                        : '/storage/' + user.image_path)
                "
                :alt="user.name"
                class="h-full w-full object-cover"
            />
            <span v-else class="text-xs uppercase">
                {{ user.name?.charAt(0) || 'U' }}
            </span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1">
                <p
                    class="truncate text-xs font-semibold text-slate-900 group-hover/user:text-indigo-600 dark:text-gray-100 dark:group-hover/user:text-indigo-400"
                >
                    {{ user.name }}
                </p>
                <VerifiedBadge v-if="isVerified" />
            </div>
            <p
                v-if="subtitle || user.institution || user.title"
                class="truncate text-[11px] text-slate-500 dark:text-gray-400"
            >
                {{ subtitle || user.institution || user.title }}
            </p>
        </div>
    </Link>
</template>
