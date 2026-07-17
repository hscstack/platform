<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3';
import { Calendar, User, Eye, ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    blog: Object,
});

const formattedDate = computed(() => {
    if (!props.blog.created_at) {
return '';
}

    const date = new Date(props.blog.created_at);

    if (isNaN(date.getTime())) {
return '';
}

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
});

const goBack = () => {
    window.history.back();
};
const copied = ref(false);

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        copied.value = true;

        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (e) {
        console.error(e);
    }
};
</script>

<template>
    <Head>
        <title>{{ blog.meta_title || blog.title }}</title>

        <meta
            name="description"
            :content="blog.meta_description || blog.excerpt"
        />
    </Head>

    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <button
                @click="goBack"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-600"
            >
                <ArrowLeft class="h-4 w-4" />
                <span>Back to journal</span>
            </button>
        </div>

        <article>
            <div
                class="mb-6 flex flex-wrap items-center gap-4 text-xs sm:text-sm"
            >
                <div class="flex items-center gap-1.5 text-slate-500">
                    <User class="h-4 w-4 text-slate-400" />
                    <span>By</span>
                    <Link
                        :href="`/about-us#author-${blog.user?.id}`"
                        class="font-medium text-indigo-600 transition-colors hover:text-indigo-800 hover:underline"
                    >
                        {{ blog.user?.name }}
                    </Link>
                </div>

                <div
                    v-if="formattedDate"
                    class="flex items-center gap-1.5 text-slate-500"
                >
                    <Calendar class="h-4 w-4 text-slate-400" />
                    <time :datetime="blog.created_at" class="text-slate-500">{{
                        formattedDate
                    }}</time>
                </div>

                <div class="flex items-center gap-1.5 text-slate-500">
                    <Eye class="h-4 w-4 text-slate-400" />
                    <span>{{ blog.views }} views</span>
                </div>
            </div>

            <h1
                class="text-3xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-4xl md:text-5xl"
            >
                {{ blog.title }}
            </h1>

            <div
                class="my-8 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 shadow-sm"
            >
                <img
                    :src="
                        blog.featured_image || 'https://placehold.co/1200x630'
                    "
                    :alt="blog.title"
                    class="aspect-[2/1] w-full object-cover"
                />
            </div>

            <div class="blog-content max-w-none" v-html="blog.content"></div>
        </article>

        <!-- "Write for us" CTA Banner -->
        <section
            class="mt-12 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 sm:flex sm:items-center sm:justify-between"
        >
            <div class="mb-4 sm:mb-0">
                <h3 class="text-lg font-bold text-slate-900">
                    Want to write a blog on this site?
                </h3>
                <p class="mt-1 text-sm text-slate-600">
                    Share your thoughts, stories, and expertise with our
                    community.
                </p>
            </div>
            <Link
                href="/join"
                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                Join us
            </Link>
        </section>

        <footer class="mt-12 border-t border-slate-200 pt-8">
            <div
                class="flex flex-col gap-6 rounded-2xl bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-slate-500">Written by</p>

                    <Link
                        :href="`/about-us#author-${blog.user?.id}`"
                        class="mt-1 block text-lg font-semibold text-slate-900 transition hover:text-indigo-600"
                    >
                        {{ blog.user?.name }}
                    </Link>

                    <Link
                        :href="`/blogs?q=${encodeURIComponent(blog.user?.name || '')}`"
                        class="mt-2 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline"
                    >
                        View more articles by {{ blog.user?.name }} →
                    </Link>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="copyLink"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600"
                    >
                        {{ copied ? 'Copied!' : 'Copy link' }}
                    </button>
                </div>
            </div>
        </footer>
    </main>
</template>
<style scoped>
:deep(.blog-content h1),
:deep(.blog-content h2),
:deep(.blog-content h3),
:deep(.blog-content h4),
:deep(.blog-content h5),
:deep(.blog-content h6),
:deep(.blog-content p),
:deep(.blog-content ul),
:deep(.blog-content ol),
:deep(.blog-content blockquote),
:deep(.blog-content a) {
    all: revert;
}
</style>
