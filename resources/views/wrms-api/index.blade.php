@php
            $hasAnyError = !empty($errorReceipts) || !empty($errorWarehouses) || !empty($errorOperators);
            $hasAnyData = $warehouseReceipts->total() > 0 || $warehouses->total() > 0 || $operators->total() > 0;
            $noDataNoError = !$hasAnyData && !$hasAnyError;
        @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('WRMS API Data') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <p class="text-gray-600 text-sm">
                {{ __('Data from WRRB-TMX Integration API (Warehouse Receipts, Warehouses, Operators).') }}
            </p>

            @if(!config('wrms.username') || !config('wrms.token'))
                <div class="rounded-md bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm">
                    <strong>{{ __('API not configured.') }}</strong>
                    {{ __('Set WRMS_API_USERNAME and WRMS_API_TOKEN in your .env file, then clear config cache:') }}
                    <code class="block mt-2 bg-amber-100 px-2 py-1 rounded">php artisan config:clear</code>
                </div>
            @endif

            @if($hasAnyError)
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
                    <strong>{{ __('API errors occurred:') }}</strong>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @if(!empty($errorReceipts))
                            <li><strong>{{ __('Warehouse Receipts:') }}</strong> {{ $errorReceipts }}</li>
                        @endif
                        @if(!empty($errorWarehouses))
                            <li><strong>{{ __('Warehouses:') }}</strong> {{ $errorWarehouses }}</li>
                        @endif
                        @if(!empty($errorOperators))
                            <li><strong>{{ __('Operators:') }}</strong> {{ $errorOperators }}</li>
                        @endif
                    </ul>
                    <p class="mt-2">{{ __('Check .env credentials and') }} <code class="bg-red-100 px-1 rounded">storage/logs/laravel.log</code> {{ __('for details.') }}</p>
                    @if($hasAnyError && (str_contains($errorReceipts ?? '', 'Odoo') || str_contains($errorWarehouses ?? '', 'Odoo') || str_contains($errorOperators ?? '', 'Odoo')))
                        <p class="mt-2 text-red-700">{{ __('If the error says "Odoo Server Error" or "BadRequest", the WRMS server may be rejecting the request format. Confirm with the WRMS/API team that the request body matches their expected format.') }}</p>
                    @endif
                </div>
            @endif

            @if($noDataNoError)
                <div class="rounded-md bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm">
                    <strong>{{ __('No data and no error reported.') }}</strong>
                    {{ __('The API may have returned an empty result or an unexpected format. Check') }}
                    <code class="bg-amber-100 px-1 rounded">storage/logs/laravel.log</code>
                    {{ __('for "WRMS API" messages. Ensure WRMS_API_BASE_URL, WRMS_API_USERNAME and WRMS_API_TOKEN are correct in .env.') }}
                </div>
            @endif

            <p class="text-sm text-gray-500">
                {{ __('Loaded:') }} {{ $warehouseReceipts->total() }} {{ __('receipts') }}, {{ $warehouses->total() }} {{ __('warehouses') }}, {{ $operators->total() }} {{ __('operators') }}.
            </p>

            {{-- Warehouse Receipts --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Warehouse Receipts') }}</h3>
                <div class="overflow-x-auto">
                    @if(isset($errorReceipts) && $errorReceipts)
                        <p class="p-6 text-amber-700 text-sm">
                            {{ __('API error:') }} {{ $errorReceipts }}
                        </p>
                    @elseif($warehouseReceipts->total() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('ID') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Receipt No') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('State') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Scheduled date') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Partner') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Warehouse') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Product') }}</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Bag count') }}</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Qty done') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Grade') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Origin') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($warehouseReceipts as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $row['id'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm font-mono">{{ $row['name'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['receipt_no'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['state'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['scheduled_date'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['partner_id'] ?? null) ? ($row['partner_id'][1] ?? $row['partner_id'][0] ?? '—') : ($row['partner_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['warehouse_id'] ?? null) ? ($row['warehouse_id'][1] ?? $row['warehouse_id'][0] ?? '—') : ($row['warehouse_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['product_id'] ?? null) ? ($row['product_id'][1] ?? $row['product_id'][0] ?? '—') : ($row['product_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm text-right">{{ $row['bag_count'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm text-right">{{ $row['qty_done'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['grade'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['origin'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <x-table-pagination :paginator="$warehouseReceipts" />
                    @else
                        <p class="p-6 text-gray-500 text-sm">
                            {{ __('No warehouse receipts returned.') }}
                            @if(!$hasAnyError)
                                {{ __('Ensure WRMS_API_BASE_URL, WRMS_API_USERNAME and WRMS_API_TOKEN are set in .env and run') }}
                                <code class="bg-gray-100 px-1 rounded">php artisan config:clear</code>.
                            @endif
                            {{ __('Check') }} <code class="bg-gray-100 px-1 rounded">storage/logs/laravel.log</code> {{ __('for details.') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Warehouses --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Warehouses') }}</h3>
                <div class="overflow-x-auto">
                    @if(isset($errorWarehouses) && $errorWarehouses)
                        <p class="p-6 text-amber-700 text-sm">
                            {{ __('API error:') }} {{ $errorWarehouses }}
                        </p>
                    @elseif($warehouses->total() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('ID') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Code') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Grade') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Address') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('State') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('District') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Partner') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($warehouses as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $row['id'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['name'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm font-mono">{{ $row['code'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['grade'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['physical_address'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['state_id'] ?? null) ? ($row['state_id'][1] ?? $row['state_id'][0] ?? '—') : ($row['state_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['district_id'] ?? null) ? ($row['district_id'][1] ?? $row['district_id'][0] ?? '—') : ($row['district_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['partner_id'] ?? null) ? ($row['partner_id'][1] ?? $row['partner_id'][0] ?? '—') : ($row['partner_id'] ?? '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <x-table-pagination :paginator="$warehouses" />
                    @else
                        <p class="p-6 text-gray-500 text-sm">
                            {{ __('No warehouses returned.') }}
                            {{ __('Check credentials and') }} <code class="bg-gray-100 px-1 rounded">storage/logs/laravel.log</code> {{ __('if this is unexpected.') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Operators --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Operators') }}</h3>
                <div class="overflow-x-auto">
                    @if(isset($errorOperators) && $errorOperators)
                        <p class="p-6 text-amber-700 text-sm">
                            {{ __('API error:') }} {{ $errorOperators }}
                        </p>
                    @elseif($operators->total() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('ID') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Email') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Phone') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration No') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('State') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('District') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Country') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($operators as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $row['id'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['name'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['email'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['phone'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row['registration_number'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['state_id'] ?? null) ? ($row['state_id'][1] ?? $row['state_id'][0] ?? '—') : ($row['state_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['district_id'] ?? null) ? ($row['district_id'][1] ?? $row['district_id'][0] ?? '—') : ($row['district_id'] ?? '—') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ is_array($row['country_id'] ?? null) ? ($row['country_id'][1] ?? $row['country_id'][0] ?? '—') : ($row['country_id'] ?? '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <x-table-pagination :paginator="$operators" />
                    @else
                        <p class="p-6 text-gray-500 text-sm">
                            {{ __('No operators returned.') }}
                            {{ __('Check credentials and') }} <code class="bg-gray-100 px-1 rounded">storage/logs/laravel.log</code> {{ __('if this is unexpected.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
