<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'message',
        'type',
        'order_id',
        'product_id',
        'image_path',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the conversation this message belongs to
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with this message (if type is 'order')
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with this message (if type is 'product')
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        // استخدام Storage::url() الذي يعمل مع local و S3/Bucket
        try {
            return Storage::disk('public')->url($this->image_path);
        } catch (\Exception $e) {
            // Fallback إلى asset() إذا فشل Storage::url()
            return asset('storage/' . $this->image_path);
        }
    }

    /**
     * Check if message has an image
     */
    public function hasImage()
    {
        return !empty($this->image_path);
    }
}
