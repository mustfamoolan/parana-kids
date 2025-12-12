<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedAlWaseetStatusHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alwaseet:seed-status-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed AlWaseet order status history from existing shipments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to seed AlWaseet status history...');
        
        $totalShipments = \App\Models\AlWaseetShipment::whereNotNull('status_id')
            ->whereNotNull('order_id')
            ->count();
        $this->info("Found {$totalShipments} shipments with status and order");
        
        $bar = $this->output->createProgressBar($totalShipments);
        $bar->start();
        
        $seeded = 0;
        $skipped = 0;
        
        \App\Models\AlWaseetShipment::whereNotNull('status_id')
            ->whereNotNull('order_id') // فقط الشحنات المرتبطة بطلبات
            ->chunk(100, function ($shipments) use (&$seeded, &$skipped, $bar) {
                foreach ($shipments as $shipment) {
                    // تحقق إذا كان هناك سجل موجود بالفعل
                    $exists = \App\Models\AlWaseetOrderStatusHistory::where('shipment_id', $shipment->id)
                        ->where('status_id', $shipment->status_id)
                        ->exists();
                    
                    if (!$exists) {
                        \App\Models\AlWaseetOrderStatusHistory::create([
                            'order_id' => $shipment->order_id,
                            'shipment_id' => $shipment->id,
                            'status_id' => $shipment->status_id,
                            'status_text' => $shipment->status ?? '',
                            'changed_at' => $shipment->alwaseet_updated_at ?? $shipment->updated_at,
                            'changed_by' => 'initial_seed',
                            'metadata' => [
                                'seeded_from_existing' => true,
                            ],
                        ]);
                        $seeded++;
                    } else {
                        $skipped++;
                    }
                    
                    $bar->advance();
                }
            });
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✓ Seeding completed!");
        $this->info("Seeded: {$seeded} records");
        $this->info("Skipped: {$skipped} records (already exist)");
        
        return 0;
    }
}
