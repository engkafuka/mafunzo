<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmxAuctionLot extends Model
{
    protected $table = 'tmx_auction_lots';

    protected $fillable = [
        'auction_id',
        'tmx_lot_id',
        'lot_uuid',
        'lot_number',
        'lot_status',
        'commodity_tmx_id',
        'commodity_tmx_name',
        'commodity_wrrb_name',
        'commodity_wrrb_grade',
        'winner_user_id',
        'winner_company_id',
        'winner_name',
        'consent_id',
        'consent_status',
        'agree_to_sale',
        'consent_type',
        'consent_deadline',
        'release_order_id',
        'release_order_number',
        'release_order_status',
        'release_order_date',
    ];

    protected $casts = [
        'agree_to_sale' => 'boolean',
        'consent_deadline' => 'datetime',
        'release_order_date' => 'date',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(TmxAuction::class, 'auction_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(TmxAuctionReceipt::class, 'lot_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(TmxAuctionLotHistory::class, 'lot_id')->orderBy('sequence');
    }
}
