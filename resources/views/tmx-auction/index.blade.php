<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('TMX Auction Data') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-600 text-sm">
                {{ __('Pull auction updates from TMX (Mode B). Use filters and click Fetch to load data.') }}
            </p>

            @if(!config('tmx_auction.client_id') || !config('tmx_auction.client_secret'))
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm">
                    <strong>{{ __('API not configured.') }}</strong>
                    {{ __('Set TMX_AUCTION_CLIENT_ID and TMX_AUCTION_CLIENT_SECRET in .env, then run') }}
                    <code class="bg-amber-100 px-1 rounded">php artisan config:clear</code>.
                </div>
            @endif

            {{-- Fetch form card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ __('Fetch auction data') }}</h3>
                    <form method="GET" action="{{ route('tmx-auction.index') }}" class="space-y-4">
                        <input type="hidden" name="fetch" value="1">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">{{ __('From date') }}</label>
                                <input type="date" id="from_date" name="from_date" value="{{ old('from_date', $fromDate ?? '') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">{{ __('To date') }}</label>
                                <input type="date" id="to_date" name="to_date" value="{{ old('to_date', $toDate ?? '') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                            <div>
                                <label for="receipt_no" class="block text-sm font-medium text-gray-700">{{ __('Receipt number') }}</label>
                                <input type="number" id="receipt_no" name="receipt_no" value="{{ old('receipt_no', $receiptNo ?? '') }}" placeholder="{{ __('Optional') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                        </div>
                        <div class="pt-2 flex flex-wrap items-center gap-3">
                            <button type="submit" name="fetch_btn" value="1" class="inline-flex items-center px-6 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-red hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                                {{ __('Fetch') }}
                            </button>
                            <a href="{{ route('tmx-auction.export') }}" class="inline-flex items-center px-6 py-2.5 bg-emerald-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
                                {{ __('Export / Download CSV') }}
                            </a>
                            <span class="text-sm text-gray-500">{{ __('Load auction data from TMX') }} · {{ __('Export downloads latest saved data as CSV.') }}</span>
                        </div>
                    </form>
                </div>
            </div>

            @if(isset($error) && $error)
                <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
                    <strong>{{ __('API error:') }}</strong> {{ $error }}
                </div>
            @endif

            @if(isset($result) && is_array($result) && !$error)
                @php $stats = $stats ?? []; @endphp
                {{-- Export result table --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Message') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Timestamp') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Processed') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Sent') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Failed') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Scheduled retry') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Auctions') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Lots') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-3 text-sm text-gray-900">
                                        {{ $result['message'] ?? '—' }}
                                        @if(!empty($storedCount))
                                            <span class="block mt-1 text-emerald-600 font-medium">{{ __('Saved :count delivery snapshot(s) to database.', ['count' => $storedCount]) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $result['timestamp'] ?? '—' }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $stats['processed'] ?? 0 }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $stats['sent'] ?? 0 }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $stats['failed'] ?? 0 }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $stats['scheduled_for_retry'] ?? 0 }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $stats['total_auctions'] ?? 0 }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $stats['total_lots'] ?? 0 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if(isset($paginatedAuctions) && $paginatedAuctions->total() > 0)
                {{-- Auctions dashboard: table + expandable lot details --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200" x-data="{ expanded: {} }">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">{{ __('Auction data') }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ __('Click a row to expand lots and receipts. Shows latest state per auction (winner, release order) from current fetch and saved data.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-10"></th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Auction ID') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Auction') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Request ID') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Date') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Type') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Start') }}</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('Lots') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($paginatedAuctions as $row)
                                    @php
                                        $rowId = $row['row_id'];
                                        $auction = $row['auction'];
                                        $requestId = $row['request_id'];
                                    @endphp
                                        <tr class="hover:bg-gray-50 transition"
                                            @click="expanded['{{ $rowId }}'] = !expanded['{{ $rowId }}']"
                                            x-bind:class="expanded['{{ $rowId }}'] ? 'bg-indigo-50/50' : ''">
                                            <td class="px-6 py-3 text-gray-400">
                                                <span x-show="!expanded['{{ $rowId }}']">▶</span>
                                                <span x-show="expanded['{{ $rowId }}']" x-cloak>▼</span>
                                            </td>
                                            <td class="px-6 py-3 text-sm font-mono text-gray-700">{{ $auction['auction_id'] ?? '—' }}</td>
                                            <td class="px-6 py-3">
                                                <span class="font-medium text-gray-900">{{ $auction['auction_title'] ?? ('Auction #' . ($auction['auction_id'] ?? '')) }}</span>
                                            </td>
                                            <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $requestId }}</td>
                                            <td class="px-6 py-3 text-sm text-gray-700">{{ $auction['auction_date'] ?? '—' }}</td>
                                            <td class="px-6 py-3">
                                                @php $status = $auction['auction_status'] ?? ''; @endphp
                                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                                    @if(in_array($status, ['PUBLISHED','CLOSED'])) bg-emerald-100 text-emerald-800
                                                    @elseif(in_array($status, ['IN_AUCTION','UPCOMING'])) bg-blue-100 text-blue-800
                                                    @elseif($status === 'CANCELED') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">{{ $status ?: '—' }}</span>
                                            </td>
                                            <td class="px-6 py-3 text-sm text-gray-700">{{ $auction['auction_type'] ?? '—' }}</td>
                                            <td class="px-6 py-3 text-sm text-gray-700">{{ $auction['start_time'] ?? '—' }}</td>
                                            <td class="px-6 py-3 text-right text-sm font-medium text-gray-700">{{ count($auction['lots'] ?? []) }}</td>
                                        </tr>
                                        <tr x-show="expanded['{{ $rowId }}']" x-cloak class="bg-gray-50/80">
                                            <td colspan="9" class="px-6 py-4">
                                                <div class="rounded-lg border border-gray-200 bg-white overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                        <thead class="bg-gray-100">
                                                            <tr>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Lot') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Lot status') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Receipt No') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('WRRB Receipt ID') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Receipt status') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Bags') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Weight (kg)') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Warehouse') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Commodity') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Winner') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Consent') }}</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{{ __('Release order') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200">
                                                            @forelse($auction['lots'] ?? [] as $lot)
                                                                @php
                                                                    $receipts = $lot['receipts'] ?? [];
                                                                    $receiptRows = count($receipts) ?: 1;
                                                                @endphp
                                                                @if(count($receipts) > 0)
                                                                    @foreach($receipts as $rIdx => $r)
                                                                        <tr class="hover:bg-gray-50">
                                                                            @if($rIdx === 0)
                                                                                <td class="px-4 py-2 font-medium text-gray-900 align-top" rowspan="{{ count($receipts) }}">{{ $lot['lot_number'] ?? '—' }}</td>
                                                                                <td class="px-4 py-2 align-top" rowspan="{{ count($receipts) }}">
                                                                                    @php $lotStatus = $lot['lot_status'] ?? ''; @endphp
                                                                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                                                                        @if($lotStatus === 'SOLD') bg-emerald-100 text-emerald-800
                                                                                        @elseif($lotStatus === 'OPEN') bg-blue-100 text-blue-800
                                                                                        @elseif($lotStatus === 'UNSOLD') bg-amber-100 text-amber-800
                                                                                        @else bg-gray-100 text-gray-700
                                                                                        @endif">{{ $lotStatus ?: '—' }}</span>
                                                                                </td>
                                                                            @endif
                                                                            <td class="px-4 py-2 text-gray-700 font-mono">{{ $r['receipt_no'] ?? '—' }}</td>
                                                                            <td class="px-4 py-2 text-gray-700 font-mono">{{ $r['wrrb_receipt_id'] ?? '—' }}</td>
                                                                            <td class="px-4 py-2 text-gray-700">{{ $r['receipt_status'] ?? '—' }}</td>
                                                                            <td class="px-4 py-2 text-gray-700">{{ $r['bags'] ?? '—' }}</td>
                                                                            <td class="px-4 py-2 text-gray-700">{{ isset($r['weight_kg']) ? number_format((float)$r['weight_kg'], 1) : '—' }}</td>
                                                                            <td class="px-4 py-2 text-gray-700">{{ $r['warehouse']['warehouse_name'] ?? ($r['warehouse']['wrrb_warehouse_id'] ?? '—') }}</td>
                                                                            @if($rIdx === 0)
                                                                                <td class="px-4 py-2 text-gray-700 align-top" rowspan="{{ count($receipts) }}">
                                                                                    @if(!empty($lot['commodity']))
                                                                                        {{ $lot['commodity']['wrrb_commodity_name'] ?? $lot['commodity']['tmx_commodity_name'] ?? '' }} ({{ $lot['commodity']['wrrb_grade'] ?? '' }})
                                                                                    @else — @endif
                                                                                </td>
                                                                                <td class="px-4 py-2 text-gray-700 align-top" rowspan="{{ count($receipts) }}">{{ $lot['winner']['winner_name'] ?? '—' }}</td>
                                                                                <td class="px-4 py-2 text-gray-700 align-top" rowspan="{{ count($receipts) }}">{{ $lot['consent']['consent_status'] ?? '—' }}</td>
                                                                                <td class="px-4 py-2 text-gray-700 align-top" rowspan="{{ count($receipts) }}">
                                                                                    @if(!empty($lot['release_order']))
                                                                                        {{ $lot['release_order']['release_order_number'] ?? '' }} ({{ $lot['release_order']['release_order_status'] ?? '' }})
                                                                                    @else — @endif
                                                                                </td>
                                                                            @endif
                                                                        </tr>
                                                                    @endforeach
                                                                @else
                                                                    <tr class="hover:bg-gray-50">
                                                                        <td class="px-4 py-2 font-medium text-gray-900">{{ $lot['lot_number'] ?? '—' }}</td>
                                                                        <td class="px-4 py-2">
                                                                            @php $lotStatus = $lot['lot_status'] ?? ''; @endphp
                                                                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                                                                @if($lotStatus === 'SOLD') bg-emerald-100 text-emerald-800
                                                                                @elseif($lotStatus === 'OPEN') bg-blue-100 text-blue-800
                                                                                @elseif($lotStatus === 'UNSOLD') bg-amber-100 text-amber-800
                                                                                @else bg-gray-100 text-gray-700
                                                                                @endif">{{ $lotStatus ?: '—' }}</span>
                                                                        </td>
                                                                        <td class="px-4 py-2 text-gray-500">—</td>
                                                                        <td class="px-4 py-2 text-gray-500">—</td>
                                                                        <td class="px-4 py-2 text-gray-500">—</td>
                                                                        <td class="px-4 py-2 text-gray-500">—</td>
                                                                        <td class="px-4 py-2 text-gray-500">—</td>
                                                                        <td class="px-4 py-2 text-gray-500">—</td>
                                                                        <td class="px-4 py-2 text-gray-700">
                                                                            @if(!empty($lot['commodity']))
                                                                                {{ $lot['commodity']['wrrb_commodity_name'] ?? $lot['commodity']['tmx_commodity_name'] ?? '' }} ({{ $lot['commodity']['wrrb_grade'] ?? '' }})
                                                                            @else — @endif
                                                                        </td>
                                                                        <td class="px-4 py-2 text-gray-700">{{ $lot['winner']['winner_name'] ?? '—' }}</td>
                                                                        <td class="px-4 py-2 text-gray-700">{{ $lot['consent']['consent_status'] ?? '—' }}</td>
                                                                        <td class="px-4 py-2 text-gray-700">
                                                                            @if(!empty($lot['release_order']))
                                                                                {{ $lot['release_order']['release_order_number'] ?? '' }} ({{ $lot['release_order']['release_order_status'] ?? '' }})
                                                                            @else — @endif
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @empty
                                                                <tr><td colspan="12" class="px-4 py-3 text-gray-500">{{ __('No lots') }}</td></tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <x-table-pagination :paginator="$paginatedAuctions" />
                    </div>
                </div>
            @elseif(isset($result) && is_array($result) && ($paginatedAuctions->total() ?? 0) === 0 && !$error)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                    <p class="text-gray-500">{{ __('No auction data in response.') }}</p>
                </div>
            @endif
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
