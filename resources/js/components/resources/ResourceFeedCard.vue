<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Book,
    Check,
    FileText,
    FlaskConical,
    Loader2,
    MessageSquare,
    Play,
    Share2,
    Video,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import ResourceVoteButtons from '@/components/resources/ResourceVoteButtons.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { useAppreciation } from '@/lib/useAppreciation';
import { useAuth } from '@/lib/useAuth';
import { getCsrfToken } from '@/lib/useCsrf';
import { formatTimeAgo } from '@/lib/useDate';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
    is_appreciated?: boolean;
}

interface NodeItem {
    id: number;
    subject_id?: number;
    name: string;
    slug: string;
    subject?: {
        id: number;
        name: string;
        course: string;
        slug: string;
    } | null;
}

interface Resource {
    id: number;
    user_id?: number;
    node_id: number;
    resource_type: string;
    title: string;
    description?: string | null;
    thumbnail?: string | null;
    items?: Array<{
        format: string;
        url?: string;
        source?: string;
        title?: string | null;
    }>;
    items_count?: number;
    vote_score: number;
    upvotes_count?: number;
    downvotes_count?: number;
    comments_count?: number;
    created_at: string;
    user?: User;
    node?: NodeItem | null;
    user_vote?: number | null;
}

const props = defineProps<{
    resource: Resource;
}>();

const { user: currentUser, requireAuth } = useAuth();
const { isAppreciated, setAppreciation, initAppreciation } = useAppreciation();

// Initialize shared appreciation cache
if (props.resource.user) {
    initAppreciation(
        props.resource.user.id,
        props.resource.user.is_appreciated,
    );
}

const isTogglingAppreciation = ref(false);

watch(
    () => props.resource.user?.is_appreciated,
    (val) => {
        if (props.resource.user && val !== undefined) {
            setAppreciation(props.resource.user.id, Boolean(val));
        }
    },
);

const toggleAppreciation = () => {
    if (
        !props.resource.user ||
        props.resource.user.id === currentUser.value?.id
    ) {
        return;
    }

    requireAuth(
        'সদস্যকে প্রশংসা (Appreciate) করতে অনুগ্রহ করে লগইন করুন।',
        async () => {
            if (isTogglingAppreciation.value) {
                return;
            }

            isTogglingAppreciation.value = true;

            const targetUser = props.resource.user!;
            const previousState = isAppreciated(
                targetUser.id,
                targetUser.is_appreciated,
            );
            setAppreciation(targetUser.id, !previousState);

            try {
                await router.post(
                    `/u/${targetUser.id}/appreciate`,
                    {},
                    {
                        preserveScroll: true,
                        preserveState: true,
                        only: ['auth', 'flash'],
                        onError: () => {
                            setAppreciation(targetUser.id, previousState);
                        },
                        onFinish: () => {
                            isTogglingAppreciation.value = false;
                        },
                    },
                );
            } catch {
                setAppreciation(targetUser.id, previousState);
                isTogglingAppreciation.value = false;
            }
        },
    );
};

const isVideo = computed(() => props.resource.resource_type === 'video');

const previewImageUrl = computed(() => {
    if (props.resource.thumbnail) {
        return props.resource.thumbnail;
    }

    const items = props.resource.items || [];
    const firstImg = items.find((i: any) => i.format === 'image');

    if (firstImg && firstImg.url) {
        return firstImg.url;
    }

    return null;
});

const pagesLabel = computed(() => {
    const items = props.resource.items || [];
    const hasPdf = items.some(
        (i: any) =>
            i.format === 'pdf' ||
            (i.url && i.url.toLowerCase().includes('.pdf')) ||
            (i.source && i.source.toLowerCase().includes('.pdf')),
    );

    if (hasPdf || props.resource.resource_type === 'other') {
        return 'PDF Document';
    }

    const count = props.resource.items_count || (items.length ?? 1);

    if (isVideo.value) {
        return `${count} ${count === 1 ? 'class' : 'classes'}`;
    }

    return `${count} ${count === 1 ? 'page' : 'pages'}`;
});

const typeBadge = computed(() => {
    switch (props.resource.resource_type) {
        case 'video':
            return {
                icon: Video,
                text: 'ভিডিও লেকচার',
                cls: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400',
            };
        case 'marked_book':
            return {
                icon: Book,
                text: 'দাগানো বই',
                cls: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400',
            };
        case 'practical':
            return {
                icon: FlaskConical,
                text: 'ব্যবহারিক',
                cls: 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400',
            };
        case 'other':
            return {
                icon: Book,
                text: 'অন্যান্য',
                cls: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300',
            };
        default:
            return {
                icon: FileText,
                text: 'হ্যান্ডনোট',
                cls: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400',
            };
    }
});

