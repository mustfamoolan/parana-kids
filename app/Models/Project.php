<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_type',
        'treasury_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        //
    ];

    /**
     * Get the treasury for this project
     */
    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    /**
     * Get all investments for this project
     */
    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * Get the user who created this project
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all investors through investments
     */
    public function investors()
    {
        return $this->hasManyThrough(
            Investor::class,
            InvestmentInvestor::class,
            'investment_id', // Foreign key on investment_investors table
            'id', // Foreign key on investors table
            'id', // Local key on projects table
            'investor_id' // Local key on investment_investors table
        )->distinct();
    }

    /**
     * Get total value of all investments in this project
     */
    public function getTotalValue(): float
    {
        return $this->investments()->sum('total_value');
    }

    /**
     * Get total investment amount from all investors
     * يُحسب من cost_percentage و total_value مباشرة لضمان الدقة 100%
     */
    public function getTotalInvestment(): float
    {
        $total = 0;
        $investmentIds = $this->investments()->pluck('id');
        $investments = Investment::whereIn('id', $investmentIds)->with('investors')->get();
        
        foreach ($investments as $investment) {
            foreach ($investment->investors as $investmentInvestor) {
                $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                $total += ($costPercentage / 100) * $investment->total_value;
            }
        }
        
        return $total;
    }

    /**
     * Get expected profit for all investors
     */
    public function getExpectedProfit(): float
    {
        // This would need to be calculated based on expected profit from products/warehouses
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get admin profit from this project
     */
    public function getAdminProfit(): float
    {
        $investmentIds = $this->investments()->pluck('id');
        $totalAdminPercentage = $this->investments()->sum('admin_profit_percentage');
        
        // Calculate based on total value and admin percentage
        $totalValue = $this->getTotalValue();
        return ($totalAdminPercentage / 100) * $totalValue;
    }

    /**
     * Check if project is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
