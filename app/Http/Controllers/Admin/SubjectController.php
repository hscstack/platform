<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

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
        return Inertia::render('admin/SubjectCreate');
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'min:3'],
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

}
