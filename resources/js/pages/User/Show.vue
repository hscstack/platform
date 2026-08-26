<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    GraduationCap,
    Edit3,
    Facebook,
    Instagram,
    Github,
    BadgeCheck,
    ArrowRight,
    Heart,
    MessageSquare,
    Eye,
    CheckCircle2,
    BookOpen,
    FileText,
    UploadCloud,
    Activity,
    ArrowUpRight,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    profileUser: {
        id: number;
        name: string;
        username: string;
        title: string | null;
        about: string | null;
        institution: string | null;
        image_url: string | null;
        facebook: string | null;
        instagram: string | null;
        github: string | null;
        created_at: string;
        roles: string[];
        is_staff: boolean;
    };
    stats: {
        completedResourcesCount: number;
        blogsCount: number;
        totalBlogLikes: number;
        totalBlogViews: number;
        sharedResourcesCount: number;
    };
    recentCompletions: Array<{
        id: number;
        title: string;
        resource_type: string;
        node?: {
            id: number;
            name: string;
            subject?: {
                id: number;
                name: string;
            };
        };
    }>;
    blogs: Array<{
        id: number;
        title: string;
        slug: string;
        excerpt: string | null;
        featured_image: string | null;
        views: number;
        reactions_count: number;
        comments_count: number;
        created_at: string;
    }>;
    recentActivities: {
        uploads: Array<{
            type: string;
            title: string;
            subtitle?: string;
            resource_type?: string;
            url: string | null;
            created_at: string;
        }>;
        completions: Array<{
            type: string;
            title: string;
            subtitle?: string;
            url: string | null;
            created_at: string;
        }>;
        reactions: Array<{
            type: string;
            title: string;
            url: string | null;
            created_at: string;
        }>;
        comments: Array<{
            type: string;
            title: string;
            content?: string;
            url: string | null;
            created_at: string;
        }>;
    };
    suggestedUsers?: Array<{
        id: number;
        name: string;
        username: string;
        title: string | null;
        institution: string | null;
        image_url: string | null;
        image_path: string | null;
        about: string | null;
        roles?: Array<{ id: number; name: string }>;
    }>;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isOwnProfile = computed(
    () => currentUser.value?.id === props.profileUser.id,
);

const activeTab = ref<'completed' | 'blogs' | 'activity'>(
    props.stats.completedResourcesCount > 0
        ? 'completed'
        : props.blogs.length > 0
          ? 'blogs'
          : 'completed',
);

const getRoleBadge = (roles: string[]) => {
    if (roles.includes('admin')) {
        return {
            label: 'Admin',
            class: 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60',
        };
    }

    if (roles.includes('editor')) {
        return {
            label: 'Editor',
            class: 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/60',
        };
    }

    if (roles.includes('manager')) {
        return {
            label: 'Staff',
            class: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
        };
    }

    return {
        label: 'Student',
        class: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/60',
    };
};

const roleInfo = computed(() => getRoleBadge(props.profileUser.roles));

const totalActivitiesCount = computed(
    () =>
        (props.recentActivities.uploads?.length || 0) +
        props.recentActivities.reactions.length +
        props.recentActivities.comments.length,
);
</script>

