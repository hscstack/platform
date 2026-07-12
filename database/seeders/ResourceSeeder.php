<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $nodes = DB::table('nodes')->get();

        foreach ($nodes as $node) {

            DB::table('resources')->insert([
                [
                    'node_id' => $node->id,
                    'user_id' => 1,
                    'resource_type' => 'note',
                    'title' => $node->name . ' - অধ্যায়ভিত্তিক নোট',
                    'content' => $node->name . ' অধ্যায়ের গুরুত্বপূর্ণ তত্ত্ব, সূত্র ও আলোচনা।',
                    'file_url' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'node_id' => $node->id,
                    'user_id' => 1,
                    'resource_type' => 'pdf',
                    'title' => $node->name . ' - PDF হ্যান্ডনোট',
                    'content' => null,
                    'file_url' => 'https://www.africau.edu/images/default/sample.pdf',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'node_id' => $node->id,
                    'user_id' => 1,
                    'resource_type' => 'image',
                    'title' => $node->name . ' - চিত্র',
                    'content' => null,
                    'file_url' => 'https://picsum.photos/1200/800?random=' . $node->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'node_id' => $node->id,
                    'user_id' => 1,
                    'resource_type' => 'video',
                    'title' => $node->name . ' - ভিডিও লেকচার',
                    'content' => "https://www.youtube.com/embed/a3ICNMQW7Ok",
                    'file_url' => 'https://www.youtube.com/embed/a3ICNMQW7Ok',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
