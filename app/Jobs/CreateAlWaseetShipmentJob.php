<?php

namespace App\Jobs;

use App\Models\AlWaseetShipment;
use App\Models\Order;
use App\Models\Setting;
use App\Services\AlWaseetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateAlWaseetShipmentJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AlWaseetService $alWaseetService): void
    {
        try {
            // التحقق من أن التكامل التلقائي مفعل
            $autoCreate = Setting::getValue('alwaseet_auto_create_shipment', '0');
            if ($autoCreate !== '1') {
                Log::info('AlWaseet: Auto-create shipment is disabled', [
                    'order_id' => $this->order->id,
                ]);
                return;
            }

            // التحقق من أن الطلب غير مربوط بشحنة موجودة
            $existingShipment = AlWaseetShipment::where('order_id', $this->order->id)->first();
            if ($existingShipment) {
                Log::info('AlWaseet: Order already has a shipment', [
                    'order_id' => $this->order->id,
                    'shipment_id' => $existingShipment->id,
                ]);
                return;
            }

            // تحميل items للتأكد من وجودها
            $this->order->load('items');

            // إعداد بيانات الطلب
            $orderData = $this->prepareOrderData();

            // التأكد من أن items_number موجود وليس 0
            if (empty($orderData['items_number']) || $orderData['items_number'] === '0') {
                $orderData['items_number'] = '1'; // قيمة افتراضية
            }

            // إنشاء الطلب في الواسط
            $result = $alWaseetService->createOrder($orderData);

            if (!isset($result['id'])) {
                throw new \Exception('لم يتم إرجاع معرف الطلب من الواسط');
            }

            // جلب بيانات الطلب الكاملة
            $orders = $alWaseetService->getOrdersByIds([$result['id']]);

            if (empty($orders)) {
                throw new \Exception('فشل جلب بيانات الطلب بعد الإنشاء');
            }

            $orderData = $orders[0];

            // حفظ في قاعدة البيانات
            $shipment = AlWaseetShipment::create([
                'alwaseet_order_id' => $orderData['id'],
                'order_id' => $this->order->id,
                'client_name' => $orderData['client_name'] ?? $this->order->customer_name,
                'client_mobile' => $orderData['client_mobile'] ?? $this->formatPhone($this->order->customer_phone),
                'client_mobile2' => $orderData['client_mobile2'] ?? null,
                'city_id' => $orderData['city_id'] ?? Setting::getValue('alwaseet_default_city_id'),
                'city_name' => $orderData['city_name'] ?? '',
                'region_id' => $orderData['region_id'] ?? Setting::getValue('alwaseet_default_region_id'),
                'region_name' => $orderData['region_name'] ?? '',
                'location' => $orderData['location'] ?? $this->order->customer_address,
                'price' => $orderData['price'] ?? $this->order->total_amount,
                'delivery_price' => $orderData['delivery_price'] ?? 0,
                'package_size' => $orderData['package_size'] ?? Setting::getValue('alwaseet_default_package_size_id'),
                'type_name' => $orderData['type_name'] ?? Setting::getValue('alwaseet_default_type_name', 'ملابس'),
                'status_id' => $orderData['status_id'] ?? '1',
                'status' => $orderData['status'] ?? 'جديد',
                'items_number' => $orderData['items_number'] ?? (string)$this->order->items->count(),
                'merchant_notes' => $orderData['merchant_notes'] ?? $this->order->notes,
                'replacement' => isset($orderData['replacement']) && $orderData['replacement'] === '1',
                'qr_id' => $result['qr_id'] ?? null,
                'qr_link' => $result['qr_link'] ?? null,
                'alwaseet_created_at' => isset($orderData['created_at']) ? \Carbon\Carbon::parse($orderData['created_at']) : now(),
                'alwaseet_updated_at' => isset($orderData['updated_at']) ? \Carbon\Carbon::parse($orderData['updated_at']) : now(),
                'synced_at' => now(),
            ]);

            Log::info('AlWaseet: Shipment created successfully', [
                'order_id' => $this->order->id,
                'shipment_id' => $shipment->id,
                'alwaseet_order_id' => $shipment->alwaseet_order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseet: Failed to create shipment', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare order data for AlWaseet API
     */
    protected function prepareOrderData(): array
    {
        $orderData = [
            'client_name' => $this->order->customer_name,
            'client_mobile' => $this->formatPhone($this->order->customer_phone),
            'city_id' => Setting::getValue('alwaseet_default_city_id'),
            'region_id' => Setting::getValue('alwaseet_default_region_id'),
            'location' => $this->order->customer_address ?? '',
            'price' => (string)$this->order->total_amount,
            'package_size' => Setting::getValue('alwaseet_default_package_size_id'),
            'type_name' => Setting::getValue('alwaseet_default_type_name', 'ملابس'),
            'items_number' => (string)$this->order->items->count(),
        ];

        if ($this->order->notes) {
            $orderData['merchant_notes'] = $this->order->notes;
        }

        return $orderData;
    }

    /**
     * Format phone number to +964 format
     */
    protected function formatPhone(string $phone): string
    {
        // إزالة المسافات والأرقام
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // إذا كان يبدأ بـ 0، استبدله بـ +964
        if (strpos($phone, '0') === 0) {
            $phone = '+964' . substr($phone, 1);
        } elseif (strpos($phone, '964') === 0) {
            $phone = '+' . $phone;
        } elseif (strpos($phone, '+964') !== 0) {
            $phone = '+964' . $phone;
        }

        return $phone;
    }
}
