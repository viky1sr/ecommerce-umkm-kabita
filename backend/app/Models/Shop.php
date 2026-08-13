<?php

namespace App\Models;

use App\Enums\ShopStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User $seller
 * @property-read \Illuminate\Database\Eloquent\Collection<Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<Order> $orders
 */

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'seller_id',
        'name',
        'description',
        'phone',
        'address',
        'logo',
        'banner',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShopStatus::class,
        ];
    }

    // ==========================================
    // RELATIONSHIPS (SESUAI RELASI DATABASE)
    // ==========================================

    /**
     * Get the seller (user) that owns the shop.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the admin who verified the shop.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the products for the shop.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the orders for the shop.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the daily product sales for the shop.
     */
    public function dailyProductSales(): HasMany
    {
        return $this->hasMany(DailyProductSales::class);
    }
}
