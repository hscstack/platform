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
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
</script>

<template>
    <footer
        class="relative mt-16 overflow-hidden border-t-4 border-red-700 bg-white py-12 sm:py-16"
    >
        <!-- faint red/blue tint wash -->
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-red-50/70 via-white to-blue-50/70"
        ></div>
        <!-- subtle web-dot pattern accent -->
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.05] [background-image:radial-gradient(circle_at_1px_1px,black_1px,transparent_1px)] [background-size:18px_18px]"
        ></div>
        <!-- top accent stripe -->
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-700 via-blue-700 to-red-700"
        ></div>

        <!-- scattered spider glyphs -->
        <svg
            class="pointer-events-none absolute top-8 left-[8%] h-7 w-7 text-red-700/40 sm:h-9 sm:w-9"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20" />
        </svg>
        <svg
            class="pointer-events-none absolute top-20 right-[14%] h-6 w-6 text-blue-700/40 sm:h-8 sm:w-8"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20" />
        </svg>
        <svg
            class="pointer-events-none absolute bottom-32 left-[24%] h-6 w-6 text-red-700/30 sm:h-8 sm:w-8"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20" />
        </svg>
        <svg
            class="pointer-events-none absolute right-[8%] bottom-36 h-7 w-7 text-blue-700/30 sm:h-9 sm:w-9"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
        >
            <ellipse cx="12" cy="13" rx="3.2" ry="4.2" />
            <circle cx="12" cy="7" r="1.8" />
            <path d="M9 10 L4 7 M9 10 L3 11 M9 10 L4 15 M15 10 L20 7 M15 10 L21 11 M15 10 L20 15 M10.5 16 L7 20 M13.5 16 L17 20" />
        </svg>

        <!-- big web-net graphic, bottom-left corner (soft radial fan) -->
        <svg
            class="pointer-events-none absolute -bottom-4 -left-4 h-28 w-28 text-red-700/35 sm:h-32 sm:w-32"
            viewBox="0 0 100 100"
            fill="none"
            stroke-linecap="round"
            xmlns="http://www.w3.org/2000/svg"
        >
            <!-- spokes fanning from the corner -->
            <line x1="0" y1="100" x2="100" y2="100" stroke="currentColor" stroke-width="1" opacity="0.5" />
            <line x1="0" y1="100" x2="96.6" y2="74.1" stroke="currentColor" stroke-width="1" opacity="0.7" />
            <line x1="0" y1="100" x2="86.6" y2="50" stroke="currentColor" stroke-width="1" opacity="0.8" />
            <line x1="0" y1="100" x2="70.7" y2="29.3" stroke="currentColor" stroke-width="1" />
            <line x1="0" y1="100" x2="50" y2="13.4" stroke="currentColor" stroke-width="1" opacity="0.8" />
            <line x1="0" y1="100" x2="25.9" y2="3.4" stroke="currentColor" stroke-width="1" opacity="0.7" />
            <line x1="0" y1="100" x2="0" y2="0" stroke="currentColor" stroke-width="1" opacity="0.5" />
            <!-- concentric rounded arcs -->
            <path d="M15,100 A15,15 0 0,1 0,85" stroke="currentColor" stroke-width="0.9" />
            <path d="M30,100 A30,30 0 0,1 0,70" stroke="currentColor" stroke-width="0.9" />
            <path d="M46,100 A46,46 0 0,1 0,54" stroke="currentColor" stroke-width="0.9" />
            <path d="M64,100 A64,64 0 0,1 0,36" stroke="currentColor" stroke-width="0.8" />
            <path d="M84,100 A84,84 0 0,1 0,16" stroke="currentColor" stroke-width="0.8" />
        </svg>

        <!-- big web-net graphic, bottom-right corner (soft radial fan) -->
        <svg
            class="pointer-events-none absolute -bottom-4 -right-4 h-28 w-28 text-blue-700/30 sm:h-32 sm:w-32"
            viewBox="0 0 100 100"
            fill="none"
            stroke-linecap="round"
            xmlns="http://www.w3.org/2000/svg"
        >
            <line x1="100" y1="100" x2="0" y2="100" stroke="currentColor" stroke-width="1" opacity="0.5" />
            <line x1="100" y1="100" x2="3.4" y2="74.1" stroke="currentColor" stroke-width="1" opacity="0.7" />
            <line x1="100" y1="100" x2="13.4" y2="50" stroke="currentColor" stroke-width="1" opacity="0.8" />
            <line x1="100" y1="100" x2="29.3" y2="29.3" stroke="currentColor" stroke-width="1" />
            <line x1="100" y1="100" x2="50" y2="13.4" stroke="currentColor" stroke-width="1" opacity="0.8" />
            <line x1="100" y1="100" x2="74.1" y2="3.4" stroke="currentColor" stroke-width="1" opacity="0.7" />
            <line x1="100" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="1" opacity="0.5" />
            <path d="M85,100 A15,15 0 0,0 100,85" stroke="currentColor" stroke-width="0.9" />
            <path d="M70,100 A30,30 0 0,0 100,70" stroke="currentColor" stroke-width="0.9" />
            <path d="M54,100 A46,46 0 0,0 100,54" stroke="currentColor" stroke-width="0.9" />
            <path d="M36,100 A64,64 0 0,0 100,36" stroke="currentColor" stroke-width="0.8" />
            <path d="M16,100 A84,84 0 0,0 100,16" stroke="currentColor" stroke-width="0.8" />
        </svg>

        <!-- tiny web-net fragment, top-left corner -->
        <svg
            class="pointer-events-none absolute -top-2 -left-2 h-10 w-10 text-red-700/20"
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <line x1="0" y1="0" x2="100" y2="0" stroke="currentColor" stroke-width="1.5" />
            <line x1="0" y1="0" x2="0" y2="100" stroke="currentColor" stroke-width="1.5" />
            <line x1="0" y1="0" x2="100" y2="60" stroke="currentColor" stroke-width="1" />
            <line x1="0" y1="0" x2="60" y2="100" stroke="currentColor" stroke-width="1" />
            <path d="M14,0 A14,14 0 0,1 0,14" stroke="currentColor" stroke-width="1" />
            <path d="M32,0 A32,32 0 0,1 0,32" stroke="currentColor" stroke-width="1" />
            <path d="M55,0 A55,55 0 0,1 0,55" stroke="currentColor" stroke-width="1" />
        </svg>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6">
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
                            href="https://github.com/hscstack"
                            rel="noopener noreferrer"
                            target="_blank"
                            class="text-slate-400 transition-colors duration-150 hover:text-red-700"
                            aria-label="GitHub Repository"
                        >
                            <Github class="h-5 w-5" />
                        </a>

                        <a
                            href="https://www.facebook.com/hscstackbd"
                            rel="noopener noreferrer"
                            target="_blank"
                            class="text-slate-400 transition-colors duration-150 hover:text-blue-700"
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
                            class="text-slate-600 underline transition-colors hover:text-red-700"
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
                                <Link
                                    href="/guide"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-red-700"
                                >
                                    <BookOpen class="h-4 w-4 text-red-600" />
                                    <span>Documentation</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/about-us"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-red-700"
                                >
                                    <Users class="h-4 w-4 text-red-600" />
                                    <span>Our Contributors</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/join"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-red-700"
                                >
                                    <UserPlus class="h-4 w-4 text-red-600" />
                                    <span>Join Our Team</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/support"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-red-700"
                                >
                                    <Heart class="h-4 w-4 text-red-600" />
                                    <span>Support HSC Stack</span>
                                </Link>
                            </li>
                            <li>
                                <a
                                    href="https://tajimz.xyz/projects"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-red-700"
                                >
                                    <ExternalLink
                                        class="h-4 w-4 text-red-600"
                                    />
                                    <span>More From Us</span>
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
                                <Link
                                    href="/privacy-policy"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-blue-700"
                                >
                                    Privacy Policy
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/terms-service"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-blue-700"
                                >
                                    Terms of Service
                                </Link>
                            </li>

                            <li>
                                <Link
                                    href="/content-policy"
                                    class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-blue-700"
                                >
                                    Content Policy
                                </Link>
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
                        class="text-slate-600 underline transition-colors hover:text-red-700"
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
