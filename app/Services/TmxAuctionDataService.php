<?php

namespace App\Services;

use App\Models\TmxAuction;
use App\Models\TmxDelivery;
use Illuminate\Support\Carbon;

class TmxAuctionDataService
{
    /**
     * Load stored deliveries and return in same shape as API data (for display).
     *
     * @return array<int, array{request_id: string, updated_at: string|null, source_system: string, auctions: array}>
     */
    public function getStoredDataItems(): array
    {
        $deliveries = TmxDelivery::with([
            'auctions.lots.receipts',
            'auctions.lots.history',
        ])->orderByDesc('tmx_updated_at')->get();

        $items = [];
        foreach ($deliveries as $d) {
            $items[] = $this->deliveryToDataItem($d);
        }

        return $items;
    }

    /**
     * Return one virtual "delivery" with the latest state of each auction (by tmx_auction_id).
     * When the API sends an updated payload (e.g. lot SOLD, winner, release order), that newer
     * version is shown so saved data reflects the update.
     *
     * @return array<int, array{request_id: string, updated_at: string|null, source_system: string, auctions: array}>
     */
    public function getStoredDataItemsLatestPerAuction(): array
    {
        $deliveries = TmxDelivery::with([
            'auctions.lots.receipts',
            'auctions.lots.history',
        ])->orderByDesc('tmx_updated_at')->get();

        $seenAuctionIds = [];
        $latestAuctions = [];
        $latestUpdatedAt = null;

        foreach ($deliveries as $d) {
            foreach ($d->auctions as $auction) {
                $tid = $auction->tmx_auction_id;
                if ($tid === null || in_array($tid, $seenAuctionIds, true)) {
                    continue;
                }
                $seenAuctionIds[] = $tid;
                $latestAuctions[] = $this->auctionToPayload($auction);
                if ($d->tmx_updated_at && ($latestUpdatedAt === null || $d->tmx_updated_at->gt($latestUpdatedAt))) {
                    $latestUpdatedAt = $d->tmx_updated_at;
                }
            }
        }

        if (count($latestAuctions) === 0) {
            return [];
        }

        return [[
            'request_id' => 'latest-per-auction',
            'updated_at' => $latestUpdatedAt?->format('c'),
            'source_system' => 'TMX',
            'auctions' => $latestAuctions,
        ]];
    }

    /**
     * Build API-like auction payload from a TmxAuction model (with lots loaded).
     */
    protected function auctionToPayload(TmxAuction $a): array
    {
        $lots = [];
        foreach ($a->lots as $lot) {
            $receipts = [];
            foreach ($lot->receipts as $r) {
                $receipts[] = [
                    'receipt_no' => $r->receipt_no,
                    'wrrb_receipt_id' => $r->wrrb_receipt_id,
                    'receipt_status' => $r->receipt_status,
                    'bags' => $r->bags,
                    'weight_kg' => $r->weight_kg,
                    'warehouse' => [
                        'wrrb_warehouse_id' => $r->wrrb_warehouse_id,
                        'warehouse_name' => $r->warehouse_name,
                    ],
                ];
            }
            $history = [];
            foreach ($lot->history as $h) {
                $history[] = [
                    'sequence' => $h->sequence,
                    'status' => $h->status,
                    'timestamp' => $h->event_timestamp?->format('c'),
                    'notes' => $h->notes,
                    'current_lot_id' => $h->current_lot_id,
                    'current_lot_uuid' => $h->current_lot_uuid,
                    'previous_lot_id' => $h->previous_lot_id,
                    'previous_lot_uuid' => $h->previous_lot_uuid,
                ];
            }
            $lots[] = [
                'lot_id' => $lot->tmx_lot_id,
                'lot_uuid' => $lot->lot_uuid,
                'lot_number' => $lot->lot_number,
                'lot_status' => $lot->lot_status,
                'commodity' => [
                    'tmx_commodity_id' => $lot->commodity_tmx_id,
                    'tmx_commodity_name' => $lot->commodity_tmx_name,
                    'wrrb_commodity_name' => $lot->commodity_wrrb_name,
                    'wrrb_grade' => $lot->commodity_wrrb_grade,
                ],
                'receipts' => $receipts,
                'winner' => [
                    'winner_user_id' => $lot->winner_user_id,
                    'winner_company_id' => $lot->winner_company_id,
                    'winner_name' => $lot->winner_name,
                ],
                'consent' => [
                    'consent_id' => $lot->consent_id,
                    'consent_status' => $lot->consent_status,
                    'agree_to_sale' => $lot->agree_to_sale,
                    'consent_type' => $lot->consent_type,
                    'consent_deadline' => $lot->consent_deadline?->format('c'),
                ],
                'release_order' => [
                    'release_order_id' => $lot->release_order_id,
                    'release_order_number' => $lot->release_order_number,
                    'release_order_status' => $lot->release_order_status,
                    'release_order_date' => $lot->release_order_date?->format('Y-m-d'),
                ],
                'history' => $history,
            ];
        }
        return [
            'auction_id' => $a->tmx_auction_id,
            'auction_uuid' => $a->auction_uuid,
            'auction_title' => $a->auction_title,
            'auction_date' => $a->auction_date?->format('Y-m-d'),
            'start_time' => $a->start_time,
            'auction_status' => $a->auction_status,
            'auction_type' => $a->auction_type,
            'execution_mode' => $a->execution_mode,
            'tick_size' => $a->tick_size,
            'initial_time_seconds' => $a->initial_time_seconds,
            'increment_time_seconds' => $a->increment_time_seconds,
            'open_auction' => $a->open_auction,
            'has_prebid' => $a->has_prebid,
            'start_mode' => $a->start_mode,
            'lot_rotation_count' => $a->lot_rotation_count,
            'lots' => $lots,
        ];
    }

