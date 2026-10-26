<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CurriculumController extends Controller
{
    public function subjectIndex(Request $request)
    {
        $course = $request->query('course', 'hsc');
        if (! in_array($course, ['hsc', 'ssc'], true)) {
            $course = 'hsc';
        }

        $group = $request->query('group', 'science');
        if (! in_array($group, ['science', 'humanities', 'commerce'], true)) {
            $group = 'science';
        }

        $subjects = Subject::where('course', $course)
            ->whereIn('group', [$group, 'common'])
            ->orderBy('sort_order', 'asc')
            ->withCount('chapters')
            ->get();

        return Inertia::render('admin/Index', [
            'subjects' => $subjects,
            'current_course' => $course,
            'current_group' => $group,
        ]);
    }

    public function storeSubject(StoreSubjectRequest $request)
    {
        Subject::create($request->validated());

        return back()->with('success', 'Subject created successfully.');
    }

    public function updateSubject(UpdateSubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());

        return back()->with('success', 'Subject updated successfully.');
    }

    public function destroySubject(Subject $subject)
    {
        $subject->delete();

        return redirect()->back()->with('success', 'Subject deleted successfully.');
    }

    public function chapterIndex(Subject $subject)
    {
        $chapters = Chapter::where('subject_id', $subject->id)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('admin/Chapter', [
            'subject' => $subject,
            'chapters' => $chapters,
            'resources' => [],
            'breadcrumb' => [],
        ]);
    }

    public function storeChapter(StoreChapterRequest $request, Subject $subject)
    {
        $validated = $request->validated();

        Chapter::create([
            'user_id' => auth()->id(),
            'subject_id' => $subject->id,
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'weight' => (int) ($validated['weight'] ?? 2),
        ]);

        return back()->with('success', 'Chapter created successfully.');
    }

    public function updateChapter(UpdateChapterRequest $request, Subject $subject, Chapter $chapter)
    {
        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $chapter->name = $validated['name'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $chapter->sort_order = $validated['sort_order'] ?? 0;
        }

        if (array_key_exists('weight', $validated)) {
            $chapter->weight = (int) $validated['weight'];
        }

        $chapter->save();

        return back()->with('success', 'Chapter updated successfully.');
    }

    public function batchStoreChapters(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'chapters' => 'nullable|array',
            'nodes' => 'nullable|array',
        ]);

        $inputChapters = $validated['chapters'] ?? $validated['nodes'] ?? [];

        $baseSortOrder = Chapter::where('subject_id', $subject->id)
            ->max('sort_order') ?? 0;

        $createdCount = 0;

        DB::transaction(function () use ($inputChapters, $subject, $baseSortOrder, &$createdCount) {
            foreach ($inputChapters as $index => $chapterData) {
                $chapterName = trim($chapterData['name'] ?? '');
                if (empty($chapterName)) {
                    continue;
                }

                Chapter::create([
                    'user_id' => auth()->id(),
                    'subject_id' => $subject->id,
                    'name' => $chapterName,
                    'sort_order' => $baseSortOrder + $index + 1,
                    'weight' => (int) ($chapterData['weight'] ?? 2),
                ]);

                $createdCount++;
            }
        });

        return back()->with('success', "Successfully generated {$createdCount} chapters!");
    }

    public function destroyChapter(Chapter $chapter)
    {
        $chapter->delete();

        return redirect()->back()->with('success', 'Chapter deleted successfully.');
    }
}
