<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmxDelivery extends Model
{
    protected $table = 'tmx_deliveries';

    protected $fillable = [
        'request_id',
        'tmx_updated_at',
        'source_system',
    ];

    protected $casts = [
        'tmx_updated_at' => 'datetime',
    ];

    public function auctions(): HasMany
    {
        return $this->hasMany(TmxAuction::class, 'delivery_id');
    }
}
