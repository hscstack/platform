<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Models\Blog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::latest()->get();

        return Inertia::render('admin/Blog', [
            'blogs' => $blogs,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/BlogCreateOrEdit');
    }

    public function edit(Blog $blog)
    {

        return Inertia::render('admin/BlogCreateOrEdit', [
            'blog' => $blog,
        ]);
    }

    public function store(StoreBlogRequest $request)
    {

        $data = $request->validated();
        $data['user_id'] = Auth::id();


        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')
                ->store('blogs', 'public');

            $data['featured_image'] = Storage::url($path);
        }

        Blog::create($data);

        return redirect()->route('admin.blogs.index');
    }

    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        $data = $request->validated();


        if ($request->hasFile('featured_image')) {

            // Delete old image
            if ($blog->featured_image) {
                $oldPath = str_replace('/storage/', '', $blog->featured_image);
                Storage::disk('public')->delete($oldPath);
            }

            // Store new image
            $path = $request->file('featured_image')
                ->store('blogs', 'public');

            $data['featured_image'] = Storage::url($path);
        } else {
            // Keep existing image
            unset($data['featured_image']);
        }

        $blog->update($data);


        return redirect()
            ->route('admin.blogs.index')
            ->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->featured_image) {
            $path = str_replace('/storage/', '', $blog->featured_image);
            Storage::disk('public')->delete($path);
        }

        $blog->delete();

        return redirect()
            ->back()
            ->with('success', 'Blog deleted successfully.');
    }
}
