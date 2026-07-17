<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreBlogRequest extends FormRequest
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
            'meta_title' => $this->title,
            'meta_description' => $excerpt,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255', 'unique:blogs,title'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blogs,slug'],
            'excerpt' => ['nullable', 'string', 'max:160'],

            'content' => ['required', 'string'],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],

            'is_published' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],

            'seo_tags' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }
}
