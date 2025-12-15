<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'code',
        'phone',
        'page_name',
        'private_warehouse_id',
        'profile_image',
        'telegram_chat_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is supplier (regular supplier, not private supplier)
     */
    public function isSupplier()
    {
        return $this->role === 'supplier';
    }

    /**
     * Check if user is private supplier (مورد)
     */
    public function isPrivateSupplier()
    {
        return $this->role === 'private_supplier';
    }

    /**
     * Check if user is regular supplier (مجهز) - supplier without private warehouse
     */
    public function isRegularSupplier()
    {
        return $this->role === 'supplier';
    }

    /**
     * Check if user is delegate
     */
    public function isDelegate()
    {
        return $this->role === 'delegate';
    }

    /**
     * Check if user is admin or supplier (includes both regular and private suppliers)
     */
    public function isAdminOrSupplier()
    {
        return $this->isAdmin() || $this->isSupplier() || $this->isPrivateSupplier();
    }

    /**
     * Get all warehouses this user has access to
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_user')
                    ->withPivot('can_manage')
                    ->withTimestamps();
    }

    /**
     * Get warehouses this user can manage
     */
    public function manageableWarehouses()
    {
        return $this->warehouses()->wherePivot('can_manage', true);
    }

    /**
     * Get all products created by this user
     */
    public function createdProducts()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    /**
     * Check if user can access a specific warehouse
     */
    public function canAccessWarehouse($warehouseId)
    {
        return $this->warehouses()->where('warehouse_id', $warehouseId)->exists();
    }

    /**
     * Check if user can manage a specific warehouse
     */
    public function canManageWarehouse($warehouseId)
    {
        return $this->warehouses()
                    ->where('warehouse_id', $warehouseId)
                    ->wherePivot('can_manage', true)
                    ->exists();
    }

    /**
     * Get all carts for this delegate
     */
    public function carts()
    {
        return $this->hasMany(Cart::class, 'delegate_id');
    }

    /**
     * Get all orders for this delegate
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'delegate_id');
    }

    /**
     * Get the private warehouse assigned to this user
     */
    public function privateWarehouse()
    {
        return $this->belongsTo(PrivateWarehouse::class, 'private_warehouse_id');
    }

    /**
     * Get all conversations this user is part of
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot('last_read_at')
                    ->withTimestamps()
                    ->orderBy('updated_at', 'desc');
    }

    /**
     * Get all messages sent by this user
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get users that share warehouses with this user
     * الآن يعيد جميع المستخدمين (إزالة قيد المخزن المشترك)
     */
    public function getUsersWithSharedWarehouses()
    {
        // إرجاع جميع المستخدمين (باستثناء المستخدم الحالي)
        return User::where('id', '!=', $this->id)->get();
    }

    /**
     * Get profile image URL or return default image
     */
    public function getProfileImageUrl()
    {
        if ($this->profile_image) {
            // استخدام Storage::url() الذي يعمل مع local و S3/Bucket
            try {
                return Storage::disk('public')->url($this->profile_image);
            } catch (\Exception $e) {
                // Fallback إلى asset() إذا فشل Storage::url()
                return asset('storage/' . $this->profile_image);
            }
        }

        // Return default profile image based on user ID
        $defaultImageNumber = ($this->id % 20) + 1;
        return asset("assets/images/profile-{$defaultImageNumber}.jpeg");
    }

    /**
     * Get profile image URL attribute (accessor)
     */
    public function getProfileImageUrlAttribute()
    {
        return $this->getProfileImageUrl();
    }

    /**
     * Get all telegram chats for this user
     */
    public function telegramChats()
    {
        return $this->hasMany(UserTelegramChat::class);
    }

    /**
     * Check if user is linked to Telegram
     */
    public function isLinkedToTelegram()
    {
        return $this->telegramChats()->exists();
    }

    /**
     * Check if specific chat_id is linked to this user
     */
    public function isChatIdLinked($chatId)
    {
        return $this->telegramChats()->where('chat_id', $chatId)->exists();
    }

    /**
     * Link user to Telegram chat
     */
    public function linkToTelegram($chatId, $deviceName = null)
    {
        // إضافة أو تحديث chat_id في الجدول الجديد
        UserTelegramChat::updateOrCreate(
            ['user_id' => $this->id, 'chat_id' => $chatId],
            ['device_name' => $deviceName, 'linked_at' => now()]
        );

        // الحفاظ على العمود القديم للتوافق (نخزن آخر chat_id)
        $this->update(['telegram_chat_id' => $chatId]);
    }

    /**
     * Unlink specific telegram chat
     */
    public function unlinkFromTelegram($chatId = null)
    {
        if ($chatId) {
            // حذف chat_id محدد
            $this->telegramChats()->where('chat_id', $chatId)->delete();
        } else {
            // حذف جميع الأجهزة
            $this->telegramChats()->delete();
        }

        // تحديث العمود القديم
        $lastChat = $this->telegramChats()->latest()->first();
        $this->update(['telegram_chat_id' => $lastChat ? $lastChat->chat_id : null]);
    }

    /**
     * Get all chat IDs for this user
     */
    public function getTelegramChatIds()
    {
        return $this->telegramChats()->pluck('chat_id')->toArray();
    }
}
