<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateWarehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    /**
     * Get the user who created this private warehouse
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all users assigned to this private warehouse
     */
    public function users()
    {
        return $this->hasMany(User::class, 'private_warehouse_id');
    }

    /**
     * Get all invoice products in this private warehouse
     */
    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    /**
     * Get all invoices in this private warehouse
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all investments for this private warehouse
     */
    public function investments()
    {
        return $this->hasMany(Investment::class, 'private_warehouse_id')->where('investment_type', 'private_warehouse');
    }
}
