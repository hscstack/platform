<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ResourceChangeRequest extends Model
{
    use Prunable;

    protected $fillable = [
        'user_id',
        'resource_id',
        'node_id',
        'action_type',
        'status',
        'payload',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    /**
     * Get the prunable model query.
     * Prunes approved and rejected change requests older than 30 days.
     */
    public function prunable()
    {
        return static::whereIn('status', ['approved', 'rejected'])
            ->where('reviewed_at', '<=', now()->subDays(30));
    }

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'staged_file_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStagedFileUrlAttribute(): ?string
    {
        $filePath = $this->payload['file_path'] ?? null;

        if ($filePath) {
            return Storage::url($filePath);
        }

        return null;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Sanitize and whitelist only valid resource attributes.
     */
    public static function sanitizePayload(array $data): array
    {
        return [
            'node_id' => $data['node_id'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'file_path' => $data['file_path'] ?? null,
        ];
    }

    public static function recordCreate(int $userId, int $nodeId, array $data): self
    {
        $data['node_id'] = $nodeId;

        return self::create([
            'user_id' => $userId,
            'node_id' => $nodeId,
            'action_type' => 'create',
            'status' => 'pending',
            'payload' => self::sanitizePayload($data),
        ]);
    }

    public static function recordUpdate(int $userId, Resource $resource, array $data): self
    {
        if (! isset($data['file_path']) && $resource->file_path) {
            $data['file_path'] = $resource->file_path;
        }

        $payload = self::sanitizePayload($data);

        return self::create([
            'user_id' => $userId,
            'resource_id' => $resource->id,
            'node_id' => $payload['node_id'] ?? $resource->node_id,
            'action_type' => 'update',
            'status' => 'pending',
            'payload' => $payload,
        ]);
    }

    public static function recordDelete(int $userId, Resource $resource): self
    {
        return self::create([
            'user_id' => $userId,
            'resource_id' => $resource->id,
            'node_id' => $resource->node_id,
            'action_type' => 'delete',
            'status' => 'pending',
            'payload' => null,
        ]);
    }
}
