<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowBigUp, User as UserIcon, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import NoteReader from '@/components/resources/NoteReader.vue';
import PdfViewer from '@/components/resources/PdfViewer.vue';
import ResourceVoteButtons from '@/components/resources/ResourceVoteButtons.vue';
import VideoSeriesPlayer from '@/components/resources/VideoSeriesPlayer.vue';

interface ResourceItem {
    id: number;
    resource_id: number;
    format: 'image' | 'video' | 'pdf' | string;
    title?: string | null;
    source: string;
    url: string;
}

interface ResourceData {
    id: number;
    node_id: number;
    user_id?: number | null;
    resource_type: string;
    title: string;
    description?: string | null;
    created_at?: string;
    updated_at?: string;
    vote_score?: number;
    upvotes_count?: number;
    downvotes_count?: number;
    items?: ResourceItem[];
    user?: {
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        image_url?: string | null;
    } | null;
}

interface Upvoter {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
}

const props = defineProps<{
    resource: ResourceData;
    subject?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    breadcrumb?: Array<{ name: string; slug: string }>;
    userVote?: number | null;
    upvoters?: Upvoter[];
}>();

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);

// Upvoters Modal State
const showUpvotersModal = ref(false);
const localUpvoters = ref<Upvoter[]>([...(props.upvoters || [])]);

watch(
    () => props.upvoters,
    (val) => {
        localUpvoters.value = [...(val || [])];
    },
);

const handleVoted = ({
    userVote,
}: {
    resourceId: number;
    userVote: number | null;
    voteScore: number;
    upvotes: number;
    downvotes: number;
}) => {
    if (!currentUser.value) {
        return;
    }

    const u = currentUser.value;

    if (userVote === 1) {
        if (!localUpvoters.value.some((x) => x.id === u.id)) {
            localUpvoters.value.unshift({
                id: u.id,
                name: u.name,
                username: u.username,
                image_path: u.image_path,
                image_url: u.image_url,
                institution: u.institution,
                is_verified: u.is_verified,
            });
        }
    } else {
        localUpvoters.value = localUpvoters.value.filter((x) => x.id !== u.id);
    }
};

// Determine which renderer to mount based on items format
const primaryFormat = computed<'image' | 'video' | 'pdf'>(() => {
    const firstItem = props.resource?.items?.[0];

    if (firstItem?.format === 'video') {
        return 'video';
    }

    if (firstItem?.format === 'pdf') {
        return 'pdf';
    }

    if (firstItem?.format === 'image') {
        return 'image';
    }

    // Fallback based on resource category
    if (props.resource?.resource_type === 'video') {
        return 'video';
    }

    return 'image';
});

// Chapter link for direct return
const chapterUrl = computed(() => {
    const subjectSlug = props.subject?.slug ? `/${props.subject.slug}` : '';

    if (!props.breadcrumb || props.breadcrumb.length === 0) {
        return subjectSlug || '/';
    }

    const path = props.breadcrumb.map((c) => c.slug).join('/');

    return `${subjectSlug}/${path}`;
});
</script>

