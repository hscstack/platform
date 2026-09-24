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
import {
    AdminPageHeader,
    SegmentedTabs,
    adminDangerIconBtnClass,
    adminHoverListClass,
    adminIconBtnClass,
    adminPageClass,
    adminPrimaryBtnClass,
    adminRowClass,
} from '@/components/admin/ui';
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

        const tabOptions = computed(() => [
            { value: 'all', label: `All (${props.subjects.length})` },
            { value: 'hsc', label: `HSC (${hscCount.value})` },
            { value: 'ssc', label: `SSC (${sscCount.value})` },
        ]);

        return () => (
            <>
                <Head title="Manage Contents" />

                <div class={adminPageClass}>
                    {/* Page header */}
                    <AdminPageHeader
                        title="Manage Subjects"
                        count={props.subjects.length}
                        description="Organize HSC and SSC subjects and their contents."
                    >
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
                                        <span>New Subject</span>
                                    </button>
                                ),
                        }}
                    </AdminPageHeader>

                    {/* Create/Edit Subject Modal */}
                    <CreateSubjectModal
                        isOpen={isCreateModalOpen.value}
                        subject={editingSubject.value}
                        onClose={handleModalClose}
                    />

                    {/* Course filter (shared segmented style) */}
                    <SegmentedTabs
                        tabs={tabOptions.value}
                        active={activeCourse.value}
                        onSelect={(value: string) =>
                            (activeCourse.value =
                                value as typeof activeCourse.value)
                        }
                    />

                    {/* Flat subject list */}
                    <div class="flex flex-1 flex-col">
                        {filteredSubjects.value.length > 0 ? (
                            <div class={adminHoverListClass}>
                                {filteredSubjects.value.map((subject) => (
                                    <div
                                        key={subject.id || subject.name}
                                        onClick={() => openSubject(subject)}
                                        role="button"
                                        tabIndex={0}
                                        onKeyDown={(e: KeyboardEvent) => {
                                            // Only the row itself opens the
                                            // subject; keydowns bubbling up
                                            // from the inner action buttons
                                            // must reach the buttons.
                                            if (e.target !== e.currentTarget) {
                                                return;
                                            }

                                            if (
                                                e.key === 'Enter' ||
                                                e.key === ' '
                                            ) {
                                                e.preventDefault();
                                                openSubject(subject);
                                            }
                                        }}
                                        class={[
                                            adminRowClass,
                                            'group cursor-pointer',
                                        ]}
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
                                                        adminIconBtnClass,
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
                                                    class={
                                                        adminDangerIconBtnClass
                                                    }
                                                    title="Delete subject"
                                                    aria-label="Delete subject"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            )}
                                            <Link
                                                href={`/admin/subjects/${subject.slug}/nodes`}
                                                class={adminIconBtnClass}
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
