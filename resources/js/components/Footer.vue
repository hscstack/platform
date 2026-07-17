<script setup lang="ts">
import { Github, Facebook } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
</script>

<template>
    <footer class="mt-16 border-t border-slate-100 bg-white py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="grid grid-cols-1 gap-y-10 md:grid-cols-12 md:gap-x-12">
                <div
                    class="flex flex-col items-center text-center md:col-span-5 md:items-start md:text-left"
                >
                    <AppLogo />
                    <p
                        class="mt-4 max-w-sm text-sm leading-relaxed text-slate-500"
                    >
                        Empowering the next generation of students with high
                        quality, direct, and accessible academic resources.
                    </p>

                    <div class="mt-4 flex items-center gap-4">
                        <a
                            href="https://github.com/hsc-stack"
                            rel="noopener noreferrer"
                            class="text-slate-400 transition-colors duration-150 hover:text-slate-900"
                            aria-label="GitHub Repository"
                        >
                            <Github class="h-5 w-5" />
                        </a>

                        <a
                            href="https://www.facebook.com/hscstackbd"
                            rel="noopener noreferrer"
                            class="text-slate-400 transition-colors duration-150 hover:text-blue-600"
                            aria-label="Facebook Page"
                        >
                            <Facebook class="h-5 w-5" />
                        </a>
                    </div>

                    <p
                        class="mt-4 hidden text-xs font-medium text-slate-400 md:block"
                    >
                        A concern of
                        <a
                            href="https://tajimz.xyz"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-slate-600 underline transition-colors hover:text-indigo-600"
                            >Tajim</a
                        >
                    </p>
                </div>

                <div
                    class="grid grid-cols-2 gap-x-8 gap-y-8 border-t border-slate-100 pt-10 sm:gap-x-12 md:col-span-7 md:grid-cols-2 md:border-t-0 md:pt-0"
                >
                    <div class="text-center md:text-left">
                        <h4
                            class="text-xs font-bold tracking-widest text-slate-900 uppercase"
                        >
                            Platform
                        </h4>
                        <ul class="mt-5 space-y-3.5">
                            <li>
                                <a
                                    href="https://tajimz.xyz/projects"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    More From Us
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/join"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Join Our Team
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/about-us"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Our Contributors
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="text-center md:text-left">
                        <h4
                            class="text-xs font-bold tracking-widest text-slate-900 uppercase"
                        >
                            Legal
                        </h4>
                        <ul class="mt-5 space-y-3.5">
                            <li>
                                <a
                                    href="/privacy-policy"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Privacy Policy
                                </a>
                            </li>
                            <li>
                                <a
                                    href="/terms-service"
                                    target="_blank"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-indigo-600"
                                >
                                    Terms of Service
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-8 sm:flex-row"
            >
                <p
                    class="text-center text-xs font-medium text-slate-400 md:hidden"
                >
                    A concern of
                    <a
                        href="https://tajimz.xyz"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-600 underline transition-colors hover:text-indigo-600"
                        >Tajim</a
                    >
                </p>

                <p
                    class="text-center text-xs font-medium text-slate-400 sm:text-left"
                >
                    &copy; 2026 HSCStack. Built for the future of learning.
                </p>
            </div>
        </div>
    </footer>
</template>
