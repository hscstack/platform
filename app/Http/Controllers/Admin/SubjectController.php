<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Models\Subject;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('course', 'desc')
            ->orderBy('sort_order', 'asc')
            ->withCount('nodes')
            ->get();

        return Inertia::render('admin/Index', [
            'subjects' => $subjects,
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
