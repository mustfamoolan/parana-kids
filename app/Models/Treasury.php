<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Treasury extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'initial_capital',
        'current_balance',
        'notes',
        'created_by',
        'investor_id',
    ];

    protected $casts = [
        'initial_capital' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    /**
     * Get all transactions for this treasury
     */
    public function transactions()
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    /**
     * Get the user who created this treasury
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the investor associated with this treasury
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * Deposit amount to treasury
     */
    public function deposit(float $amount, string $referenceType = null, int $referenceId = null, string $description = null, int $createdBy = null): TreasuryTransaction
    {
        return DB::transaction(function () use ($amount, $referenceType, $referenceId, $description, $createdBy) {
            $this->increment('current_balance', $amount);

            return TreasuryTransaction::create([
                'treasury_id' => $this->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $createdBy ?? auth()->id(),
            ]);
        });
    }

    /**
     * Withdraw amount from treasury
     * يسمح بالذهاب للسالب (للمصروفات وتكاليف المنتجات)
     */
    public function withdraw(float $amount, string $referenceType = null, int $referenceId = null, string $description = null, int $createdBy = null): TreasuryTransaction
    {
        return DB::transaction(function () use ($amount, $referenceType, $referenceId, $description, $createdBy) {
            // السماح بالذهاب للسالب للمصروفات وتكاليف المنتجات
            $this->decrement('current_balance', $amount);

            return TreasuryTransaction::create([
                'treasury_id' => $this->id,
                'transaction_type' => 'withdrawal',
                'amount' => $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_by' => $createdBy ?? auth()->id(),
            ]);
        });
    }

    /**
     * Get current balance
     */
    public function getBalance(): float
    {
        return (float) $this->current_balance;
    }

    /**
     * Get the default treasury (first one that is not linked to an investor, or create if not exists)
     */
    public static function getDefault(): self
    {
        // جلب أول خزنة ليست مرتبطة بمستثمر
        $treasury = self::whereNull('investor_id')
            ->orderBy('id', 'asc')
            ->first();
        
        if (!$treasury) {
            $treasury = self::create([
                'name' => 'الخزنة الرئيسية',
                'initial_capital' => 0,
                'current_balance' => 0,
                'investor_id' => null, // التأكد من أنها ليست مرتبطة بمستثمر
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        return $treasury;
    }

    /**
     * Create a treasury for an investor
     */
    public static function createForInvestor(string $name, float $initialCapital, int $createdBy = null): self
    {
        return self::create([
            'name' => $name,
            'initial_capital' => $initialCapital,
            'current_balance' => $initialCapital,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }
}