    /**
     * Convert a TmxDelivery model (with relations) to API-like data item.
     */
    protected function deliveryToDataItem(TmxDelivery $d): array
    {
        $auctions = [];
        foreach ($d->auctions as $a) {
            $auctions[] = $this->auctionToPayload($a);
        }
        return [
            'request_id' => $d->request_id,
            'updated_at' => $d->tmx_updated_at?->format('c'),
            'source_system' => $d->source_system ?? 'TMX',
            'auctions' => $auctions,
        ];
    }

    /**
     * Store or update TMX API data items into the database.
     * Uses request_id as unique key: same request_id replaces previous snapshot.
     *
     * @param  array<int, array{request_id?: string, updated_at?: string, source_system?: string, auctions?: array}>  $dataItems
     * @return array{stored: int, delivery_ids: array<int>}
     */
    public function storeDataItems(array $dataItems): array
    {
        $deliveryIds = [];

        foreach ($dataItems as $item) {
            $requestId = $item['request_id'] ?? null;
            if (! $requestId) {
                continue;
            }

            $tmxUpdatedAt = isset($item['updated_at'])
                ? Carbon::parse($item['updated_at'])
                : null;

            $delivery = TmxDelivery::updateOrCreate(
                ['request_id' => $requestId],
                [
                    'tmx_updated_at' => $tmxUpdatedAt,
                    'source_system' => $item['source_system'] ?? 'TMX',
                ]
            );

            $delivery->auctions()->delete();

            foreach ($item['auctions'] ?? [] as $auctionPayload) {
                $auction = $delivery->auctions()->create($this->auctionAttributes($auctionPayload));

                foreach ($auctionPayload['lots'] ?? [] as $lotPayload) {
                    $lot = $auction->lots()->create($this->lotAttributes($lotPayload));

                    foreach ($lotPayload['receipts'] ?? [] as $receiptPayload) {
                        $lot->receipts()->create($this->receiptAttributes($receiptPayload));
                    }

                    foreach ($lotPayload['history'] ?? [] as $historyPayload) {
                        $lot->history()->create($this->historyAttributes($historyPayload));
                    }
                }
            }

            $deliveryIds[] = $delivery->id;
        }

        return ['stored' => count($deliveryIds), 'delivery_ids' => $deliveryIds];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>
     */
    protected function auctionAttributes(array $a): array
    {
        return [
            'tmx_auction_id' => $a['auction_id'] ?? null,
            'auction_uuid' => $a['auction_uuid'] ?? null,
            'auction_title' => $a['auction_title'] ?? null,
            'auction_date' => isset($a['auction_date']) ? $a['auction_date'] : null,
            'start_time' => $a['start_time'] ?? null,
            'auction_status' => $a['auction_status'] ?? null,
            'auction_type' => $a['auction_type'] ?? null,
            'execution_mode' => $a['execution_mode'] ?? null,
            'tick_size' => $a['tick_size'] ?? null,
            'initial_time_seconds' => $a['initial_time_seconds'] ?? null,
            'increment_time_seconds' => $a['increment_time_seconds'] ?? null,
            'open_auction' => $a['open_auction'] ?? null,
            'has_prebid' => $a['has_prebid'] ?? null,
            'start_mode' => $a['start_mode'] ?? null,
            'lot_rotation_count' => $a['lot_rotation_count'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $l
     * @return array<string, mixed>
     */
    protected function lotAttributes(array $l): array
    {
        $commodity = $l['commodity'] ?? [];
        $winner = $l['winner'] ?? [];
        $consent = $l['consent'] ?? [];
        $releaseOrder = $l['release_order'] ?? [];

        return [
            'tmx_lot_id' => $l['lot_id'] ?? null,
            'lot_uuid' => $l['lot_uuid'] ?? null,
            'lot_number' => $l['lot_number'] ?? null,
            'lot_status' => $l['lot_status'] ?? null,
            'commodity_tmx_id' => $commodity['tmx_commodity_id'] ?? null,
            'commodity_tmx_name' => $commodity['tmx_commodity_name'] ?? null,
            'commodity_wrrb_name' => $commodity['wrrb_commodity_name'] ?? null,
            'commodity_wrrb_grade' => $commodity['wrrb_grade'] ?? null,
            'winner_user_id' => $winner['winner_user_id'] ?? null,
            'winner_company_id' => $winner['winner_company_id'] ?? null,
            'winner_name' => $winner['winner_name'] ?? null,
            'consent_id' => $consent['consent_id'] ?? null,
            'consent_status' => $consent['consent_status'] ?? null,
            'agree_to_sale' => $consent['agree_to_sale'] ?? null,
            'consent_type' => $consent['consent_type'] ?? null,
            'consent_deadline' => isset($consent['consent_deadline']) ? $consent['consent_deadline'] : null,
            'release_order_id' => $releaseOrder['release_order_id'] ?? null,
            'release_order_number' => $releaseOrder['release_order_number'] ?? null,
            'release_order_status' => $releaseOrder['release_order_status'] ?? null,
            'release_order_date' => isset($releaseOrder['release_order_date']) ? $releaseOrder['release_order_date'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    protected function receiptAttributes(array $r): array
    {
        $warehouse = $r['warehouse'] ?? [];

        return [
            'receipt_no' => $r['receipt_no'] ?? null,
            'wrrb_receipt_id' => $r['wrrb_receipt_id'] ?? null,
            'receipt_status' => $r['receipt_status'] ?? null,
            'bags' => $r['bags'] ?? null,
            'weight_kg' => $r['weight_kg'] ?? null,
            'wrrb_warehouse_id' => $warehouse['wrrb_warehouse_id'] ?? null,
            'warehouse_name' => $warehouse['warehouse_name'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $h
     * @return array<string, mixed>
     */
    protected function historyAttributes(array $h): array
    {
        return [
            'sequence' => $h['sequence'] ?? 0,
            'status' => $h['status'] ?? null,
            'event_timestamp' => isset($h['timestamp']) ? $h['timestamp'] : null,
            'notes' => $h['notes'] ?? null,
            'current_lot_id' => $h['current_lot_id'] ?? null,
            'current_lot_uuid' => $h['current_lot_uuid'] ?? null,
            'previous_lot_id' => $h['previous_lot_id'] ?? null,
            'previous_lot_uuid' => $h['previous_lot_uuid'] ?? null,
        ];
    }
}
