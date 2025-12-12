<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlWaseetOrderStatusHistory extends Model
{
    protected $table = 'alwaseet_order_status_history';
    
    protected $fillable = [
        'order_id',
        'shipment_id',
        'status_id',
        'status_text',
        'changed_at',
        'changed_by',
        'metadata',
    ];
    
    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function shipment()
    {
        return $this->belongsTo(AlWaseetShipment::class, 'shipment_id');
    }
    
    public function statusInfo()
    {
        return $this->belongsTo(AlWaseetOrderStatus::class, 'status_id', 'status_id');
    }
}

