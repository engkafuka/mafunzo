<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmxAuctionReceipt extends Model
{
    protected $table = 'tmx_auction_receipts';

    protected $fillable = [
        'lot_id',
        'receipt_no',
        'wrrb_receipt_id',
        'receipt_status',
        'bags',
        'weight_kg',
        'wrrb_warehouse_id',
        'warehouse_name',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(TmxAuctionLot::class, 'lot_id');
    }
}
