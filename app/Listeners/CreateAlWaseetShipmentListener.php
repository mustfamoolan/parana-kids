<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\CreateAlWaseetShipmentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateAlWaseetShipmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        // إرسال Job لإنشاء الشحنة في الواسط
        CreateAlWaseetShipmentJob::dispatch($event->order);
    }
}
