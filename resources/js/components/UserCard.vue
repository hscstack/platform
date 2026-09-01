<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, GraduationCap } from 'lucide-vue-next';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    member: {
        id: number;
        name: string;
        username?: string;
        title?: string | null;
        about?: string | null;
        institution?: string | null;
        image_url?: string | null;
        roles?: Array<{ name: string }>;
    };
}>();

const profileUrl = computed(() => {
    return props.member.username ? `/u/${props.member.username}` : '#';
});
</script>

<template>
    <div
        class="group flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-6 text-center shadow-xs transition-all duration-300 hover:border-indigo-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/60"
    >
        <div class="flex flex-col items-center">
            <!-- Avatar Linking to Public Profile -->
            <Link
                :href="profileUrl"
                class="mb-4 block transition-transform group-hover:scale-105"
            >
                <div
                    class="h-24 w-24 overflow-hidden rounded-2xl shadow-sm ring-4 ring-slate-100 dark:ring-gray-800"
                >
                    <img
                        v-if="member.image_url"
                        :src="member.image_url"
                        :alt="member.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-700 text-2xl font-black text-white"
                    >
                        {{ member.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
            </Link>

            <!-- Name, Handle & Role -->
            <div class="space-y-1">
                <div class="flex items-center justify-center gap-1.5">
                    <Link
                        :href="profileUrl"
                        class="text-lg font-black tracking-tight text-slate-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                    >
                        {{ member.name }}
                    </Link>
                </div>

                <!-- Role Pill -->
                <div class="flex items-center justify-center pt-0.5">
                    <StatusBadge
                        :status="member.roles?.[0]?.name || 'staff'"
                        size="xs"
                    />
                </div>

                <!-- Title / Tagline -->
                <p
                    v-if="member.title"
                    class="pt-1 text-xs font-bold text-indigo-600 dark:text-indigo-400"
                >
                    {{ member.title }}
                </p>

                <!-- Institution -->
                <div
                    v-if="member.institution"
                    class="flex items-center justify-center gap-1 text-xs font-medium text-slate-500 dark:text-gray-400"
                >
                    <GraduationCap
                        class="h-3.5 w-3.5 shrink-0 text-slate-400"
                    />
                    <span>{{ member.institution }}</span>
                </div>
            </div>

            <!-- About Snippet -->
            <p
                v-if="member.about"
                class="mt-4 line-clamp-3 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
            >
                {{ member.about }}
            </p>
        </div>

        <!-- Minimal Footer: View Profile -->
        <div class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800">
            <Link
                :href="profileUrl"
                class="group/btn inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-900 hover:text-white dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-100 dark:hover:text-gray-900"
            >
                <span>View Profile</span>
                <ArrowRight
                    class="h-3.5 w-3.5 transition-transform group-hover/btn:translate-x-0.5"
                />
            </Link>
        </div>
    </div>
</template>
