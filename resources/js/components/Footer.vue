<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Github,
    Facebook,
    BookOpen,
    Users,
    UserPlus,
    ExternalLink,
    Heart,
    Instagram,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const currentUrl = computed(() => page.url);

// Full marketing footer on primary landing/home views; sleek minimal footer elsewhere
const isFullFooter = computed(() => {
    const path = currentUrl.value.split('?')[0];

    return path === '/' || path === '/ssc';
});
</script>

<template>
    <!-- Desktop: Always Full Footer (md:block) | Mobile: Full Footer on Home/SSC (block md:hidden) -->
    <footer
        :class="[
            isFullFooter ? 'block' : 'hidden md:block',
            'mt-auto border-t border-slate-100 bg-white py-12 sm:py-16 dark:border-gray-800 dark:bg-gray-900',
        ]"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="grid grid-cols-1 gap-y-10 md:grid-cols-12 md:gap-x-12">
                <div
                    class="flex flex-col items-center text-center md:col-span-5 md:items-start md:text-left"
                >
                    <AppLogo />
                    <p
                        class="mt-4 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-gray-400"
                    >
                        Empowering the next generation of students with high
                        quality, direct, and accessible academic resources.
                    </p>

                    <div class="mt-4 flex items-center gap-4">
                        <a
                            href="https://github.com/hscstack"
                            rel="noopener noreferrer"
                            target="_blank"
                            class="text-slate-400 transition-colors duration-150 hover:text-slate-900 dark:text-gray-500 dark:hover:text-gray-100"
                            aria-label="GitHub Repository"
                        >
                            <Github class="h-5 w-5" />
                        </a>

                        <a
                            href="https://facebook.com/hscstackbd"
                            rel="noopener noreferrer"
                            target="_blank"
                            class="text-slate-400 transition-colors duration-150 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400"
                            aria-label="Facebook Page"
                        >
                            <Facebook class="h-5 w-5" />
                        </a>
                        <a
                            href="https://instagram.com/hscstack"
                            rel="noopener noreferrer"
                            target="_blank"
                            class="text-slate-400 transition-colors duration-150 hover:text-rose-600 dark:text-gray-500 dark:hover:text-rose-400"
                            aria-label="Instagram Profile"
                        >
                            <Instagram class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 gap-x-8 gap-y-8 border-t border-slate-100 pt-10 sm:gap-x-12 md:col-span-7 md:grid-cols-2 md:border-t-0 md:pt-0 dark:border-gray-800"
                >
                    <div class="text-center md:text-left">
                        <h4
                            class="text-xs font-bold tracking-widest text-slate-900 uppercase dark:text-gray-100"
                        >
                            Platform
                        </h4>
                        <ul class="mt-5 space-y-3.5">
                            <li>
                                <Link
                                    href="/guide"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <BookOpen
                                        class="h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                    />
                                    <span>Documentation</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/about-us"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <Users
                                        class="h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                    />
                                    <span>Our Contributors</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/join"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <UserPlus
                                        class="h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                    />
                                    <span>Join Our Team</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/support"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <Heart
                                        class="h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                    />
                                    <span>Support Us</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/projects"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <ExternalLink
                                        class="h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                    />
                                    <span>More From Us</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div class="text-center md:text-left">
                        <h4
                            class="text-xs font-bold tracking-widest text-slate-900 uppercase dark:text-gray-100"
                        >
                            Legal
                        </h4>
                        <ul class="mt-5 space-y-3.5">
                            <li>
                                <Link
                                    href="/privacy-policy"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    Privacy Policy
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/terms-service"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    Terms of Service
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/content-policy"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    Content Policy
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-8 sm:flex-row dark:border-gray-800"
            >
                <p
                    class="text-center text-xs font-medium text-slate-400 sm:text-left dark:text-gray-500"
                >
                    &copy; 2026 HSCStack. Built for the future of learning.
                </p>

                <div
                    v-if="$page.props.app_version"
                    class="flex items-center gap-2"
                >
                    <a
                        :href="`https://github.com/hscstack/platform/releases/tag/${$page.props.app_version}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-mono text-[11px] font-medium text-slate-500 transition-colors hover:border-slate-300 hover:text-slate-900 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-200"
                    >
                        {{ $page.props.app_version }}
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Minimal, Clean Mobile Footer on Non-Home Pages (md:hidden) -->
    <footer
        v-if="!isFullFooter"
        class="mt-auto border-t border-slate-200/70 bg-white/80 pt-8 pb-6 backdrop-blur-md md:hidden dark:border-gray-800/70 dark:bg-gray-950/80"
    >
        <div
            class="mx-auto flex max-w-7xl flex-col items-center gap-3.5 px-4 text-center"
        >
            <!-- App Logo with top breathing room -->
            <div class="pt-1">
                <AppLogo />
            </div>

            <!-- Essential Links -->
            <div
                class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-xs font-medium text-slate-600 dark:text-gray-400"
            >
                <Link
                    href="/about-us"
                    class="transition hover:text-indigo-600 dark:hover:text-indigo-400"
                >
                    About
                </Link>
                <Link
                    href="/privacy-policy"
                    class="transition hover:text-indigo-600 dark:hover:text-indigo-400"
                >
                    Privacy
                </Link>
                <Link
                    href="/terms-service"
                    class="transition hover:text-indigo-600 dark:hover:text-indigo-400"
                >
                    Terms
                </Link>
                <Link
                    href="/support"
                    class="transition hover:text-indigo-600 dark:hover:text-indigo-400"
                >
                    Support Us
                </Link>
            </div>

            <!-- Copyright at the bottom -->
            <p
                class="pt-1.5 text-[11px] font-medium text-slate-400 dark:text-gray-500"
            >
                &copy; 2026 HSCStack
            </p>
        </div>
    </footer>
</template>
