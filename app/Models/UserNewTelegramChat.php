<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNewTelegramChat extends Model
{
    use HasFactory;

    protected $table = 'user_new_telegram_chats';

    protected $fillable = [
        'user_id',
        'chat_id',
        'device_name',
        'linked_at',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
    ];

    /**
     * Get the user that owns this telegram chat
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
