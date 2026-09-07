<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit interview company') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="POST" action="{{ route('interview.companies.update', $company) }}" class="space-y-4 max-w-2xl">
                    @csrf
                    @method('PUT')
                    @include('interview.companies._form', ['company' => $company])
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Update company') }}</button>
                </form>

                <div class="max-w-2xl border-t pt-4 flex flex-wrap gap-3">
                    @if($company->canBeDeleted())
                        <form method="POST"
                              action="{{ route('interview.companies.destroy', $company) }}"
                              onsubmit="return confirm(@js(__('Permanently delete this company? This cannot be undone.')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold">{{ __('Delete company') }}</button>
                        </form>
                    @elseif($company->status === 'active')
                        <form method="POST"
                              action="{{ route('interview.companies.deactivate', $company) }}"
                              onsubmit="return confirm(@js(__('This company has interview sessions. Deactivate it instead of deleting?')))">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md text-sm font-semibold">{{ __('Deactivate company') }}</button>
                        </form>
                        <p class="w-full text-sm text-gray-500">{{ __('Delete is blocked because this company has :count session(s).', ['count' => $company->sessions_count]) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
