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

        $content = (string) $this->input('content', '');
        $clean = preg_replace('/```[\s\S]*?```|\$\$[\s\S]*?\$\$/', ' [Equation] ', $content);
        $clean = preg_replace('/\\$[^$\n]+\\$/', ' [Equation] ', (string) $clean);
        $excerpt = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $clean))), 150);

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
                'max:5120',
            ],

            'is_published' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],

            'seo_tags' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }
}
