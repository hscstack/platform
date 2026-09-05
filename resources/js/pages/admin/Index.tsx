/**
 * AdminIndex — TSX port of the former `Index.vue` (flat, decardified).
 *
 * Same behavior as the SFC: course filter + subject list + create/edit
 * modal. Resolved via the explicit dual-extension (`*.vue` + `*.tsx`)
 * page resolver in `resources/js/app.ts`.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import CreateSubjectModal from '@/components/admin/CreateSubjectModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import SubjectIcon from '@/components/SubjectIcon.vue';
import { usePermissions } from '@/lib/usePermissions';

interface Subject {
    id: number;
    name: string;
    slug: string;
    course?: 'hsc' | 'ssc' | string | null;
    icon?: string | null;
    tailwind_format?: string | null;
    sort_order?: number | null;
    nodes_count?: number | null;
}

export default defineComponent({
    name: 'AdminIndex',
    props: {
        subjects: { type: Array as PropType<Subject[]>, required: true },
    },
    setup(props) {
        const { can } = usePermissions();

        const isCreateModalOpen = ref(false);
        const editingSubject = ref<Subject | null>(null);
        const activeCourse = ref<'all' | 'hsc' | 'ssc'>('all');

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

        const handleDelete = (subject: Subject) => {
            if (confirm('Are you sure you want to delete this Subject?')) {
                router.delete(`/admin/subjects/${subject.id}`);
            }
        };

        const openSubject = (subject: Subject) => {
            router.visit(`/admin/subjects/${subject.slug}/nodes`);
        };

        const hscCount = computed(
            () =>
                props.subjects.filter((s) => s.course?.toLowerCase() === 'hsc')
                    .length,
        );

        const sscCount = computed(
            () =>
                props.subjects.filter((s) => s.course?.toLowerCase() === 'ssc')
                    .length,
        );

        const filteredSubjects = computed(() => {
            if (activeCourse.value === 'all') {
                return props.subjects;
            }

            return props.subjects.filter(
                (s) =>
                    s.course?.toLowerCase() ===
                    activeCourse.value.toLowerCase(),
            );
        });

        const tabClass = (isActive: boolean) => [
            'cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all active:scale-95 focus-visible:outline-2 focus-visible:outline-indigo-500',
            isActive
                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
        ];

        const ghostActionClass =
            'flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 active:scale-95 focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300';

        const roseGhostActionClass =
            'flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 active:scale-95 focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400';

        return () => (
            <>
                <Head title="Manage Contents" />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Manage Subjects
                                </h1>
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    {props.subjects.length}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Organize HSC and SSC subjects and their
                                contents.
                            </p>
                        </div>

                        {can('create subjects') && (
                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    onClick={openCreateModal}
                                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                >
                                    <Plus
                                        class="h-3.5 w-3.5"
                                        strokeWidth={2.2}
                                    />
                                    <span>New Subject</span>
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Create/Edit Subject Modal */}
                    <CreateSubjectModal
                        isOpen={isCreateModalOpen.value}
                        subject={editingSubject.value}
                        onClose={handleModalClose}
                    />

                    {/* Course filter (shared segmented style) */}
                    <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 dark:bg-gray-800">
                        <button
                            type="button"
                            onClick={() => (activeCourse.value = 'all')}
                            class={tabClass(activeCourse.value === 'all')}
                        >
                            All ({props.subjects.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => (activeCourse.value = 'hsc')}
                            class={tabClass(activeCourse.value === 'hsc')}
                        >
                            HSC ({hscCount.value})
                        </button>
                        <button
                            type="button"
                            onClick={() => (activeCourse.value = 'ssc')}
                            class={tabClass(activeCourse.value === 'ssc')}
                        >
                            SSC ({sscCount.value})
                        </button>
                    </div>

                    {/* Flat subject list */}
                    <div class="flex flex-1 flex-col">
                        {filteredSubjects.value.length > 0 ? (
                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                {filteredSubjects.value.map((subject) => (
                                    <div
                                        key={subject.id || subject.name}
                                        onClick={() => openSubject(subject)}
                                        role="button"
                                        tabIndex={0}
                                        onKeyDown={(e: KeyboardEvent) => {
                                            if (
                                                e.key === 'Enter' ||
                                                e.key === ' '
                                            ) {
                                                e.preventDefault();
                                                openSubject(subject);
                                            }
                                        }}
                                        class="group flex cursor-pointer items-center gap-3 py-2.5 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 dark:hover:bg-gray-800/50"
                                    >
                                        <div
                                            class={[
                                                subject.tailwind_format ||
                                                    'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400',
                                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                            ]}
                                        >
                                            <SubjectIcon
                                                name={subject.icon}
                                                className="h-4 w-4 stroke-[2]"
                                            />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
                                                {subject.name}
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                                {subject.course
                                                    ? `${subject.course.toUpperCase()} · `
                                                    : ''}
                                                {subject.nodes_count ?? 0}{' '}
                                                {(subject.nodes_count ?? 0) ===
                                                1
                                                    ? 'node'
                                                    : 'nodes'}
                                            </p>
                                        </div>

                                        <div
                                            class="flex shrink-0 items-center gap-0.5"
                                            onClick={(e: MouseEvent) =>
                                                e.stopPropagation()
                                            }
                                        >
                                            {can('edit subjects') && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        openEditModal(subject)
                                                    }
                                                    class={[
                                                        ghostActionClass,
                                                        'hover:text-indigo-600 dark:hover:text-indigo-400',
                                                    ]}
                                                    title="Edit subject"
                                                    aria-label="Edit subject"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </button>
                                            )}
                                            {can('delete subjects') && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleDelete(subject)
                                                    }
                                                    class={roseGhostActionClass}
                                                    title="Delete subject"
                                                    aria-label="Delete subject"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            )}
                                            <Link
                                                href={`/admin/subjects/${subject.slug}/nodes`}
                                                class={ghostActionClass}
                                                title="Open subject"
                                                aria-label="Open subject"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                title="No subjects found"
                                description="No subjects have been created in this category yet."
                                showCta={false}
                            />
                        )}
                    </div>
                </div>
            </>
        );
    },
});
