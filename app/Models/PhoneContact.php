<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the phone numbers for the contact.
     */
    public function phoneNumbers()
    {
        return $this->hasMany(PhoneNumber::class, 'contact_id');
    }
}
