<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmxAuction extends Model
{
    protected $table = 'tmx_auctions';

    protected $fillable = [
        'delivery_id',
        'tmx_auction_id',
        'auction_uuid',
        'auction_title',
        'auction_date',
        'start_time',
        'auction_status',
        'auction_type',
        'execution_mode',
        'tick_size',
        'initial_time_seconds',
        'increment_time_seconds',
        'open_auction',
        'has_prebid',
        'start_mode',
        'lot_rotation_count',
    ];

    protected $casts = [
        'auction_date' => 'date',
        'open_auction' => 'boolean',
        'has_prebid' => 'boolean',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(TmxDelivery::class, 'delivery_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(TmxAuctionLot::class, 'auction_id');
    }
}
