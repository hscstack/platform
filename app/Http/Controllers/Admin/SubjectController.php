<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index(Request $request)
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
            ->withCount('nodes')
            ->get();

        return Inertia::render('admin/Index', [
            'subjects' => $subjects,
            'current_course' => $course,
            'current_group' => $group,
        ]);
    }

    public function store(StoreSubjectRequest $request)
    {
        Subject::create($request->validated());

        return back()->with('success', 'Subject created successfully.');
    }

    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());

        return back()->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->back()->with('success', 'Subject deleted successfully.');
    }
}
