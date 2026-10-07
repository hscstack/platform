<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommerceHumanitiesSubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            // HSC Commerce
            [
                'name' => 'হিসাববিজ্ঞান ১ম পত্র',
                'slug' => 'hsc-accounting-1st',
                'icon' => 'Calculator',
                'tailwind_format' => 'bg-emerald-50 text-emerald-600',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 20,
            ],
            [
                'name' => 'হিসাববিজ্ঞান ২য় পত্র',
                'slug' => 'hsc-accounting-2nd',
                'icon' => 'Calculator',
                'tailwind_format' => 'bg-emerald-100 text-emerald-700',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 21,
            ],
            [
                'name' => 'ব্যবসায় সংগঠন ও ব্যবস্থাপনা ১ম পত্র',
                'slug' => 'hsc-business-organization-1st',
                'icon' => 'Briefcase',
                'tailwind_format' => 'bg-blue-50 text-blue-600',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 22,
            ],
            [
                'name' => 'ব্যবসায় সংগঠন ও ব্যবস্থাপনা ২য় পত্র',
                'slug' => 'hsc-business-organization-2nd',
                'icon' => 'Briefcase',
                'tailwind_format' => 'bg-blue-100 text-blue-700',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 23,
            ],
            [
                'name' => 'ফিন্যান্স, ব্যাংকিং ও বিমা ১ম পত্র',
                'slug' => 'hsc-finance-1st',
                'icon' => 'Coins',
                'tailwind_format' => 'bg-amber-50 text-amber-600',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 24,
            ],
            [
                'name' => 'ফিন্যান্স, ব্যাংকিং ও বিমা ২য় পত্র',
                'slug' => 'hsc-finance-2nd',
                'icon' => 'Coins',
                'tailwind_format' => 'bg-amber-100 text-amber-700',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 25,
            ],
            [
                'name' => 'উৎপাদন ব্যবস্থাপনা ও বিপণন ১ম পত্র',
                'slug' => 'hsc-marketing-1st',
                'icon' => 'TrendingUp',
                'tailwind_format' => 'bg-purple-50 text-purple-600',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 26,
            ],
            [
                'name' => 'উৎপাদন ব্যবস্থাপনা ও বিপণন ২য় পত্র',
                'slug' => 'hsc-marketing-2nd',
                'icon' => 'TrendingUp',
                'tailwind_format' => 'bg-purple-100 text-purple-700',
                'course' => 'hsc',
                'group' => 'commerce',
                'sort_order' => 27,
            ],

            // HSC Humanities
            [
                'name' => 'পৌরনীতি ও সুশাসন ১ম পত্র',
                'slug' => 'hsc-civics-1st',
                'icon' => 'Scale',
                'tailwind_format' => 'bg-rose-50 text-rose-600',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 30,
            ],
            [
                'name' => 'পৌরনীতি ও সুশাসন ২য় পত্র',
                'slug' => 'hsc-civics-2nd',
                'icon' => 'Scale',
                'tailwind_format' => 'bg-rose-100 text-rose-700',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 31,
            ],
            [
                'name' => 'অর্থনীতি ১ম পত্র',
                'slug' => 'hsc-economics-1st',
                'icon' => 'BarChart3',
                'tailwind_format' => 'bg-cyan-50 text-cyan-600',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 32,
            ],
            [
                'name' => 'অর্থনীতি ২য় পত্র',
                'slug' => 'hsc-economics-2nd',
                'icon' => 'BarChart3',
                'tailwind_format' => 'bg-cyan-100 text-cyan-700',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 33,
            ],
            [
                'name' => 'যুক্তিবিদ্যা ১ম পত্র',
                'slug' => 'hsc-logic-1st',
                'icon' => 'Brain',
                'tailwind_format' => 'bg-violet-50 text-violet-600',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 34,
            ],
            [
                'name' => 'যুক্তিবিদ্যা ২য় পত্র',
                'slug' => 'hsc-logic-2nd',
                'icon' => 'Brain',
                'tailwind_format' => 'bg-violet-100 text-violet-700',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 35,
            ],
            [
                'name' => 'ইসলামের ইতিহাস ও সংস্কৃতি ১ম পত্র',
                'slug' => 'hsc-islamic-history-1st',
                'icon' => 'BookMarked',
                'tailwind_format' => 'bg-emerald-50 text-emerald-600',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 36,
            ],
            [
                'name' => 'ইসলামের ইতিহাস ও সংস্কৃতি ২য় পত্র',
                'slug' => 'hsc-islamic-history-2nd',
                'icon' => 'BookMarked',
                'tailwind_format' => 'bg-emerald-100 text-emerald-700',
                'course' => 'hsc',
                'group' => 'humanities',
                'sort_order' => 37,
            ],

            // SSC Commerce
            [
                'name' => 'হিসাববিজ্ঞান',
                'slug' => 'ssc-accounting',
                'icon' => 'Calculator',
                'tailwind_format' => 'bg-emerald-50 text-emerald-600',
                'course' => 'ssc',
                'group' => 'commerce',
                'sort_order' => 15,
            ],
            [
                'name' => 'ব্যবসায় উদ্যোগ',
                'slug' => 'ssc-business-entrepreneurship',
                'icon' => 'Briefcase',
                'tailwind_format' => 'bg-blue-50 text-blue-600',
                'course' => 'ssc',
                'group' => 'commerce',
                'sort_order' => 16,
            ],
            [
                'name' => 'ফিন্যান্স ও ব্যাংকিং',
                'slug' => 'ssc-finance',
                'icon' => 'Coins',
                'tailwind_format' => 'bg-amber-50 text-amber-600',
                'course' => 'ssc',
                'group' => 'commerce',
                'sort_order' => 17,
            ],

            // SSC Humanities
            [
                'name' => 'বাংলাদেশের ইতিহাস ও বিশ্বসভ্যতা',
                'slug' => 'ssc-history',
                'icon' => 'BookMarked',
                'tailwind_format' => 'bg-rose-50 text-rose-600',
                'course' => 'ssc',
                'group' => 'humanities',
                'sort_order' => 18,
            ],
            [
                'name' => 'ভূগোল ও পরিবেশ',
                'slug' => 'ssc-geography',
                'icon' => 'Globe',
                'tailwind_format' => 'bg-teal-50 text-teal-600',
                'course' => 'ssc',
                'group' => 'humanities',
                'sort_order' => 19,
            ],
            [
                'name' => 'পৌরনীতি ও নাগরিকতা',
                'slug' => 'ssc-civics',
                'icon' => 'Scale',
                'tailwind_format' => 'bg-indigo-50 text-indigo-600',
                'course' => 'ssc',
                'group' => 'humanities',
                'sort_order' => 20,
            ],
            [
                'name' => 'অর্থনীতি',
                'slug' => 'ssc-economics',
                'icon' => 'BarChart3',
                'tailwind_format' => 'bg-cyan-50 text-cyan-600',
                'course' => 'ssc',
                'group' => 'humanities',
                'sort_order' => 21,
            ],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['slug' => $subject['slug']],
                array_merge($subject, [
                    'is_trackable' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
