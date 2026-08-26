<?php

namespace App\Http\Controllers;

use App\Mail\NodeNotificationMail;
use App\Models\Node;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class NodeController extends Controller
{
    public function show(Subject $subject, $path)
    {
        $slugs = explode('/', trim($path, '/'));

        $node = null;
        $parent = null;

        foreach ($slugs as $slug) {
            $query = Node::where('subject_id', $subject->id)
                ->where('slug', $slug);

            if ($parent) {
                $query->where('parent_id', $parent->id);
            } else {
                $query->whereNull('parent_id');
            }

            $node = $query->firstOrFail();
            $parent = $node;
        }

        $nodes = Cache::remember("node_children_{$node->id}", now()->addDay(), function () use ($node) {
            return Node::where('parent_id', $node->id)
                ->withCount(['children', 'resources', 'upvotes', 'downvotes'])
                ->orderByRaw('(upvotes_count - downvotes_count) DESC')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug'])->toArray();
        });

        $resources = Cache::remember("node_resources_{$node->id}", now()->addDay(), function () use ($node) {
            return $node->resources()->get()->toArray();
        });

        $upvotesCount = $node->upvotes()->count();
        $downvotesCount = $node->downvotes()->count();
        $userVote = auth()->check()
            ? $node->votes()->where('user_id', auth()->id())->value('type')
            : null;

        $upvoters = $node->upvotes()
            ->with(['user:id,name,username,image_path,institution', 'user.roles:id,name'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        return Inertia::render('Node', [
            'subject' => $subject,
            'currentNode' => [
                'id' => $node->id,
                'name' => $node->name,
                'slug' => $node->slug,
            ],
            'nodes' => $nodes,
            'breadcrumb' => Cache::remember("node_breadcrumb_{$node->id}", now()->addDay(), function () use ($node) {
                return $this->buildBreadcrumb($node);
            }),
            'resources' => $resources,
            'upvotesCount' => $upvotesCount,
            'downvotesCount' => $downvotesCount,
            'userVote' => $userVote,
            'upvoters' => $upvoters,
        ]);
    }

    public function vote(Request $request, Node $node)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:up,down'],
        ]);

        $user = auth()->user();
        $existing = $node->votes()->where('user_id', $user->id)->first();
        $isNewUpvote = false;

        if ($existing) {
            if ($existing->type === $validated['type']) {
                $existing->delete();
            } else {
                $existing->update(['type' => $validated['type']]);
                if ($validated['type'] === 'up') {
                    $isNewUpvote = true;
                }
            }
        } else {
            $node->votes()->create([
                'user_id' => $user->id,
                'type' => $validated['type'],
            ]);
            if ($validated['type'] === 'up') {
                $isNewUpvote = true;
            }
        }

        if ($isNewUpvote) {
            $upvotesCount = $node->upvotes()->count();
            $milestones = [1, 5, 10, 25, 50, 100, 250, 500, 1000];
            $isMilestone = in_array($upvotesCount, $milestones, true) || ($upvotesCount > 1000 && $upvotesCount % 500 === 0);

            if ($isMilestone) {
                $node->loadMissing('user:id,name,email,receive_emails', 'subject:id,name,slug', 'parent:id,name,slug');

                if ($node->user && $node->user_id !== $user->id && $node->user->email && $node->user->receive_emails !== false) {
                    Mail::to($node->user->email)->queue(NodeNotificationMail::forUpvoteMilestone($node, $user, $upvotesCount));
                }
            }
        }

        return back();
    }

    private function buildBreadcrumb($node)
    {
        $breadcrumb = [];

        while ($node) {
            array_unshift($breadcrumb, [
                'name' => $node->name,
                'slug' => $node->slug,
            ]);

            $node = $node->parent;
        }

        return $breadcrumb;
    }
}
