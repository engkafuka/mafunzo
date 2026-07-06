<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Audit Entry') }}
            </h2>
            <a href="{{ route('audit-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                {{ __('Back to audit trail') }}
            </a>
        </div>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-4xl space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('When') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $auditLog->created_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('Changed by') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $auditLog->user_name ?? '—' }}
                            @if($auditLog->user_role)
                                <span class="text-gray-500">({{ __(ucfirst(str_replace('_', ' ', $auditLog->user_role))) }})</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('Event') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $auditLog->eventLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('Subject') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $auditLog->subjectLabel() }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $auditLog->description }}</dd>
                    </div>
                    @if($auditLog->ip_address)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('IP address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $auditLog->ip_address }}</dd>
                        </div>
                    @endif
                    @if($auditLog->url)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('Request URL') }}</dt>
                            <dd class="mt-1 text-sm text-gray-600 break-all">{{ $auditLog->url }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if($auditLog->old_values || $auditLog->new_values)
                <div class="grid gap-6 lg:grid-cols-2">
                    @if($auditLog->old_values)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Previous values') }}</h3>
                            <dl class="space-y-2">
                                @foreach($auditLog->old_values as $key => $value)
                                    <div class="border-b border-gray-100 pb-2">
                                        <dt class="text-xs font-medium text-gray-500">{{ $key }}</dt>
                                        <dd class="mt-0.5 text-sm text-gray-900 break-words">{{ is_scalar($value) || $value === null ? ($value ?? '—') : json_encode($value) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                    @if($auditLog->new_values)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('New values') }}</h3>
                            <dl class="space-y-2">
                                @foreach($auditLog->new_values as $key => $value)
                                    <div class="border-b border-gray-100 pb-2">
                                        <dt class="text-xs font-medium text-gray-500">{{ $key }}</dt>
                                        <dd class="mt-0.5 text-sm text-gray-900 break-words">{{ is_scalar($value) || $value === null ? ($value ?? '—') : json_encode($value) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
