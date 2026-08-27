<?php

namespace App\Http\Requests\Subject;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
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
        if ($this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug($this->slug),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'name' => ['sometimes', 'string', 'max:100', 'min:3', Rule::unique('subjects', 'name')->ignore($subject->id)],
            'english_name' => ['nullable', 'string', 'max:100'],
            'tailwind_format' => ['sometimes', 'string', 'max:100'],
            'icon' => ['sometimes', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('subjects', 'slug')->ignore($subject->id)],
            'course' => ['sometimes', 'string', 'in:ssc,hsc'],
        ];
    }
}
