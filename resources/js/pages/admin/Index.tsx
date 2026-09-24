/**
 * AdminIndex — TSX port of `Index.vue`.
 *
 * Server-query-driven course/group filters + subject list + create/edit
 * modal, rewritten as a `.tsx` `defineComponent` render function.
 * Resolved via the explicit dual-extension (`*.vue` + `*.tsx`) page
 * resolver in `resources/js/app.ts`.
 *
 * Ported 1:1 from the SFC; shared admin chrome (header/buttons) comes
 * from `@/components/admin/ui`.
 */
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import CreateSubjectModal from '@/components/admin/CreateSubjectModal.vue';
import SubjectCard from '@/components/admin/SubjectCard.vue';
import {
    AdminPageHeader,
    adminPageClass,
    adminPrimaryBtnClass,
} from '@/components/admin/ui';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

interface Subject {
    id?: number | null;
    name: string;
    slug?: string | null;
    course?: string | null;
    [key: string]: unknown;
}

/* Filter option data shared by both pill groups. */
const CURRICULUM_PILLS = [
    { key: 'hsc', label: 'HSC' },
    { key: 'ssc', label: 'SSC' },
];

const GROUP_PILLS = [
    { key: 'science', label: 'বিজ্ঞান' },
    { key: 'humanities', label: 'মানবিক' },
    { key: 'commerce', label: 'ব্যবসায় শিক্ষা' },
];

/** One server-query-driven filter pill (active = the rail's indigo wash). */
const FilterPill = defineComponent({
    name: 'AdminFilterPill',
    props: {
        href: { type: String, required: true },
        label: { type: String, required: true },
        active: { type: Boolean, default: false },
    },
    setup(props) {
        return () => (
            <Link
                href={props.href}
                preserve-scroll
                class={[
                    'inline-flex cursor-pointer items-center rounded-[10px] px-3 py-1.5 text-xs transition-colors duration-150 select-none',
                    props.active
                        ? 'bg-indigo-50 font-semibold text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                        : 'text-slate-500 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                ]}
            >
                <span>{props.label}</span>
            </Link>
        );
    },
});

export default defineComponent({
    name: 'AdminIndex',
    props: {
        subjects: { type: Array as PropType<Subject[]>, required: true },
        currentCourse: {
            type: String as PropType<string | null>,
            default: null,
        },
        currentGroup: {
            type: String as PropType<string | null>,
            default: null,
        },
    },
    setup(props) {
        const { can } = usePermissions();

        const isCreateModalOpen = ref(false);
        const editingSubject = ref<Subject | null>(null);

        const openCreateModal = () => {
            editingSubject.value = null;
            isCreateModalOpen.value = true;
        };

        const openEditModal = (subject: Subject) => {
            editingSubject.value = subject;
            isCreateModalOpen.value = true;
        };

        const handleModalClose = () => {
            isCreateModalOpen.value = false;
            editingSubject.value = null;
        };

        const getFilterUrl = (course: string, group: string) => {
            return `/admin/subjects?course=${course}&group=${group}`;
        };

        return () => (
            <>
                <Head title="Manage Contents" />

                <div class={adminPageClass}>
                    {/* Compact Page Title Bar */}
                    <AdminPageHeader title="Manage Subjects">
                        {{
                            actions: () =>
                                can('create subjects') && (
                                    <button
                                        type="button"
                                        onClick={openCreateModal}
                                        class={adminPrimaryBtnClass}
                                    >
                                        <Plus
                                            class="h-3.5 w-3.5"
                                            strokeWidth={2.2}
                                        />
                                        <span>Create Subject</span>
                                    </button>
                                ),
                        }}
                    </AdminPageHeader>

                    {/* Create/Edit Subject Modal */}
                    <CreateSubjectModal
                        isOpen={isCreateModalOpen.value}
                        subject={editingSubject.value}
                        defaultCourse={props.currentCourse || 'hsc'}
                        defaultGroup={props.currentGroup || 'science'}
                        onClose={handleModalClose}
                    />

                    {/* Filter Pills Bar (Server Query Driven, Defaults to HSC & Science) */}
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        {/* Curriculum Filter Pills */}
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">
                                কারিকুলাম:
                            </span>
                            {CURRICULUM_PILLS.map((c) => (
                                <FilterPill
                                    key={c.key}
                                    href={getFilterUrl(
                                        c.key,
                                        props.currentGroup || 'science',
                                    )}
                                    label={c.label}
                                    active={
                                        (props.currentCourse || 'hsc') === c.key
                                    }
                                />
                            ))}
                        </div>

                        {/* Group Filter Pills */}
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">
                                বিভাগ:
                            </span>
                            {GROUP_PILLS.map((g) => (
                                <FilterPill
                                    key={g.key}
                                    href={getFilterUrl(
                                        props.currentCourse || 'hsc',
                                        g.key,
                                    )}
                                    label={g.label}
                                    active={
                                        (props.currentGroup || 'science') ===
                                        g.key
                                    }
                                />
                            ))}
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col">
                        {props.subjects && props.subjects.length > 0 ? (
                            <div class="flex flex-col gap-2.5 sm:gap-3">
                                {props.subjects.map((subject) => (
                                    <SubjectCard
                                        key={subject.id || subject.name}
                                        admin={true}
                                        subject={subject}
                                        onEdit={openEditModal}
                                    />
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                title="No subjects found"
                                description="No subjects have been created in this category yet."
                            />
                        )}
                    </div>
                </div>
            </>
        );
    },
});
