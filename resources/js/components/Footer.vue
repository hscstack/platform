<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
import AppLogo from './AppLogo.vue';
</script>

<template>
    <footer
        class="mt-16 border-t border-slate-100 bg-white py-12 sm:py-16 dark:border-gray-800 dark:bg-gray-900"
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
                            class="text-slate-400 transition-colors duration-150 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400"
                            aria-label="Facebook Page"
                        >
                            <Instagram class="h-5 w-5" />
                        </a>
                    </div>

                    <p
                        class="mt-4 hidden text-xs font-medium text-slate-400 md:block dark:text-gray-500"
                    >
                        A concern of
                        <a
                            href="https://tajimz.xyz"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-slate-600 underline transition-colors hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400"
                            >Tajim</a
                        >
                    </p>
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
                                    <span>Support HSCStack</span>
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
                    class="text-center text-xs font-medium text-slate-400 md:hidden dark:text-gray-500"
                >
                    A concern of
                    <a
                        href="https://tajimz.xyz"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-600 underline transition-colors hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400"
                        >Tajim</a
                    >
                </p>

                <p
                    class="text-center text-xs font-medium text-slate-400 sm:text-left dark:text-gray-500"
                >
                    &copy; 2026 HSCStack. Built for the future of learning.
                </p>
            </div>
        </div>
    </footer>
</template>
