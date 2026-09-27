<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'total_price' => 'decimal:2', 'quantity' => 'integer'];
    }

    protected $fillable = [
        'product_id',
        'user_id',
        'name',
        'phone',
        'location',
        'size',
        'color',
        'quantity',
        'unit_price',
        'total_price',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