const copied = ref(false);
const isSharing = ref(false);

const shareResource = async () => {
    if (isSharing.value) {
        return;
    }

    isSharing.value = true;

    const originalUrl = `${window.location.origin}/resources/${props.resource.id}`;
    let finalUrl = originalUrl;

    try {
        const res = await fetch('/api/short-urls', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                original_url: originalUrl,
            }),
        });

        if (res.ok) {
            const data = await res.json();

            if (data.short_url) {
                finalUrl = data.short_url;
            }
        }
    } catch {
        // Fallback
    } finally {
        isSharing.value = false;
    }

    if (navigator.share) {
        try {
            await navigator.share({
                title: props.resource.title,
                url: finalUrl,
            });
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2500);

            return;
        } catch {
            // fallback
        }
    }

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(finalUrl);
        } else {
            throw new Error('Clipboard API unavailable');
        }

        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = finalUrl;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2500);
        } catch {
            // silent
        }
    }
};
</script>

<template>
    <article
        class="group relative cursor-pointer rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs transition-all duration-150 hover:border-slate-300 hover:shadow-xs sm:p-5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
        @click="router.visit(`/resources/${resource.id}`)"
    >
        <!-- 1. Header: Author Meta (Left) & Resource Type/Subject (Right) -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5" @click.stop>
                <!-- Avatar -->
                <Link
                    v-if="resource.user"
                    :href="`/u/${resource.user.username}`"
                    class="relative shrink-0"
                >
                    <img
                        v-if="
                            resource.user.image_url || resource.user.image_path
                        "
                        :src="
                            resource.user.image_url ||
                            '/storage/' + resource.user.image_path
                        "
                        :alt="resource.user.name"
                        class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-800"
                    />
                    <div
                        v-else
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                    >
                        {{ resource.user.name?.charAt(0) || 'U' }}
                    </div>
                </Link>
                <div
                    v-else
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                >
                    ?
                </div>

                <!-- Name & Time ago -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <Link
                            v-if="resource.user"
                            :href="`/u/${resource.user.username}`"
                            class="truncate text-sm font-bold text-slate-900 transition hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                        >
                            {{ resource.user.name }}
                        </Link>
                        <span
                            v-else
                            class="text-sm font-bold text-slate-700 dark:text-gray-300"
                        >
                            Community
                        </span>

                        <VerifiedBadge
                            v-if="resource.user?.is_verified"
                            size="sm"
                        />

                        <span
                            v-if="
                                resource.user &&
                                (!currentUser ||
                                    currentUser.id !== resource.user.id)
                            "
                            class="text-slate-300 dark:text-gray-700"
                            >•</span
                        >

                        <!-- Text-only Appreciate Button (no border) -->
                        <button
                            v-if="
                                resource.user &&
                                (!currentUser ||
                                    currentUser.id !== resource.user.id)
                            "
                            type="button"
                            @click.stop.prevent="toggleAppreciation"
                            :disabled="isTogglingAppreciation"
                            class="cursor-pointer text-xs font-semibold transition-colors duration-150 select-none hover:underline disabled:opacity-50"
                            :class="[
                                isAppreciated(
                                    resource.user.id,
                                    resource.user.is_appreciated,
                                )
                                    ? 'font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300'
                                    : 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300',
                            ]"
                            :title="
                                isAppreciated(
                                    resource.user.id,
                                    resource.user.is_appreciated,
                                )
                                    ? 'প্রশংসা অপসারণ করুন'
                                    : 'সদস্যকে প্রশংসা করুন'
                            "
                        >
                            {{
                                isAppreciated(
                                    resource.user.id,
                                    resource.user.is_appreciated,
                                )
                                    ? 'Appreciating'
                                    : 'Appreciate'
                            }}
                        </button>
                    </div>

                    <div class="text-[11px] text-slate-400 dark:text-gray-500">
                        {{ formatTimeAgo(resource.created_at) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Body: Resource Title, Description & Preview -->
        <div class="mt-3">
            <!-- Resource Type, Chapter & Subject Hierarchy Subtitle above the title -->
            <div
                class="mb-1.5 flex items-center gap-1.5 truncate text-xs font-medium text-slate-500 dark:text-gray-400"
            >
                <span
                    class="shrink-0 font-medium text-indigo-600 dark:text-indigo-400"
                    >{{ typeBadge.text }}</span
                >
                <template v-if="resource.node">
                    <span class="text-slate-300 dark:text-gray-600">•</span>
                    <span
                        class="truncate font-medium text-slate-600 dark:text-gray-300"
                        >{{ resource.node.name }}</span
                    >
                </template>
                <template v-if="resource.node?.subject">
                    <span class="text-slate-300 dark:text-gray-600">•</span>
                    <Link
                        :href="`/notes?subject=${resource.node.subject.id}`"
                        @click.stop
                        class="shrink-0 transition hover:text-indigo-600 dark:hover:text-indigo-400"
                    >
                        {{ resource.node.subject.name }}
                    </Link>
                </template>
            </div>

            <h2
                class="text-base leading-snug font-bold text-slate-900 transition group-hover:text-indigo-600 sm:text-lg dark:text-gray-100 dark:group-hover:text-indigo-400"
            >
                {{ resource.title }}
            </h2>

            <p
                v-if="resource.description"
                class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-gray-300"
            >
                {{ resource.description }}
            </p>

            <!-- Image/Video Preview -->
            <div
                v-if="previewImageUrl"
                class="relative mt-3 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-100 dark:border-gray-800 dark:bg-gray-800/50"
                :class="[isVideo ? 'aspect-video w-full' : 'max-h-96 w-full']"
            >
                <img
                    :src="previewImageUrl"
                    :alt="resource.title"
                    loading="lazy"
                    class="w-full transition duration-300 group-hover:scale-101"
                    :class="[
                        isVideo
                            ? 'h-full object-cover'
                            : 'max-h-96 bg-slate-50 object-contain dark:bg-gray-900',
                    ]"
                />

                <!-- Centered Play Button Overlay for Videos -->
                <div
                    v-if="isVideo"
                    class="absolute inset-0 flex items-center justify-center bg-black/20 backdrop-blur-[1px] transition group-hover:bg-black/30"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-600/90 text-white shadow-lg transition-transform duration-200 group-hover:scale-110 sm:h-14 sm:w-14"
                    >
                        <Play
                            class="h-6 w-6 translate-x-0.5 fill-white text-white sm:h-7 sm:w-7"
                        />
                    </div>
                </div>

                <!-- Clean subtle floating badge for page/class count -->
                <div
                    class="absolute right-2.5 bottom-2.5 flex items-center gap-1.5 rounded-lg bg-black/75 px-2.5 py-1 text-xs font-bold text-white shadow-sm backdrop-blur-xs"
                >
                    <Play
                        v-if="isVideo"
                        class="h-3 w-3 fill-current text-rose-400"
                    />
                    <FileText v-else class="h-3 w-3 text-emerald-400" />
                    <span>{{ pagesLabel }}</span>
                </div>
            </div>

            <!-- Alternative Fallback Card if no image preview -->
            <div
                v-else
                class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200/70 bg-slate-50/60 p-2.5 text-xs text-slate-600 transition group-hover:border-slate-300 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 ring-1 ring-indigo-500/10 dark:bg-indigo-950/50 dark:text-indigo-400 dark:ring-indigo-400/20"
                >
                    <component :is="typeBadge.icon" class="h-4 w-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800 dark:text-gray-200">
                        {{ typeBadge.text }}
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        {{ pagesLabel }}
                    </p>
                </div>
            </div>
        </div>

        <!-- 3. Footer Action Bar -->
        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-2.5 border-t border-slate-100 pt-3 dark:border-gray-800/80"
        >
            <div class="flex flex-wrap items-center gap-2" @click.stop>
                <ResourceVoteButtons
                    :resource-id="resource.id"
                    :initial-score="resource.vote_score || 0"
                    :initial-upvotes="resource.upvotes_count || 0"
                    :initial-downvotes="resource.downvotes_count || 0"
                    :initial-user-vote="resource.user_vote"
                    direction="horizontal"
                    size="sm"
                />

                <!-- Comment Count Button (Exactly matching Forum & Blog) -->
                <Link
                    :href="`/resources/${resource.id}#comments`"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <MessageSquare
                        class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400"
                    />
                    <span>{{ resource.comments_count ?? 0 }} comments</span>
                </Link>
            </div>

            <!-- Right: Clean Share Button (Exactly matching Forum & Blog) -->
            <div class="flex items-center gap-2" @click.stop>
                <button
                    type="button"
                    @click="shareResource"
                    :disabled="isSharing"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 disabled:cursor-wait disabled:opacity-75 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="Share resource"
                >
                    <Loader2
                        v-if="isSharing"
                        class="h-3.5 w-3.5 animate-spin text-indigo-600 dark:text-indigo-400"
                    />
                    <Check
                        v-else-if="copied"
                        class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                    />
                    <Share2
                        v-else
                        class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                    />
                    <span>{{
                        isSharing ? 'Copying...' : copied ? 'Copied' : 'Share'
                    }}</span>
                </button>
            </div>
        </div>
    </article>
</template>
