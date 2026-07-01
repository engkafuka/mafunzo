<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmxAuctionLotHistory extends Model
{
    protected $table = 'tmx_auction_lot_history';

    public $timestamps = true;

    protected $fillable = [
        'lot_id',
        'sequence',
        'status',
        'event_timestamp',
        'notes',
        'current_lot_id',
        'current_lot_uuid',
        'previous_lot_id',
        'previous_lot_uuid',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(TmxAuctionLot::class, 'lot_id');
    }
}
