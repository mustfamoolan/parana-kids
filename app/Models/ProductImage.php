<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the product that owns this image
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        // استخدام cloud disk إذا كان متاحاً (Laravel Cloud)، وإلا استخدم public
        $disk = env('AWS_BUCKET') ? 'cloud' : 'public';

        if ($disk === 'cloud') {
            return \Illuminate\Support\Facades\Storage::disk('cloud')->url($this->image_path);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }
}
