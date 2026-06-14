<script setup lang="ts">
import { Github, Facebook } from 'lucide-vue-next';

const props = defineProps({
    member: Object,
});

const getRoleTitle = (role: string) => {
    switch (role) {
        case 'admin':
            return 'Lead Maintainer & Developer';

        case 'editor':
            return 'Resource Curator';

        case 'manager':
            return 'Operations & Community Manager';

        default:
            return 'Team Member';
    }
};
</script>

<template>
    <div
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-8 text-center transition-all duration-300 hover:border-slate-300 hover:shadow-md hover:shadow-slate-100/50"
    >
        <div class="flex flex-col items-center">
            <div class="mb-6">
                <img
                    v-if="member.image"
                    :src="member.image"
                    :alt="member.name"
                    class="h-28 w-28 rounded-3xl border border-slate-100 object-cover shadow-sm transition-transform duration-300 group-hover:scale-[1.03]"
                />
                <div
                    v-else
                    class="flex h-28 w-28 items-center justify-center rounded-3xl border border-indigo-100 bg-indigo-50 text-2xl font-black text-indigo-600 shadow-sm"
                >
                    {{
                        member.name
                            .split(' ')
                            .map((n) => n[0])
                            .join('')
                    }}
                </div>
            </div>

            <div class="space-y-1">
                <h3 class="text-xl font-black tracking-tight text-slate-950">
                    {{ member.name }}
                </h3>
                <p
                    class="text-xs font-bold tracking-wide text-indigo-600 uppercase"
                >
                    {{ getRoleTitle(member.roles?.[0]?.name) }}
                </p>
                <p class="text-xs font-semibold text-slate-400">
                    {{ member.institution }}
                </p>
            </div>

            <p
                class="mt-5 max-w-xs text-sm leading-relaxed font-medium text-slate-600"
            >
                {{ member.about }}
            </p>
        </div>

        <div
            class="mt-8 flex items-center justify-center gap-6 border-t border-slate-100 pt-5"
        >
            <a
                v-if="member.github"
                :href="member.github"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 transition-colors hover:text-slate-950"
            >
                <Github class="h-4 w-4" />
                GitHub
            </a>
            <span
                v-if="member.github && member.facebook"
                class="h-3.5 w-px bg-slate-200"
            ></span>
            <a
                v-if="member.facebook"
                :href="member.facebook"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 transition-colors hover:text-indigo-600"
            >
                <Facebook class="h-4 w-4" />
                Connect
            </a>
        </div>
    </div>
</template>
