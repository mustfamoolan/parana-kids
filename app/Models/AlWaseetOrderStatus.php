<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlWaseetOrderStatus extends Model
{
    protected $table = 'alwaseet_order_statuses';

    protected $fillable = [
        'status_id',
        'status_text',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all active statuses ordered by display_order
     */
    public static function getActiveStatuses()
    {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('status_text')
            ->get();
    }

    /**
     * Sync statuses from API
     */
    public static function syncFromApi(array $statuses)
    {
        foreach ($statuses as $status) {
            if (isset($status['id']) && isset($status['status'])) {
                static::updateOrCreate(
                    ['status_id' => (string)$status['id']],
                    [
                        'status_text' => $status['status'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
