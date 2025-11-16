<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'type',
        'title',
    ];

    /**
     * Get the warehouse this conversation belongs to
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get all participants in this conversation
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    /**
     * Get all messages in this conversation
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in this conversation
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get unread messages count for a specific user
     */
    public function unreadCount($userId)
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        if (!$participant || !$participant->pivot->last_read_at) {
            return $this->messages()->count();
        }

        return $this->messages()
                    ->where('created_at', '>', $participant->pivot->last_read_at)
                    ->where('user_id', '!=', $userId)
                    ->count();
    }

    /**
     * Get the other participant in a direct conversation
     */
    public function getOtherParticipant($userId)
    {
        return $this->participants()->where('user_id', '!=', $userId)->first();
    }
}
