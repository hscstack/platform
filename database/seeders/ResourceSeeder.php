<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        // Scan real image files from storage if available
        $imageDir = storage_path('app/public/resources/images');
        $availableImages = [];
        if (is_dir($imageDir)) {
            $files = scandir($imageDir);
            $availableImages = array_values(array_filter($files, fn ($f) => str_ends_with($f, '.jpg') || str_ends_with($f, '.png') || str_ends_with($f, '.webp')
            ));
        }

        // Fallback placeholder images if none on disk
        if (empty($availableImages)) {
            $availableImages = [
                'sample1.jpg',
                'sample2.jpg',
                'sample3.jpg',
                'sample4.jpg',
            ];
        }

        // Real educational YouTube lecture URLs
        $youtubeUrls = [
            'https://www.youtube.com/watch?v=lnnYO482hhg',
            'https://www.youtube.com/watch?v=1fRPMrIBgUk',
            'https://www.youtube.com/watch?v=vXsBw4zz5MQ',
            'https://www.youtube.com/watch?v=pzAeyWKWSs0',
            'https://www.youtube.com/watch?v=aXs8t1j9SwY',
            'https://www.youtube.com/watch?v=FijbFpRurag',
            'https://www.youtube.com/watch?v=45tM4SLSwCY',
            'https://www.youtube.com/watch?v=quiNSYM51fA',
        ];

        // Sample PDF URLs (including real Google Drive previewable PDF links)
        $pdfUrls = [
            'https://drive.google.com/file/d/1L2_3I1liaXy5YlYBAHEEe1iU1eR7ppI2/view',
            'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
        ];

        // Pick leaf nodes (nodes without children) across various subjects
        $leafNodes = Node::whereDoesntHave('children')
            ->with('subject')
            ->take(50)
            ->get();

        $imageIndex = 0;
        $videoIndex = 0;

        $statuses = ['approved', 'approved', 'approved', 'pending', 'rejected'];

        $getRandomStatus = function () use ($statuses, $user): array {
            $status = $statuses[array_rand($statuses)];

            if ($status === 'approved') {
                $upvotes = rand(2, 45);

                return [
                    'status' => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now()->subDays(rand(1, 28))->subHours(rand(1, 23)),
                    'admin_note' => null,
                    'vote_score' => $upvotes,
                    'upvotes_count' => $upvotes,
                    'downvotes_count' => 0,
                ];
            }

            if ($status === 'rejected') {
                return [
                    'status' => 'rejected',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now()->subDays(rand(1, 10)),
                    'admin_note' => 'কন্টেন্টের মান যাচাইয়ে উত্তীর্ণ হয়নি অথবা অসঙ্গতি পাওয়া গেছে।',
                    'vote_score' => 0,
                    'upvotes_count' => 0,
                    'downvotes_count' => 0,
                ];
            }

            return [
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'admin_note' => null,
                'vote_score' => 0,
                'upvotes_count' => 0,
                'downvotes_count' => 0,
            ];
        };

        foreach ($leafNodes as $node) {
            $subjectName = $node->subject?->name ?? 'সাধারণ';

            // 1. Multi-Page Scanned Handwritten Note (format = 'image')
            $notePagesCount = rand(4, 8);
            $noteItems = [];
            for ($page = 1; $page <= $notePagesCount; $page++) {
                $imageFile = $availableImages[$imageIndex % count($availableImages)];
                $imageIndex++;

                $noteItems[] = [
                    'format' => 'image',
                    'title' => null,
                    'source' => "resources/images/{$imageFile}",
                ];
            }

            Resource::create(array_merge([
                'node_id' => $node->id,
                'user_id' => $user->id,
                'resource_type' => 'note',
                'title' => "{$node->name} সম্পূর্ণ অধ্যায়ভিত্তিক হ্যান্ডনোট",
                'description' => "{$subjectName} বিষয়ের '{$node->name}' অধ্যায়ের সকল গুরুত্বপূর্ণ সূত্র, নিয়ম ও বিস্তারিত সমাধানসহ সম্পূর্ণ হ্যান্ডনোট। পরীক্ষার আগে দ্রুত রিভিশনের জন্য সহায়ক।",
                'items' => $noteItems,
            ], $getRandomStatus()));

            // 2. Video Lecture Course (format = 'video')
            $lectureCount = rand(3, 5);
            $lectureTitles = [
                'লেকচার ০১: প্রাথমিক ধারণা ও বেসিক আলোচনা',
                'লেকচার ০২: গাণিতিক সূত্রাবলি ও টাইপভিত্তিক সমস্যা',
                'লেকচার ০৩: জটিল সমস্যার সহজ সমাধান',
                'লেকচার ০৪: বোর্ড প্রশ্ন ও বিগত বছরের প্রশ্ন সমাধান',
                'লেকচার ০৫: এমসিকিউ শর্টকাট ও সুপার ট্রিকস',
            ];

            $videoItems = [];
            for ($lec = 0; $lec < $lectureCount; $lec++) {
                $videoUrl = $youtubeUrls[$videoIndex % count($youtubeUrls)];
                $videoIndex++;

                $videoItems[] = [
                    'format' => 'video',
                    'title' => $lectureTitles[$lec] ?? 'লেকচার ০'.($lec + 1),
                    'source' => $videoUrl,
                ];
            }

            Resource::create(array_merge([
                'node_id' => $node->id,
                'user_id' => $user->id,
                'resource_type' => 'video',
                'title' => "{$node->name} পূর্ণাঙ্গ মাস্টারক্লাস ও কনসেপ্ট ক্লিয়ারিং",
                'description' => "অভিজ্ঞ শিক্ষক দ্বারা পরিচালিত '{$node->name}' অধ্যায়ের ধারাবাহিক ভিডিও লেকচার। টপিকভিত্তিক আলোচনা ও বোর্ড প্রশ্ন সমাধান কাভার করা হয়েছে।",
                'items' => $videoItems,
            ], $getRandomStatus()));

            // 3. Highlighted Textbook / Note in PDF Format (format = 'pdf')
            $pdfUrl = $pdfUrls[array_rand($pdfUrls)];
            Resource::create(array_merge([
                'node_id' => $node->id,
                'user_id' => $user->id,
                'resource_type' => 'note',
                'title' => "{$node->name} দাগানো মূল বই ও গুরুত্বপূর্ণ পয়েন্ট (PDF)",
                'description' => 'বোর্ড পরীক্ষার জন্য সবচেয়ে গুরুত্বপূর্ণ লাইন এবং জ্ঞানমূলক ও অনুধাবনমূলক প্রশ্নের জন্য দাগানো পাঠ্যবই।',
                'items' => [
                    [
                        'format' => 'pdf',
                        'title' => 'দাগানো পাঠ্যবই (PDF)',
                        'source' => $pdfUrl,
                    ],
                ],
            ], $getRandomStatus()));

            // 4. Practical / Lab Notebook (format = 'pdf')
            if ($node->id % 2 === 0) {
                Resource::create(array_merge([
                    'node_id' => $node->id,
                    'user_id' => $user->id,
                    'resource_type' => 'practical',
                    'title' => "{$node->name} ব্যবহারিক নোটবুক ও এক্সপেরিমেন্ট চিত্র",
                    'description' => 'ল্যাব ক্লাসের জন্য সম্পূর্ণ ব্যবহারিক খাতা, এক্সপেরিমেন্ট ডেটা টেবিল ও সঠিক চিত্রাঙ্কনসহ সমাধান।',
                    'items' => [
                        [
                            'format' => 'pdf',
                            'title' => 'ব্যবহারিক নির্দেশিকা ও ল্যাব শিট (PDF)',
                            'source' => $pdfUrl,
                        ],
                    ],
                ], $getRandomStatus()));
            }

            // 5. Other Resources (format = 'pdf')
            if ($node->id % 3 === 0) {
                Resource::create(array_merge([
                    'node_id' => $node->id,
                    'user_id' => $user->id,
                    'resource_type' => 'other',
                    'title' => "{$node->name} বিগত ৫ বছরের বোর্ড প্রশ্ন ব্যাংক ও সাজেশন",
                    'description' => 'বিগত বছরের সকল বোর্ডের গুরুত্বপূর্ণ সৃজনশীল ও বহুনির্বাচনী প্রশ্নের সাজানো সংগ্রহ।',
                    'items' => [
                        [
                            'format' => 'pdf',
                            'title' => 'প্রশ্নব্যাংক ও সাজেশন বুকলেট (PDF)',
                            'source' => $pdfUrl,
                        ],
                    ],
                ], $getRandomStatus()));
            }
        }
    }
}
