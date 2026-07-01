<?php

namespace App\Http\Controllers;

use App\Services\TmxAuctionApiService;
use App\Services\TmxAuctionDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TmxAuctionController extends Controller
{
    public function __construct(
        protected TmxAuctionApiService $tmxApi,
        protected TmxAuctionDataService $dataService
    ) {}

    /**
     * TMX Auction data (Mode B — export-pending / export-by-receipt). Super Admin only.
     */
    public function index(Request $request): View
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $receiptNo = $request->query('receipt_no') ? (int) $request->query('receipt_no') : null;

        $result = null;
        $error = null;

        if ($request->hasAny(['from_date', 'to_date', 'receipt_no']) || $request->query('fetch') === '1') {
            if ($receiptNo && ! $fromDate && ! $toDate) {
                $result = $this->tmxApi->exportByReceipt($receiptNo);
            } else {
                $result = $this->tmxApi->exportPending($fromDate ?: null, $toDate ?: null, $receiptNo);
            }
            $error = $this->tmxApi->getLastError();
        }

        $dataItems = [];
        $stats = [
            'processed' => $result['processed'] ?? 0,
            'sent' => $result['sent'] ?? 0,
            'failed' => $result['failed'] ?? 0,
            'scheduled_for_retry' => $result['scheduled_for_retry'] ?? 0,
            'total_auctions' => 0,
            'total_lots' => 0,
        ];
        $storedCount = 0;
        if (is_array($result) && isset($result['data'])) {
            $data = $result['data'];
            $dataItems = is_array($data) && isset($data[0]) ? $data : ($data ? [$data] : []);
            foreach ($dataItems as $item) {
                $auctions = $item['auctions'] ?? [];
                $stats['total_auctions'] += count($auctions);
                foreach ($auctions as $auction) {
                    $stats['total_lots'] += count($auction['lots'] ?? []);
                }
            }
            if (! empty($dataItems)) {
                $storeResult = $this->dataService->storeDataItems($dataItems);
                $storedCount = $storeResult['stored'];
            }
        }

        // Latest state per auction from DB (API updates like winner/release order appear when we fetch again)
        $storedLatest = $this->dataService->getStoredDataItemsLatestPerAuction();
        $currentAuctionIds = [];
        foreach ($dataItems as $item) {
            foreach ($item['auctions'] ?? [] as $a) {
                if (isset($a['auction_id'])) {
                    $currentAuctionIds[(string) $a['auction_id']] = true;
                }
            }
        }
        $previousDataItems = [];
        foreach ($storedLatest as $item) {
            $auctions = array_values(array_filter($item['auctions'] ?? [], fn ($a) => ! isset($currentAuctionIds[(string) ($a['auction_id'] ?? '')])));
            if (count($auctions) > 0) {
                $previousDataItems[] = array_merge($item, ['auctions' => $auctions]);
            }
        }
        $dataItemsForView = array_merge($dataItems, $previousDataItems);

        return view('tmx-auction.index', [
            'result' => $result,
            'dataItems' => $dataItemsForView,
            'stats' => $stats,
            'storedCount' => $storedCount,
            'error' => $error,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'receiptNo' => $receiptNo,
        ]);
    }

    /**
     * Export auction data (latest per auction from DB) as CSV download.
     */
    public function export(): StreamedResponse
    {
        $items = $this->dataService->getStoredDataItemsLatestPerAuction();
        $filename = 'tmx-auction-data-' . date('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            $header = [
                'Auction ID', 'Auction Title', 'Auction Date', 'Auction Status', 'Start Time',
                'Lot Number', 'Lot Status', 'Commodity', 'Grade', 'Winner',
                'Receipt No', 'WRRB Receipt ID', 'Receipt Status', 'Bags', 'Weight (kg)', 'Warehouse',
                'Consent Status', 'Release Order No', 'Release Order Status', 'Release Order Date',
            ];
            fputcsv($out, $header);

            foreach ($items as $item) {
                foreach ($item['auctions'] ?? [] as $auction) {
                    foreach ($auction['lots'] ?? [] as $lot) {
                        $commodityName = $lot['commodity']['wrrb_commodity_name'] ?? $lot['commodity']['tmx_commodity_name'] ?? '';
                        $commodityGrade = $lot['commodity']['wrrb_grade'] ?? '';
                        $winner = $lot['winner']['winner_name'] ?? '';
                        $consent = $lot['consent']['consent_status'] ?? '';
                        $roNumber = $lot['release_order']['release_order_number'] ?? '';
                        $roStatus = $lot['release_order']['release_order_status'] ?? '';
                        $roDate = $lot['release_order']['release_order_date'] ?? '';
                        $receipts = $lot['receipts'] ?? [];
                        if (count($receipts) === 0) {
                            fputcsv($out, [
                                $auction['auction_id'] ?? '',
                                $auction['auction_title'] ?? '',
                                $auction['auction_date'] ?? '',
                                $auction['auction_status'] ?? '',
                                $auction['start_time'] ?? '',
                                $lot['lot_number'] ?? '',
                                $lot['lot_status'] ?? '',
                                $commodityName,
                                $commodityGrade,
                                $winner,
                                '', '', '', '', '', '',
                                $consent,
                                $roNumber,
                                $roStatus,
                                $roDate,
                            ]);
                        } else {
                            foreach ($receipts as $r) {
                                fputcsv($out, [
                                    $auction['auction_id'] ?? '',
                                    $auction['auction_title'] ?? '',
                                    $auction['auction_date'] ?? '',
                                    $auction['auction_status'] ?? '',
                                    $auction['start_time'] ?? '',
                                    $lot['lot_number'] ?? '',
                                    $lot['lot_status'] ?? '',
                                    $commodityName,
                                    $commodityGrade,
                                    $winner,
                                    $r['receipt_no'] ?? '',
                                    $r['wrrb_receipt_id'] ?? '',
                                    $r['receipt_status'] ?? '',
                                    $r['bags'] ?? '',
                                    isset($r['weight_kg']) ? (string) $r['weight_kg'] : '',
                                    $r['warehouse']['warehouse_name'] ?? '',
                                    $consent,
                                    $roNumber,
                                    $roStatus,
                                    $roDate,
                                ]);
                            }
                        }
                    }
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