<template>
    <Head :title="`${profileUser.name} (@${profileUser.username})`">
        <meta
            name="description"
            :content="
                [profileUser.title, profileUser.institution, profileUser.about]
                    .filter(Boolean)
                    .slice(0, 2)
                    .join(' · ') ||
                `View ${profileUser.name}'s profile, completed study topics, and contributions on HSCStack.`
            "
        />
        <meta
            property="og:title"
            :content="`${profileUser.name} (@${profileUser.username})`"
        />
        <meta
            property="og:description"
            :content="
                [profileUser.title, profileUser.institution, profileUser.about]
                    .filter(Boolean)
                    .slice(0, 2)
                    .join(' · ') ||
                `View ${profileUser.name}'s profile, completed study topics, and contributions on HSCStack.`
            "
        />
        <meta property="og:type" content="profile" />
        <meta
            v-if="profileUser.image_url"
            property="og:image"
            :content="profileUser.image_url"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            :content="`${profileUser.name} (@${profileUser.username})`"
        />
        <meta
            name="twitter:description"
            :content="
                [profileUser.title, profileUser.institution, profileUser.about]
                    .filter(Boolean)
                    .slice(0, 2)
                    .join(' · ') ||
                `View ${profileUser.name}'s profile, completed study topics, and contributions on HSCStack.`
            "
        />
        <meta
            v-if="profileUser.image_url"
            name="twitter:image"
            :content="profileUser.image_url"
        />
    </Head>

    <div
        class="min-h-screen bg-slate-50/60 px-3.5 py-5 sm:px-6 sm:py-8 lg:px-8 dark:bg-gray-950"
    >
        <div class="mx-auto max-w-3xl space-y-4.5 sm:space-y-6">
            <!-- Profile Identity Card -->
            <div
                class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-xs sm:rounded-3xl sm:p-7 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-5"
                >
                    <!-- Left: Avatar & Bio Details -->
                    <div class="flex min-w-0 items-start gap-3.5 sm:gap-4.5">
                        <!-- Avatar -->
                        <div class="relative shrink-0">
                            <div
                                class="h-16 w-16 overflow-hidden rounded-2xl shadow-sm ring-4 ring-slate-100 sm:h-20 sm:w-20 dark:ring-gray-800"
                            >
                                <img
                                    v-if="profileUser.image_url"
                                    :src="profileUser.image_url"
                                    :alt="profileUser.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-700 text-xl font-black text-white sm:text-2xl"
                                >
                                    {{
                                        profileUser.name.charAt(0).toUpperCase()
                                    }}
                                </div>
                            </div>
                        </div>

                        <!-- Name, Verified Badge, Username & Institution -->
                        <div class="min-w-0 flex-1 space-y-1">
                            <div
                                class="flex flex-wrap items-center gap-1.5 sm:gap-2"
                            >
                                <h1
                                    class="truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                                >
                                    {{ profileUser.name }}
                                </h1>

                                <!-- Verified Staff Badge -->
                                <span
                                    v-if="profileUser.is_staff"
                                    class="inline-flex items-center text-blue-600 dark:text-blue-400"
                                    title="Verified HSCStack Staff / Contributor"
                                >
                                    <BadgeCheck
                                        class="h-4.5 w-4.5 fill-blue-50 stroke-[2.2] sm:h-5 sm:w-5 dark:fill-blue-950/60"
                                    />
                                </span>

                                <!-- Role Pill -->
                                <span
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                    :class="roleInfo.class"
                                >
                                    {{ roleInfo.label }}
                                </span>
                            </div>

                            <!-- Username Tag -->
                            <p
                                class="text-xs font-semibold text-slate-400 dark:text-gray-500"
                            >
                                @{{ profileUser.username }}
                            </p>

                            <!-- Academic / Institution Info -->
                            <div
                                v-if="profileUser.institution"
                                class="flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-gray-300"
                            >
                                <GraduationCap
                                    class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
                                />
                                <span class="truncate">{{
                                    profileUser.institution
                                }}</span>
                            </div>

                            <!-- Tagline / Title -->
                            <p
                                v-if="profileUser.title"
                                class="truncate text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                            >
                                {{ profileUser.title }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Actions: Edit (Only for Profile Owner) -->
                    <div
                        v-if="isOwnProfile"
                        class="flex shrink-0 items-center self-start pt-1 sm:self-auto sm:pt-0"
                    >
                        <Link
                            href="/profile"
                            class="inline-flex h-8 items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 text-xs font-semibold text-white shadow-xs transition hover:bg-slate-800 active:scale-95 sm:h-8.5 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            <Edit3 class="h-3.5 w-3.5" />
                            <span>Edit</span>
                        </Link>
                    </div>
                </div>

                <!-- Bio / About -->
                <div
                    v-if="profileUser.about"
                    class="mt-4 rounded-xl border border-slate-100 bg-slate-50/60 p-3 text-xs leading-relaxed text-slate-600 sm:rounded-2xl sm:p-3.5 dark:border-gray-800/80 dark:bg-gray-800/30 dark:text-gray-300"
                >
                    <p class="whitespace-pre-line">{{ profileUser.about }}</p>
                </div>

                <!-- Social Links & Joined Date Footer -->
                <div
                    class="mt-4.5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5 dark:border-gray-800"
                >
                    <!-- Social Links -->
                    <div class="flex items-center gap-2">
                        <a
                            v-if="profileUser.facebook"
                            :href="profileUser.facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-blue-400"
                            title="Facebook Profile"
                        >
                            <Facebook class="h-3.5 w-3.5" />
                        </a>
                        <a
                            v-if="profileUser.instagram"
                            :href="profileUser.instagram"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-pink-200 hover:bg-pink-50 hover:text-pink-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-pink-400"
                            title="Instagram Profile"
                        >
                            <Instagram class="h-3.5 w-3.5" />
                        </a>
                        <a
                            v-if="profileUser.github"
                            :href="profileUser.github"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-400 hover:bg-slate-100 hover:text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-100"
                            title="GitHub Profile"
                        >
                            <Github class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <!-- Member Joined Date -->
                    <div
                        class="flex items-center gap-1.5 text-xs font-medium text-slate-400 dark:text-gray-500"
                    >
                        <Calendar class="h-3.5 w-3.5" />
                        <span>Joined {{ profileUser.created_at }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Metrics Row (Optimized for Mobile) -->
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3">
                <!-- Topics Completed -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.completedResourcesCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Topics Done
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Articles Published -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <FileText class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.blogsCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Articles
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Likes Received -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                        >
                            <Heart class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.totalBlogLikes }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Likes Gained
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shared Resources -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7.5 w-7.5 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                        >
                            <UploadCloud class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-black text-slate-900 dark:text-gray-100"
                            >
                                {{ stats.sharedResourcesCount }}
                            </p>
                            <p
                                class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Shared Notes
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Content Section (No Counts in Brackets) -->
            <div class="space-y-3.5">
                <!-- Navigation Tabs Bar -->
                <div
                    class="flex items-center gap-1 rounded-xl border border-slate-200/80 bg-white p-1 shadow-xs sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="activeTab = 'completed'"
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                        :class="
                            activeTab === 'completed'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <CheckCircle2 class="h-3.5 w-3.5 shrink-0" />
                        <span>Completed</span>
                    </button>

                    <button
                        v-if="blogs.length > 0"
                        @click="activeTab = 'blogs'"
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                        :class="
                            activeTab === 'blogs'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <FileText class="h-3.5 w-3.5 shrink-0" />
                        <span>Articles</span>
                    </button>

                    <button
                        @click="activeTab = 'activity'"
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold transition sm:rounded-xl sm:px-3"
                        :class="
                            activeTab === 'activity'
                                ? 'bg-slate-900 text-white shadow-xs dark:bg-gray-100 dark:text-gray-900'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <Activity class="h-3.5 w-3.5 shrink-0" />
                        <span>Activity</span>
                    </button>
                </div>

                <!-- Tab 1: Completed Topics -->
                <div v-if="activeTab === 'completed'">
                    <div
                        v-if="recentCompletions.length === 0"
                        class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 sm:h-10 sm:w-10 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <BookOpen class="h-5 w-5 stroke-[1.8]" />
                        </div>
                        <h3
                            class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            No study topics completed yet
                        </h3>
                        <p
                            class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            Topics marked as done while studying chapters will
                            show up here.
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Link
                            v-for="resource in recentCompletions"
                            :key="resource.id"
                            :href="`/resources/${resource.id}`"
                            class="group flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-xs transition hover:border-indigo-300 hover:bg-slate-50/80 sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/50 dark:hover:bg-gray-800/60"
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-400"
                                >
                                    <CheckCircle2
                                        class="h-3.5 w-3.5 stroke-[2.2]"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-xs font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                                    >
                                        {{ resource.title }}
                                    </p>
                                    <p
                                        v-if="resource.node?.subject?.name"
                                        class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        {{ resource.node.subject.name }} ·
                                        {{ resource.node.name }}
                                    </p>
                                </div>
                            </div>
                            <ArrowUpRight
                                class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                            />
                        </Link>
                    </div>
                </div>

                <!-- Tab 2: Compact Authored Articles -->
                <div v-else-if="activeTab === 'blogs'">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between px-1">
                            <span
                                class="text-xs font-bold text-slate-700 dark:text-gray-300"
                            >
                                Published Guides & Notes
                            </span>
                            <Link
                                :href="`/blogs?q=${encodeURIComponent(profileUser.name)}`"
                                class="group inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400"
                            >
                                <span>See all blogs</span>
                                <ArrowRight
                                    class="h-3 w-3 transition-transform group-hover:translate-x-0.5"
                                />
                            </Link>
                        </div>

                        <div class="space-y-2">
                            <Link
                                v-for="blog in blogs"
                                :key="blog.id"
                                :href="`/blogs/${blog.slug}`"
                                class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-xs transition hover:border-indigo-300 hover:shadow-xs sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/50"
                            >
                                <!-- Compact Thumbnail -->
                                <div
                                    class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100 sm:h-16 sm:w-24 sm:rounded-xl dark:bg-gray-800"
                                >
                                    <img
                                        :src="
                                            blog.featured_image ||
                                            'https://placehold.co/400x250'
                                        "
                                        :alt="blog.title"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                </div>

                                <!-- Info -->
                                <div
                                    class="min-w-0 flex-1 space-y-0.5 sm:space-y-1"
                                >
                                    <h3
                                        class="line-clamp-1 text-xs font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                                    >
                                        {{ blog.title }}
                                    </h3>
                                    <p
                                        v-if="blog.excerpt"
                                        class="line-clamp-1 text-[11px] text-slate-500 dark:text-gray-400"
                                    >
                                        {{ blog.excerpt }}
                                    </p>
                                    <div
                                        class="flex items-center gap-2.5 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        <span class="flex items-center gap-1">
                                            <Eye class="h-3 w-3" />
                                            {{ blog.views }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Heart class="h-3 w-3" />
                                            {{ blog.reactions_count }}
                                        </span>
                                    </div>
                                </div>

                                <ArrowUpRight
                                    class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                                />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Recent Activity -->
                <div v-else-if="activeTab === 'activity'">
                    <div
                        v-if="totalActivitiesCount === 0"
                        class="rounded-2xl border border-dashed border-slate-200 bg-white p-7 text-center sm:rounded-3xl sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 sm:h-10 sm:w-10 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <Activity class="h-5 w-5 stroke-[1.8]" />
                        </div>
                        <h3
                            class="mt-2 text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            No recent activity yet
                        </h3>
                        <p
                            class="mx-auto mt-1 max-w-xs text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            Reactions and comments on study articles will show
                            up here.
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <!-- Uploads -->
                        <div
                            v-for="(item, idx) in recentActivities.uploads"
                            :key="'upload-' + idx"
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 shadow-xs sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <div
                                    class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                                >
                                    <UploadCloud class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1 truncate">
                                    <span
                                        class="text-xs text-slate-500 dark:text-gray-400"
                                        >Uploaded
                                    </span>
                                    <Link
                                        v-if="item.url"
                                        :href="item.url"
                                        class="text-xs font-bold text-slate-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                    >
                                        {{ item.title }}
                                    </Link>
                                    <span
                                        v-if="item.subtitle"
                                        class="ml-1 text-[10px] text-slate-400 dark:text-gray-500"
                                    >
                                        ({{ item.subtitle }})
                                    </span>
                                </div>
                            </div>
                            <span
                                class="shrink-0 pl-2 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                {{ item.created_at }}
                            </span>
                        </div>

                        <!-- Reactions -->
                        <div
                            v-for="(item, idx) in recentActivities.reactions"
                            :key="'react-' + idx"
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 shadow-xs sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <div
                                    class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-950/60 dark:text-rose-400"
                                >
                                    <Heart class="h-3.5 w-3.5 fill-rose-500" />
                                </div>
                                <div class="min-w-0 flex-1 truncate">
                                    <span
                                        class="text-xs text-slate-500 dark:text-gray-400"
                                        >Liked
                                    </span>
                                    <Link
                                        v-if="item.url"
                                        :href="item.url"
                                        class="text-xs font-bold text-slate-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                    >
                                        {{ item.title }}
                                    </Link>
                                </div>
                            </div>
                            <span
                                class="shrink-0 pl-2 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                {{ item.created_at }}
                            </span>
                        </div>

                        <!-- Comments -->
                        <div
                            v-for="(item, idx) in recentActivities.comments"
                            :key="'comm-' + idx"
                            class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-white p-2.5 shadow-xs sm:rounded-2xl sm:p-3 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex min-w-0 items-center gap-2">
                                    <div
                                        class="flex h-6.5 w-6.5 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                                    >
                                        <MessageSquare class="h-3.5 w-3.5" />
                                    </div>
                                    <div class="min-w-0 flex-1 truncate">
                                        <span
                                            class="text-xs text-slate-500 dark:text-gray-400"
                                            >Commented on
                                        </span>
                                        <Link
                                            v-if="item.url"
                                            :href="item.url"
                                            class="text-xs font-bold text-slate-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            {{ item.title }}
                                        </Link>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 pl-2 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    {{ item.created_at }}
                                </span>
                            </div>
                            <p
                                v-if="item.content"
                                class="line-clamp-1 pl-8.5 text-xs text-slate-600 italic dark:text-gray-300"
                            >
                                "{{ item.content }}"
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suggested / Discover Other Learners & Contributors -->
            <div
                v-if="suggestedUsers && suggestedUsers.length > 0"
                class="space-y-3 pt-2"
            >
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-gray-300">
                        <Users class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                        <span>Discover People</span>
                    </div>
                    <Link
                        href="/about-us"
                        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    >
                        Meet team →
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <Link
                        v-for="person in suggestedUsers"
                        :key="person.id"
                        :href="`/u/${person.username}`"
                        class="group flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs transition hover:border-indigo-300 hover:bg-slate-50/70 sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-900/60 dark:hover:bg-gray-800/50"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-indigo-50 shadow-2xs ring-2 ring-slate-100 sm:h-11 sm:w-11 dark:bg-indigo-950/60 dark:ring-gray-800"
                            >
                                <img
                                    v-if="person.image_url"
                                    :src="person.image_url"
                                    :alt="person.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-600 text-sm font-black text-white"
                                >
                                    {{ person.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <p
                                        class="truncate text-xs font-bold text-slate-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                                    >
                                        {{ person.name }}
                                    </p>
                                    <span
                                        v-if="person.roles && person.roles.length > 0"
                                        class="inline-flex items-center text-blue-600 dark:text-blue-400"
                                        title="Verified HSCStack Contributor"
                                    >
                                        <BadgeCheck
                                            class="h-3.5 w-3.5 fill-blue-50 stroke-[2.2] dark:fill-blue-950/60"
                                        />
                                    </span>
                                </div>
                                <p
                                    v-if="person.title || person.institution"
                                    class="truncate text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                >
                                    {{ person.title || person.institution }}
                                </p>
                                <p
                                    v-else
                                    class="truncate text-[10px] font-semibold text-slate-400 dark:text-gray-500"
                                >
                                    @{{ person.username }}
                                </p>
                            </div>
                        </div>

                        <ArrowUpRight
                            class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