<template>
    <Head>
        <title>{{ resource.title }}</title>
        <meta
            name="description"
            :content="resource.description || `${resource.title} - HSCStack`"
        />
        <meta property="og:title" :content="`${resource.title} - HSCStack`" />
        <meta
            property="og:description"
            :content="resource.description || `${resource.title} - HSCStack`"
        />
    </Head>

    <div class="mx-auto max-w-5xl px-3 pt-6 pb-24 sm:px-6">
        <!-- Breadcrumb Navigation -->
        <header class="mb-6">
            <BreadcrumbNav
                :breadcrumb="breadcrumb"
                :subject="subject"
                class="mb-3"
            />

            <!-- Title & Back Button -->
            <div class="flex items-center gap-3">
                <!-- Clear Back Button -->
                <Link
                    :href="chapterUrl"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-500 shadow-xs transition hover:border-indigo-200 hover:bg-indigo-50/50 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    title="অধ্যায়ে ফিরে যান"
                >
                    <ArrowLeft class="h-4 w-4 stroke-[2.3]" />
                </Link>

                <h1
                    class="truncate text-xl font-black tracking-tight text-slate-900 sm:text-2xl md:text-3xl dark:text-gray-100"
                >
                    {{ resource.title }}
                </h1>
            </div>

            <!-- Author Info -->
            <div
                v-if="resource.user"
                class="mt-3 flex items-center gap-2 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
            >
                <span class="font-medium text-slate-400 dark:text-gray-500"
                    >By</span
                >
                <Link
                    :href="`/users/${resource.user.username}`"
                    class="inline-flex items-center gap-2 font-bold text-slate-800 transition hover:text-indigo-600 dark:text-gray-200 dark:hover:text-indigo-400"
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
                        class="h-5 w-5 rounded-full border border-slate-200 object-cover dark:border-gray-700"
                    />
                    <div
                        v-else
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800"
                    >
                        <UserIcon class="h-3 w-3" />
                    </div>
                    <span>{{ resource.user.name }}</span>
                </Link>
            </div>

            <!-- Vote Buttons & Upvoters Stack (Next Line) -->
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <ResourceVoteButtons
                    :resource-id="resource.id"
                    :initial-upvotes="resource.upvotes_count || 0"
                    :initial-downvotes="resource.downvotes_count || 0"
                    :initial-score="resource.vote_score || 0"
                    :initial-user-vote="userVote"
                    direction="horizontal"
                    size="md"
                    @voted="handleVoted"
                />

                <!-- Upvoters Avatar Stack & Modal Trigger (Forum Style) -->
                <button
                    v-if="localUpvoters.length > 0"
                    type="button"
                    @click="showUpvotersModal = true"
                    class="group flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                    title="আপভোটকারী শিক্ষার্থীদের তালিকা দেখুন"
                >
                    <div class="flex -space-x-1.5 overflow-hidden">
                        <div
                            v-for="upvoter in localUpvoters.slice(0, 3)"
                            :key="upvoter.id"
                            class="inline-block h-5 w-5 overflow-hidden rounded-full ring-2 ring-white dark:ring-gray-900"
                        >
                            <img
                                v-if="upvoter.image_url || upvoter.image_path"
                                :src="
                                    upvoter.image_url ||
                                    '/storage/' + upvoter.image_path
                                "
                                :alt="upvoter.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-indigo-100 text-[9px] font-bold text-indigo-700 uppercase dark:bg-indigo-950 dark:text-indigo-300"
                            >
                                {{ upvoter.name?.charAt(0) || 'U' }}
                            </div>
                        </div>
                    </div>

                    <span
                        class="font-medium text-slate-700 underline-offset-2 group-hover:underline dark:text-gray-300"
                    >
                        {{ localUpvoters.length }} জন আপভোট করেছেন
                    </span>
                </button>
            </div>

            <!-- Author's Note / Description Box -->
            <div
                v-if="resource.description"
                class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 text-sm text-slate-700 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-300"
            >
                <p class="leading-relaxed whitespace-pre-line">
                    {{ resource.description }}
                </p>
            </div>
        </header>

        <!-- Dynamic Content Renderer Engine -->
        <main>
            <!-- 1. Video Series Player -->
            <VideoSeriesPlayer
                v-if="primaryFormat === 'video'"
                :items="resource.items || []"
            />

            <!-- 2. PDF Document Viewer -->
            <PdfViewer
                v-else-if="primaryFormat === 'pdf'"
                :items="resource.items || []"
            />

            <!-- 3. Multi-Image Note Reader -->
            <NoteReader v-else :items="resource.items || []" />
        </main>
    </div>

    <!-- Upvoters Modal (Forum Style) -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showUpvotersModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showUpvotersModal = false"
            >
                <div
                    class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        type="button"
                        @click="showUpvotersModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-4 flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <ArrowBigUp
                                class="h-5 w-5 fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                আপভোটকারী শিক্ষার্থী
                            </h3>
                            <p
                                class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                            >
                                সাম্প্রতিক ২০ জন আপভোটকারী
                            </p>
                        </div>
                    </div>

                    <div
                        class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                    >
                        <div
                            v-if="localUpvoters.length === 0"
                            class="py-6 text-center text-xs text-slate-500 dark:text-gray-400"
                        >
                            এখনো কেউ আপভোট করেননি।
                        </div>

                        <Link
                            v-for="upvoter in localUpvoters"
                            :key="upvoter.id"
                            :href="`/users/${upvoter.username}`"
                            class="flex items-center gap-3 rounded-xl px-2 py-2.5 transition hover:bg-slate-50 dark:hover:bg-gray-800/50"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                            >
                                <img
                                    v-if="
                                        upvoter.image_url || upvoter.image_path
                                    "
                                    :src="
                                        upvoter.image_url ||
                                        '/storage/' + upvoter.image_path
                                    "
                                    :alt="upvoter.name"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="text-xs font-bold uppercase"
                                >
                                    {{ upvoter.name?.charAt(0) || 'U' }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    {{ upvoter.name }}
                                </p>
                                <p
                                    v-if="upvoter.institution"
                                    class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    {{ upvoter.institution }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
