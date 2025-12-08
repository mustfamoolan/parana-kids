<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlWaseetSyncLog extends Model
{
    protected $table = 'alwaseet_sync_logs';

    protected $fillable = [
        'type',
        'status',
        'orders_synced',
        'orders_updated',
        'orders_created',
        'orders_failed',
        'error_message',
        'filters',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Mark sync as completed
     */
    public function markCompleted(string $status = 'success'): void
    {
        $this->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }
}
