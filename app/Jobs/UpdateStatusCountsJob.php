<?php

namespace App\Jobs;

use App\Models\AlWaseetShipment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateStatusCountsJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // حساب statusCounts لكل delegate
            $this->updateDelegateStatusCounts();
            
            // حساب statusCounts للمدير والمجهز (بدون فلاتر)
            $this->updateAdminStatusCounts();
            
            Log::info('UpdateStatusCountsJob: Completed successfully');
        } catch (\Exception $e) {
            Log::error('UpdateStatusCountsJob: Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * حساب statusCounts لكل delegate
     */
    private function updateDelegateStatusCounts(): void
    {
        $delegates = User::where('role', 'delegate')->get();
        
        foreach ($delegates as $delegate) {
            try {
                // جلب جميع order IDs للمندوب
                $orderIds = Order::where('status', 'confirmed')
                    ->where('delegate_id', $delegate->id)
                    ->whereHas('alwaseetShipment')
                    ->pluck('id')
                    ->toArray();

                $statusCounts = [];
                
                if (!empty($orderIds)) {
                    // حساب عدد الطلبات لكل حالة مباشرة من قاعدة البيانات
                    $statusCountsFromDb = AlWaseetShipment::whereIn('order_id', $orderIds)
                        ->whereNotNull('status_id')
                        ->selectRaw('status_id, COUNT(*) as count')
                        ->groupBy('status_id')
                        ->get()
                        ->mapWithKeys(function($item) {
                            return [(string)$item->status_id => (int)$item->count];
                        })
                        ->toArray();

                    // جلب جميع الحالات من قاعدة البيانات
                    $allStatuses = \App\Models\AlWaseetOrderStatus::orderBy('display_order')
                        ->orderBy('status_text')
                        ->get();

                    // تهيئة العدادات لجميع الحالات أولاً بقيمة 0
                    foreach ($allStatuses as $status) {
                        $statusId = (string)$status->status_id;
                        $statusCounts[$statusId] = 0;
                    }
                    
                    // تحديث العدادات للحالات الموجودة فقط
                    foreach ($statusCountsFromDb as $statusId => $count) {
                        $statusIdStr = (string)$statusId;
                        $statusCounts[$statusIdStr] = (int)$count;
                    }
                    
                    // إضافة أي حالات موجودة في قاعدة البيانات ولكن غير موجودة في allStatuses
                    foreach ($statusCountsFromDb as $statusId => $count) {
                        $statusIdStr = (string)$statusId;
                        if (!isset($statusCounts[$statusIdStr])) {
                            $statusCounts[$statusIdStr] = (int)$count;
                        }
                    }
                } else {
                    // إرجاع أصفار لجميع الحالات
                    $allStatuses = \App\Models\AlWaseetOrderStatus::orderBy('display_order')
                        ->orderBy('status_text')
                        ->get();
                    
                    foreach ($allStatuses as $status) {
                        $statusId = (string)$status->status_id;
                        $statusCounts[$statusId] = 0;
                    }
                }

                // حفظ في Cache لمدة 10 دقائق
                $cacheKey = 'delegate_all_status_counts_' . $delegate->id;
                Cache::put($cacheKey, $statusCounts, now()->addMinutes(10));
                
            } catch (\Exception $e) {
                Log::warning('UpdateStatusCountsJob: Failed to update status counts for delegate', [
                    'delegate_id' => $delegate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * حساب statusCounts للمدير والمجهز (بدون فلاتر)
     */
    private function updateAdminStatusCounts(): void
    {
        try {
            // حساب statusCounts لجميع الطلبات (بدون فلاتر)
            $orderIds = Order::where('status', 'confirmed')
                ->whereHas('alwaseetShipment')
                ->pluck('id')
                ->toArray();

            $statusCounts = [];
            
            if (!empty($orderIds)) {
                // حساب عدد الطلبات لكل حالة مباشرة من قاعدة البيانات
                $statusCountsFromDb = AlWaseetShipment::whereIn('order_id', $orderIds)
                    ->whereNotNull('status_id')
                    ->selectRaw('status_id, COUNT(*) as count')
                    ->groupBy('status_id')
                    ->get()
                    ->mapWithKeys(function($item) {
                        return [(string)$item->status_id => (int)$item->count];
                    })
                    ->toArray();

                // جلب جميع الحالات من قاعدة البيانات
                $allStatuses = \App\Models\AlWaseetOrderStatus::orderBy('display_order')
                    ->orderBy('status_text')
                    ->get();

                // تهيئة العدادات لجميع الحالات أولاً بقيمة 0
                foreach ($allStatuses as $status) {
                    $statusId = (string)$status->status_id;
                    $statusCounts[$statusId] = 0;
                }
                
                // تحديث العدادات للحالات الموجودة فقط
                foreach ($statusCountsFromDb as $statusId => $count) {
                    $statusIdStr = (string)$statusId;
                    $statusCounts[$statusIdStr] = (int)$count;
                }
            } else {
                // إرجاع أصفار لجميع الحالات
                $allStatuses = \App\Models\AlWaseetOrderStatus::orderBy('display_order')
                    ->orderBy('status_text')
                    ->get();
                
                foreach ($allStatuses as $status) {
                    $statusId = (string)$status->status_id;
                    $statusCounts[$statusId] = 0;
                }
            }

            // حفظ في Cache لمدة 10 دقائق
            $cacheKey = 'admin_all_status_counts';
            Cache::put($cacheKey, $statusCounts, now()->addMinutes(10));
            
        } catch (\Exception $e) {
            Log::warning('UpdateStatusCountsJob: Failed to update status counts for admin', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

