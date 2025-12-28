<?php

/**
 * Script لإزالة المعاملات الخاطئة من خزنة المشروع
 * هذه المعاملات تم تسجيلها للطلبات من مخازن بدون استثمارات نشطة
 * 
 * تشغيل: php fix_old_project_transactions.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\TreasuryTransaction;
use App\Models\Order;
use App\Models\Investment;
use App\Models\InvestmentTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "بدء تنظيف المعاملات الخاطئة من خزنات المشاريع...\n\n";

// جلب جميع المشاريع
$projects = Project::with('treasury')->get();

$totalDeleted = 0;
$processedProjects = 0;

foreach ($projects as $project) {
    if (!$project->treasury) {
        continue;
    }
    
    echo "فحص المشروع: {$project->name} (ID: {$project->id})\n";
    
    // جلب جميع معاملات البيع في خزنة المشروع
    $saleTransactions = $project->treasury->transactions()
        ->where('reference_type', 'order')
        ->where('transaction_type', 'deposit')
        ->where('description', 'like', 'بيع طلب #%')
        ->get();
    
    $deletedForProject = 0;
    
    foreach ($saleTransactions as $transaction) {
        $orderId = $transaction->reference_id;
        $order = Order::with('items.product')->find($orderId);
        
        if (!$order) {
            echo "  - الطلب #{$orderId} غير موجود، تخطي...\n";
            continue;
        }
        
        // التحقق من أن الطلب يحتوي على منتجات من مخازن/منتجات بها استثمارات نشطة لهذا المشروع
        $hasValidInvestment = false;
        
        foreach ($order->items as $item) {
            if (!$item->product) {
                continue;
            }
            
            $productId = $item->product_id;
            $warehouseId = $item->product->warehouse_id;
            
            // التحقق من وجود استثمار نشط للمنتج مرتبط بهذا المشروع
            $hasProductInvestment = InvestmentTarget::where('target_type', 'product')
                ->where('target_id', $productId)
                ->whereHas('investment', function($q) use ($project) {
                    $q->where('status', 'active')
                      ->where('start_date', '<=', now())
                      ->where(function($q2) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', now());
                      })
                      ->where('project_id', $project->id)
                      ->whereHas('investors');
                })
                ->exists();
            
            if ($hasProductInvestment) {
                $hasValidInvestment = true;
                break;
            }
            
            // التحقق من وجود استثمار نشط للمخزن مرتبط بهذا المشروع
            $hasWarehouseInvestment = InvestmentTarget::where('target_type', 'warehouse')
                ->where('target_id', $warehouseId)
                ->whereHas('investment', function($q) use ($project) {
                    $q->where('status', 'active')
                      ->where('start_date', '<=', now())
                      ->where(function($q2) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', now());
                      })
                      ->where('project_id', $project->id)
                      ->whereHas('investors');
                })
                ->exists();
            
            if ($hasWarehouseInvestment) {
                $hasValidInvestment = true;
                break;
            }
        }
        
        // إذا لم يكن هناك استثمار صالح، احذف المعاملة
        if (!$hasValidInvestment) {
            echo "  - حذف معاملة خاطئة: طلب #{$order->order_number} - مبلغ {$transaction->amount}\n";
            
            // تحديث رصيد الخزنة
            $project->treasury->balance -= $transaction->amount;
            $project->treasury->save();
            
            // حذف المعاملة
            $transaction->delete();
            
            $deletedForProject++;
            $totalDeleted++;
        }
    }
    
    if ($deletedForProject > 0) {
        echo "  ✓ تم حذف {$deletedForProject} معاملة خاطئة من هذا المشروع\n";
    } else {
        echo "  ✓ لا توجد معاملات خاطئة\n";
    }
    
    $processedProjects++;
    echo "\n";
}

echo "===========================================\n";
echo "انتهى التنظيف:\n";
echo "- عدد المشاريع المعالجة: {$processedProjects}\n";
echo "- إجمالي المعاملات المحذوفة: {$totalDeleted}\n";
echo "===========================================\n";

