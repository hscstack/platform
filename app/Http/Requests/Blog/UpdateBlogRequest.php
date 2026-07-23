<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $excerpt = Str::limit(strip_tags($this->input('content')), 150);

        $this->merge([
            'slug' => Str::slug($this->input('title')),
            'excerpt' => $excerpt,
            'meta_title' => $this->input('title'),
            'meta_description' => $excerpt,
        ]);
    }

    public function rules(): array
    {
        $blog = $this->route('blog');

        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('blogs', 'title')->ignore($blog->id),
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('blogs', 'slug')->ignore($blog->id),
            ],

            'excerpt' => [
                'nullable',
                'string',
                'max:160',
            ],

            'content' => [
                'required',
                'string',
            ],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],

            'is_published' => [
                'required',
                'boolean',
            ],

            'is_featured' => [
                'required',
                'boolean',
            ],

            'seo_tags' => [
                'nullable',
                'string',
                'max:255',
            ],

            'meta_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:160',
            ],
        ];
    }
}
