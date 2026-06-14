<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'min:3', 'unique:subjects,name'],
            'tailwind_format' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $subject = new Subject();

        $subject->name = $validated['name'];
        $subject->tailwind_format = $validated['tailwind_format'];
        $subject->slug = Str::slug($validated['name']);
        $subject->icon = $validated['icon'];
        $subject->sort_order = $validated['sort_order'] ?? 0;

        $subject->save();

        return redirect()->route('admin.subjects.index');
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:100', 'min:3', Rule::unique('subjects', 'name')->ignore($subject->id),],
            'tailwind_format' => ['sometimes', 'string', 'max:100'],
            'icon'            => ['sometimes', 'string', 'max:50'],
            'sort_order'      => ['sometimes', 'nullable', 'integer'],
        ]);

        if (isset($validated['name'])) {
            $subject->name = $validated['name'];
            $subject->slug = Str::slug($validated['name']);
        }

        if (isset($validated['tailwind_format'])) {
            $subject->tailwind_format = $validated['tailwind_format'];
        }

        if (isset($validated['icon'])) {
            $subject->icon = $validated['icon'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $subject->sort_order = $validated['sort_order'] ?? 0;
        }

        $subject->save();

        return redirect()->route('admin.subjects.index');
    }


    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->back()->with('success', 'Node deleted successfully.');
    }
}
