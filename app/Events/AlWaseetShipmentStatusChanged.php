<?php

namespace App\Events;

use App\Models\AlWaseetShipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlWaseetShipmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AlWaseetShipment $shipment,
        public string $oldStatusId,
        public string $newStatusId
    ) {
        //
    }
}
