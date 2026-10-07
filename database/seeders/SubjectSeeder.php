<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'বাংলা ১ম পত্র', 'icon' => 'BookOpen', 'tailwind_format' => 'bg-red-50 text-red-600'],
            ['name' => 'বাংলা ২য় পত্র', 'icon' => 'BookOpen', 'tailwind_format' => 'bg-red-100 text-red-700'],

            ['name' => 'ইংরেজি ১ম পত্র', 'icon' => 'PenTool', 'tailwind_format' => 'bg-blue-50 text-blue-600'],
            ['name' => 'ইংরেজি ২য় পত্র', 'icon' => 'PenTool', 'tailwind_format' => 'bg-blue-100 text-blue-700'],

            ['name' => 'পদার্থবিজ্ঞান ১ম পত্র', 'icon' => 'Atom', 'tailwind_format' => 'bg-purple-50 text-purple-600'],
            ['name' => 'পদার্থবিজ্ঞান ২য় পত্র', 'icon' => 'Atom', 'tailwind_format' => 'bg-purple-100 text-purple-700'],

            ['name' => 'রসায়ন ১ম পত্র', 'icon' => 'FlaskConical', 'tailwind_format' => 'bg-emerald-50 text-emerald-600'],
            ['name' => 'রসায়ন ২য় পত্র', 'icon' => 'FlaskConical', 'tailwind_format' => 'bg-emerald-100 text-emerald-700'],

            ['name' => 'উচ্চতর গণিত ১ম পত্র', 'icon' => 'Sigma', 'tailwind_format' => 'bg-amber-50 text-amber-600'],
            ['name' => 'উচ্চতর গণিত ২য় পত্র', 'icon' => 'Sigma', 'tailwind_format' => 'bg-amber-100 text-amber-700'],

            ['name' => 'জীববিজ্ঞান ১ম পত্র', 'icon' => 'Dna', 'tailwind_format' => 'bg-green-50 text-green-600'],
            ['name' => 'জীববিজ্ঞান ২য় পত্র', 'icon' => 'Dna', 'tailwind_format' => 'bg-green-100 text-green-700'],
        ];

        foreach ($subjects as $index => $subject) {
            $isCommon = str_contains($subject['name'], 'বাংলা') || str_contains($subject['name'], 'ইংরেজি');
            DB::table('subjects')->insert([
                'name' => $subject['name'],
                'slug' => Str::slug($subject['name']),
                'icon' => $subject['icon'],
                'tailwind_format' => $subject['tailwind_format'],
                'course' => 'hsc',
                'group' => $isCommon ? 'common' : 'science',
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
