<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Certificates') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-6xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to Application Management') }}</a>
            </div>

            <div class="mb-6 bg-white shadow-sm sm:rounded-lg p-5 border border-gray-200">
                <h3 class="font-medium text-gray-900">{{ __('Managing Director signature') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Upload the signature image used on all training certificates (PNG or JPG).') }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-6">
                    @if($signatureUrl ?? null)
                        <img src="{{ $signatureUrl }}" alt="{{ __('Current signature') }}" class="h-16 object-contain border border-gray-200 rounded bg-gray-50 px-3 py-2">
                    @else
                        <p class="text-sm text-amber-700">{{ __('No signature uploaded yet.') }}</p>
                    @endif
                    <form method="POST" action="{{ route('app-management.certificates.signature') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="md_signature" class="block text-xs font-medium text-gray-600 mb-1">{{ __('Signature file') }}</label>
                            <input id="md_signature" type="file" name="md_signature" accept=".png,.jpg,.jpeg,.webp" required
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-[#0a71ab]/10 file:text-[#0a71ab]">
                            <x-input-error :messages="$errors->get('md_signature')" class="mt-1" />
                        </div>
                        <button type="submit" class="px-4 py-2 bg-[#0a71ab] text-white text-sm rounded-md hover:bg-[#086090]">{{ __('Upload signature') }}</button>
                    </form>
                </div>
            </div>

            <form method="GET" class="mb-6">
                <select name="course_id" class="rounded-md border-gray-300" onchange="this.form.submit()">
                    <option value="">{{ __('All courses') }}</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <x-responsive-table>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Certificate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($applications as $app)
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono">{{ $app->registration_number }}</td>
                                <td class="px-4 py-3 text-sm">{{ $app->first_name }} {{ $app->last_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $app->course->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($app->isEligibleForCertificate())
                                        <a href="{{ route('app-management.certificates.show', $app) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('View / Print') }}</a>
                                        @if(!$app->certificate_issued_at)
                                            <form method="POST" action="{{ route('app-management.certificates.issue', $app) }}" class="inline ms-2">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm">{{ __('Mark as issued') }}</button>
                                            </form>
                                        @else
                                            <span class="text-gray-500 text-xs">({{ __('Issued') }} {{ $app->certificate_issued_at->format('Y-m-d') }})</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">{{ __('Not eligible') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No eligible trainees.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </x-responsive-table>
                @if($applications->hasPages())
                <x-table-pagination :paginator="$applications" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
