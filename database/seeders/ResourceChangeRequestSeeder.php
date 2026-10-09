<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Resource;
use App\Models\ResourceChangeRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceChangeRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::role('admin')->first() ?? User::first();
        $contributor = User::where('id', '!=', $admin?->id)->first() ?? User::factory()->create();

        $node = Node::has('subject')->first() ?? Node::first();
        if (! $node) {
            $this->command->warn('No nodes available to seed change requests.');

            return;
        }

        // Fetch or create sample resources for update and delete requests
        $noteResource = Resource::where('resource_type', 'note')->first() ?? Resource::create([
            'node_id' => $node->id,
            'user_id' => $contributor->id,
            'resource_type' => 'note',
            'title' => 'Original Summary of Newton Mechanics',
            'content' => 'Newton second law states that F = dp/dt. When mass is constant, F = ma.',
        ]);

        $pdfResource = Resource::where('resource_type', 'pdf')->first() ?? Resource::create([
            'node_id' => $node->id,
            'user_id' => $contributor->id,
            'resource_type' => 'pdf',
            'title' => 'Calculus Formula Sheet 2024',
            'external_url' => 'https://example.com/calculus-formula-sheet.pdf',
        ]);

        $videoResource = Resource::where('resource_type', 'video')->first() ?? Resource::create([
            'node_id' => $node->id,
            'user_id' => $contributor->id,
            'resource_type' => 'video',
            'title' => 'Introduction to Organic Reactions',
            'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $deleteCandidateResource = Resource::whereNotIn('id', [$noteResource->id, $pdfResource->id, $videoResource->id])->first() ?? Resource::create([
            'node_id' => $node->id,
            'user_id' => $contributor->id,
            'resource_type' => 'note',
            'title' => 'Outdated Exam Routine 2021',
            'content' => 'Routine for 2021 HSC batch. No longer relevant.',
        ]);

        // 1. Pending CREATE Requests
        // Note
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'node_id' => $node->id,
            'action_type' => 'create',
            'status' => 'pending',
            'payload' => [
                'node_id' => $node->id,
                'resource_type' => 'note',
                'title' => 'Photosynthesis Light & Dark Reaction Summary Notes',
                'content' => "### Photosynthesis Key Points\n\n- **Light Dependent Phase**: Occurs in thylakoid membranes.\n- **Light Independent Phase (Calvin Cycle)**: Occurs in the stroma.\n- Key enzyme: RuBisCO.",
                'external_url' => null,
                'file_path' => null,
            ],
        ]);

        // PDF
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'node_id' => $node->id,
            'action_type' => 'create',
            'status' => 'pending',
            'payload' => [
                'node_id' => $node->id,
                'resource_type' => 'pdf',
                'title' => 'Thermodynamics Formulas & Chapter Practice PDF',
                'content' => null,
                'external_url' => 'https://drive.google.com/file/d/1sample_pdf_drive_link/view',
                'file_path' => null,
            ],
        ]);

        // Video
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'node_id' => $node->id,
            'action_type' => 'create',
            'status' => 'pending',
            'payload' => [
                'node_id' => $node->id,
                'resource_type' => 'video',
                'title' => 'Complete Matrices and Determinants Masterclass (Bangla)',
                'content' => null,
                'external_url' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                'file_path' => null,
            ],
        ]);

        // Image
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'node_id' => $node->id,
            'action_type' => 'create',
            'status' => 'pending',
            'payload' => [
                'node_id' => $node->id,
                'resource_type' => 'image',
                'title' => 'Conic Sections - Hyperbola and Ellipse Geometric Diagram',
                'content' => null,
                'external_url' => null,
                'file_path' => 'resources/images/sample-conic-diagram.png',
            ],
        ]);

        // 2. Pending UPDATE Requests
        // Note update
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'resource_id' => $noteResource->id,
            'node_id' => $noteResource->node_id,
            'action_type' => 'update',
            'status' => 'pending',
            'payload' => [
                'node_id' => $noteResource->node_id,
                'resource_type' => 'note',
                'title' => 'Newtonian Mechanics - Comprehensive Derivations & Solved Problems',
                'content' => "### Comprehensive Newtonian Mechanics\n\n1. Momentum conservation: m1*u1 + m2*u2 = m1*v1 + m2*v2\n2. Friction: f_k = mu_k * N\n3. Centripetal Force: F_c = m * v^2 / r",
                'external_url' => null,
                'file_path' => null,
            ],
        ]);

        // PDF update
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'resource_id' => $pdfResource->id,
            'node_id' => $pdfResource->node_id,
            'action_type' => 'update',
            'status' => 'pending',
            'payload' => [
                'node_id' => $pdfResource->node_id,
                'resource_type' => 'pdf',
                'title' => 'Calculus Formula Sheet 2026 (Updated with Integration Rules)',
                'content' => null,
                'external_url' => 'https://example.com/calculus-formula-sheet-2026-revised.pdf',
                'file_path' => null,
            ],
        ]);

        // Video update
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'resource_id' => $videoResource->id,
            'node_id' => $videoResource->node_id,
            'action_type' => 'update',
            'status' => 'pending',
            'payload' => [
                'node_id' => $videoResource->node_id,
                'resource_type' => 'video',
                'title' => 'Organic Chemistry - Electrophilic Aromatic Substitution (HD Remastered)',
                'content' => null,
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'file_path' => null,
            ],
        ]);

        // 3. Pending DELETE Request
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'resource_id' => $deleteCandidateResource->id,
            'node_id' => $deleteCandidateResource->node_id,
            'action_type' => 'delete',
            'status' => 'pending',
            'payload' => null,
        ]);

        // 4. APPROVED Request (history check)
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'node_id' => $node->id,
            'action_type' => 'create',
            'status' => 'approved',
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now()->subDay(),
            'payload' => [
                'node_id' => $node->id,
                'resource_type' => 'pdf',
                'title' => 'HSC English 1st Paper Flowchart and Summary Guidelines',
                'content' => null,
                'external_url' => 'https://example.com/english-1st-paper-guide.pdf',
                'file_path' => null,
            ],
        ]);

        // 5. REJECTED Request (history check)
        ResourceChangeRequest::create([
            'user_id' => $contributor->id,
            'node_id' => $node->id,
            'action_type' => 'create',
            'status' => 'rejected',
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now()->subHours(6),
            'rejection_reason' => 'The provided Google Drive link requires permission to view. Please set link sharing to "Anyone with the link can view" and resubmit.',
            'payload' => [
                'node_id' => $node->id,
                'resource_type' => 'pdf',
                'title' => 'Inaccessible Question Bank PDF',
                'content' => null,
                'external_url' => 'https://drive.google.com/file/d/private-file-id/view',
                'file_path' => null,
            ],
        ]);
    }
}
