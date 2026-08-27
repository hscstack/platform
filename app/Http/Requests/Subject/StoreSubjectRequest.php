<?php

namespace App\Http\Requests\Subject;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->filled('slug')
            ? Str::slug($this->slug)
            : Str::slug($this->course.'-'.($this->english_name ?: $this->name));

        $this->merge([
            'slug' => $slug,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'min:3', 'unique:subjects,name'],
            'english_name' => ['nullable', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:subjects,slug'],
            'tailwind_format' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer'],
            'course' => ['required', 'string', 'in:ssc,hsc'],
        ];
    }
}
