<?php

namespace App\Jobs;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetShipment;
use App\Models\AlWaseetSyncLog;
use App\Models\Setting;
use App\Services\AlWaseetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAlWaseetOrdersJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300;

    protected $statusId;
    protected $dateFrom;
    protected $dateTo;
    protected $syncLog;

    /**
     * Create a new job instance.
     */
    public function __construct($statusId = null, $dateFrom = null, $dateTo = null)
    {
        $this->statusId = $statusId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Execute the job.
     */
    public function handle(AlWaseetService $alWaseetService): void
    {
        $this->syncLog = AlWaseetSyncLog::create([
            'type' => 'automatic',
            'status' => 'success',
            'started_at' => now(),
            'filters' => [
                'status_id' => $this->statusId,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ],
        ]);

        try {
            // جلب الطلبات من الواسط
            $orders = $alWaseetService->getOrders(
                $this->statusId,
                $this->dateFrom,
                $this->dateTo
            );

            if (empty($orders) || !is_array($orders)) {
                $this->syncLog->update([
                    'orders_synced' => 0,
                    'status' => 'success',
                ]);
                $this->syncLog->markCompleted('success');
                return;
            }

            $synced = 0;
            $updated = 0;
            $created = 0;
            $failed = 0;

            DB::transaction(function() use ($orders, &$synced, &$updated, &$created, &$failed) {
                foreach ($orders as $orderData) {
                    try {
                        $shipment = AlWaseetShipment::updateOrCreate(
                            ['alwaseet_order_id' => $orderData['id']],
                            [
                                'client_name' => $orderData['client_name'] ?? '',
                                'client_mobile' => $orderData['client_mobile'] ?? '',
                                'client_mobile2' => $orderData['client_mobile2'] ?? null,
                                'city_id' => $orderData['city_id'] ?? '',
                                'city_name' => $orderData['city_name'] ?? '',
                                'region_id' => $orderData['region_id'] ?? '',
                                'region_name' => $orderData['region_name'] ?? '',
                                'location' => $orderData['location'] ?? '',
                                'price' => $orderData['price'] ?? 0,
                                'delivery_price' => $orderData['delivery_price'] ?? 0,
                                'package_size' => $orderData['package_size'] ?? '',
                                'type_name' => $orderData['type_name'] ?? '',
                                'status_id' => $orderData['status_id'] ?? '',
                                'status' => $orderData['status'] ?? '',
                                'items_number' => $orderData['items_number'] ?? '1',
                                'merchant_notes' => $orderData['merchant_notes'] ?? null,
                                'issue_notes' => $orderData['issue_notes'] ?? null,
                                'replacement' => isset($orderData['replacement']) && $orderData['replacement'] === '1',
                                'qr_id' => $orderData['qr_id'] ?? null,
                                'qr_link' => $orderData['qr_link'] ?? null,
                                'alwaseet_created_at' => isset($orderData['created_at']) ? \Carbon\Carbon::parse($orderData['created_at']) : null,
                                'alwaseet_updated_at' => isset($orderData['updated_at']) ? \Carbon\Carbon::parse($orderData['updated_at']) : null,
                                'synced_at' => now(),
                            ]
                        );

                        // التحقق من تغيير الحالة لإرسال إشعار
                        $oldStatusId = $shipment->getOriginal('status_id');
                        $newStatusId = $orderData['status_id'] ?? '';

                        if ($oldStatusId && $oldStatusId !== $newStatusId) {
                            event(new AlWaseetShipmentStatusChanged($shipment, $oldStatusId, $newStatusId));
                        }

                        if ($shipment->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $updated++;
                        }
                        $synced++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('AlWaseet: Failed to sync order', [
                            'alwaseet_order_id' => $orderData['id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            $status = ($failed > 0 && $synced > 0) ? 'partial' : ($failed > 0 ? 'failed' : 'success');

            $this->syncLog->update([
                'orders_synced' => $synced,
                'orders_updated' => $updated,
                'orders_created' => $created,
                'orders_failed' => $failed,
                'status' => $status,
            ]);

            $this->syncLog->markCompleted($status);

            Log::info('AlWaseet: Sync completed', [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            $this->syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $this->syncLog->markCompleted('failed');

            Log::error('AlWaseet: Sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
