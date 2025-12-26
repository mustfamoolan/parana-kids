<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ProfitCalculator;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id', // nullable للبنية الجديدة
        'project_id', // للمشروع
        'investment_type',
        'product_id',
        'warehouse_id',
        'private_warehouse_id',
        'profit_percentage', // للبنية القديمة
        'investment_amount', // للبنية القديمة
        'admin_profit_percentage',
        'total_value',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'profit_percentage' => 'decimal:2',
        'investment_amount' => 'decimal:2',
        'admin_profit_percentage' => 'decimal:2',
        'total_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the investor (for backward compatibility)
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * Get all targets for this investment (new structure)
     */
    public function targets()
    {
        return $this->hasMany(InvestmentTarget::class);
    }

    /**
     * Get all investors for this investment (new structure)
     */
    public function investors()
    {
        return $this->hasMany(InvestmentInvestor::class);
    }

    /**
     * Get the product (if investment type is product)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse (if investment type is warehouse)
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the private warehouse (if investment type is private_warehouse)
     */
    public function privateWarehouse()
    {
        return $this->belongsTo(PrivateWarehouse::class, 'private_warehouse_id');
    }

    /**
     * Get the project (if investment belongs to a project)
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get all profits from this investment
     */
    public function profits()
    {
        return $this->hasMany(InvestorProfit::class);
    }

    /**
     * Get the user who created this investment
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if investment is active
     */
    public function isActive()
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Get target type (product/warehouse/private_warehouse)
     */
    public function getTargetType()
    {
        return $this->investment_type;
    }

    /**
     * Get target ID
     */
    public function getTargetId()
    {
        return match($this->investment_type) {
            'product' => $this->product_id,
            'warehouse' => $this->warehouse_id,
            'private_warehouse' => $this->private_warehouse_id,
            default => null,
        };
    }

    /**
     * Get expected profit for this investment
     * يحسب الربح المتوقع من جميع المخازن المرتبطة بهذا الاستثمار
     */
    public function getExpectedProfit(): float
    {
        $expectedProfit = 0;
        
        // جلب جميع المخازن المرتبطة بهذا الاستثمار
        $warehouseTargets = $this->targets()->where('target_type', 'warehouse')->get();
        
        if ($warehouseTargets->isEmpty()) {
            return 0;
        }
        
        $profitCalculator = app(\App\Services\ProfitCalculator::class);
        
        foreach ($warehouseTargets as $target) {
            $warehouse = Warehouse::find($target->target_id);
            if ($warehouse) {
                $expectedProfit += $profitCalculator->calculateWarehouseExpectedProfit($warehouse);
            }
        }
        
        return $expectedProfit;
    }

    /**
     * Validate percentages for a target (updated for new structure)
     */
    public static function validatePercentages($targetType, $targetId, $newPercentage, $excludeInvestmentId = null, $adminPercentage = 0)
    {
        // البحث عن الاستثمارات التي تحتوي على هذا الهدف
        $investmentIds = InvestmentTarget::where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->pluck('investment_id');

        $query = self::whereIn('id', $investmentIds)
            ->where('status', 'active');

        if ($excludeInvestmentId) {
            $query->where('id', '!=', $excludeInvestmentId);
        }

        // حساب مجموع النسب من investment_investors
        $existingInvestorPercentage = InvestmentInvestor::whereIn('investment_id', $query->pluck('id'))
            ->sum('profit_percentage');

        // حساب مجموع نسب المدير
        $existingAdminPercentage = $query->sum('admin_profit_percentage');

        $totalPercentage = $existingInvestorPercentage + $existingAdminPercentage + $newPercentage + $adminPercentage;

        return [
            'total' => $totalPercentage,
            'is_valid' => $totalPercentage <= 100,
            'remaining' => max(0, 100 - $totalPercentage),
            'existing' => $existingInvestorPercentage + $existingAdminPercentage,
            'existing_investor' => $existingInvestorPercentage,
            'existing_admin' => $existingAdminPercentage,
        ];
    }
}
