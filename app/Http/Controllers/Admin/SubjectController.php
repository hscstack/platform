<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SubjectController extends Controller
{
    function index()
    {
        $subjects = Subject::orderBy('sort_order', 'asc')->withCount('nodes')->get();

        return Inertia::render('admin/Index', [
            'subjects' => $subjects,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/SubjectCreateOrEdit');
    }

    public function edit(Subject $subject)
    {
        return Inertia::render('admin/SubjectCreateOrEdit', [
            'subject' => $subject
        ]);
    }


    public function store(StoreSubjectRequest $request)
    {
        Subject::create($request->validated());
        Cache::forget('home_page_data');

        return redirect()->route('admin.subjects.index');
    }

    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());
        Cache::forget('home_page_data');


        return redirect()->route('admin.subjects.index');
    }


    public function destroy(Subject $subject)
    {
        $subject->delete();
        Cache::forget('home_page_data');

        return redirect()->back()->with('success', 'Subject deleted successfully.');
    }
}
