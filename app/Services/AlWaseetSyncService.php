<?php

namespace App\Services;

use App\Events\AlWaseetShipmentStatusChanged;
use App\Models\AlWaseetShipment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AlWaseetSyncService
{
    /**
     * Sync a local shipment record with fresh data from AlWaseet API
     *
     * @param AlWaseetShipment $shipment
     * @param array $apiOrder Fresh data from AlWaseet API
     * @return bool True if updated, false otherwise
     */
    public function syncShipmentWithApiData(AlWaseetShipment $shipment, array $apiOrder): bool
    {
        try {
            $oldStatusId = $shipment->status_id;

            // Prepare update data
            $updateData = [
                'status_id' => $apiOrder['status_id'] ?? $shipment->status_id,
                'status' => $apiOrder['status'] ?? $shipment->status,
                'pickup_id' => $apiOrder['pickup_id'] ?? $shipment->pickup_id,
                'merchant_invoice_id' => $apiOrder['merchant_invoice_id'] ?? $shipment->merchant_invoice_id,
                'qr_id' => $apiOrder['qr_id'] ?? $shipment->qr_id,
                'api_data' => $apiOrder,
                'synced_at' => now(),
            ];

            // Update other fields if present in API
            $fields = [
                'client_name', 'client_mobile', 'client_mobile2', 
                'city_id', 'city_name', 'region_id', 'region_name', 
                'location', 'price', 'delivery_price', 'package_size', 
                'type_name', 'items_number', 'merchant_notes', 'issue_notes', 
                'qr_link'
            ];

            foreach ($fields as $field) {
                if (isset($apiOrder[$field])) {
                    $updateData[$field] = $apiOrder[$field];
                }
            }

            if (isset($apiOrder['replacement'])) {
                $updateData['replacement'] = ($apiOrder['replacement'] === '1' || $apiOrder['replacement'] === true);
            }

            if (isset($apiOrder['created_at'])) {
                $updateData['alwaseet_created_at'] = Carbon::parse($apiOrder['created_at']);
            }

            if (isset($apiOrder['updated_at'])) {
                $updateData['alwaseet_updated_at'] = Carbon::parse($apiOrder['updated_at']);
            }

            // Update the shipment
            $shipment->update($updateData);

            // Trigger notification event if status changed
            // Ensure oldStatusId exists to avoid triggering on first sync ever if not intended,
            // but usually we want to notify if status is different from what we had.
            if ($oldStatusId && (string)$oldStatusId !== (string)$updateData['status_id']) {
                event(new AlWaseetShipmentStatusChanged($shipment, $oldStatusId, $updateData['status_id']));
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('AlWaseetSyncService: Sync failed', [
                'shipment_id' => $shipment->id,
                'alwaseet_order_id' => $apiOrder['id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
