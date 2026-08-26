<?php

namespace App\Observers;

use App\Models\NodeVote;
use Illuminate\Support\Facades\Cache;

class NodeVoteObserver
{
    public function created(NodeVote $nodeVote): void
    {
        $this->clearVoteCache($nodeVote);
    }

    public function updated(NodeVote $nodeVote): void
    {
        $this->clearVoteCache($nodeVote);
    }

    public function deleted(NodeVote $nodeVote): void
    {
        $this->clearVoteCache($nodeVote);
    }

    private function clearVoteCache(NodeVote $nodeVote): void
    {
        $node = $nodeVote->node;
        if (! $node) {
            return;
        }

        if ($node->parent_id) {
            Cache::forget("node_children_{$node->parent_id}");
        } else {
            Cache::forget("subject_page_{$node->subject_id}");
        }
    }
}
