<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_type',
        'amount',
        'salary_amount',
        'expense_date',
        'person_name',
        'user_id',
        'product_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'salary_amount' => 'decimal:2',
    ];

    /**
     * Get the user who created this expense
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user associated with this expense (for salaries)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the product associated with this expense (for promotions)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get expense type name in Arabic
     */
    public function getExpenseTypeNameAttribute()
    {
        return match($this->expense_type) {
            'rent' => 'إيجار',
            'salary' => 'رواتب',
            'other' => 'صرفيات أخرى',
            'promotion' => 'ترويج',
            default => $this->expense_type,
        };
    }

    /**
     * Get person name (from user or person_name field)
     */
    public function getPersonDisplayNameAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->person_name ?? '-';
    }

    /**
     * Scope to filter by expense type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('expense_type', $type);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
