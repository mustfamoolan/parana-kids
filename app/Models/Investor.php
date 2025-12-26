<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'balance',
        'total_profit',
        'total_withdrawals',
        'total_deposits',
        'notes',
        'status',
        'is_admin',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'is_admin' => 'boolean',
    ];

    /**
     * Hash password before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($investor) {
            if (isset($investor->password)) {
                // إذا كانت كلمة المرور غير hashed، قم بhashها
                // Hash::needsRehash يعيد true إذا كانت كلمة المرور hashed وتحتاج rehash
                // إذا كانت كلمة المرور غير hashed (نص عادي)، نحتاج hashها
                // نتحقق إذا كانت كلمة المرور تبدو كـ hash (تبدأ بـ $2y$ أو $2a$ أو $2b$)
                $isHashed = str_starts_with($investor->password, '$2y$') || 
                           str_starts_with($investor->password, '$2a$') || 
                           str_starts_with($investor->password, '$2b$');
                
                if (!$isHashed) {
                    $investor->password = Hash::make($investor->password);
                } elseif (Hash::needsRehash($investor->password)) {
                    $investor->password = Hash::make($investor->password);
                }
            }
        });

        static::updating(function ($investor) {
            if ($investor->isDirty('password')) {
                // التحقق إذا كانت كلمة المرور غير hashed
                $isHashed = str_starts_with($investor->password, '$2y$') || 
                           str_starts_with($investor->password, '$2a$') || 
                           str_starts_with($investor->password, '$2b$');
                
                if (!$isHashed) {
                    $investor->password = Hash::make($investor->password);
                } elseif (Hash::needsRehash($investor->password)) {
                    $investor->password = Hash::make($investor->password);
                }
            }
        });
    }

    /**
     * Get all investments for this investor
     */
    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * Get all profits for this investor
     */
    public function profits()
    {
        return $this->hasMany(InvestorProfit::class);
    }

    /**
     * Get all transactions for this investor
     */
    public function transactions()
    {
        return $this->hasMany(InvestorTransaction::class);
    }

    /**
     * Get deposits
     */
    public function deposits()
    {
        return $this->transactions()->where('transaction_type', 'deposit');
    }

    /**
     * Get withdrawals
     */
    public function withdrawals()
    {
        return $this->transactions()->where('transaction_type', 'withdrawal');
    }

    /**
     * Get the treasury associated with this investor
     */
    public function treasury()
    {
        return $this->hasOne(Treasury::class);
    }

    /**
     * Check if investor is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    // Note: total_profit, total_withdrawals, and total_deposits are stored directly in the database
    // and updated automatically when transactions/profits are created

    /**
     * Get current balance
     */
    public function getCurrentBalance()
    {
        return $this->balance;
    }

    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Scope for admin investor
     */
    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Get or create admin investor
     */
    public static function getOrCreateAdminInvestor(): self
    {
        $adminInvestor = self::where('is_admin', true)->with('treasury')->first();
        
        if (!$adminInvestor) {
            // كلمة المرور سيتم hashها تلقائياً في boot() method
            $adminInvestor = self::create([
                'name' => 'المدير',
                'phone' => 'admin_' . time(),
                'password' => 'admin', // سيتم hashها تلقائياً
                'is_admin' => true,
                'status' => 'active',
            ]);
            
            // إنشاء خزنة للمدير
            $treasury = \App\Models\Treasury::create([
                'name' => 'خزنة المدير',
                'initial_capital' => 0,
                'current_balance' => 0,
                'investor_id' => $adminInvestor->id,
                'created_by' => auth()->id() ?? 1,
            ]);
        } elseif (!$adminInvestor->treasury) {
            // إذا كان المستثمر موجوداً لكن لا يملك خزنة، إنشاء خزنة له
            $treasury = \App\Models\Treasury::create([
                'name' => 'خزنة المدير',
                'initial_capital' => 0,
                'current_balance' => 0,
                'investor_id' => $adminInvestor->id,
                'created_by' => auth()->id() ?? 1,
            ]);
            $adminInvestor->load('treasury');
        }
        
        return $adminInvestor;
    }
}
