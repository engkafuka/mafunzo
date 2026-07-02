<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registration status') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if($user->hasRejectedRegistration())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                    <h3 class="text-lg font-semibold text-red-800">{{ __('Registration rejected') }}</h3>
                    <p class="mt-2 text-gray-700">{{ __('Your registration was reviewed and could not be approved.') }}</p>
                    @if($user->registration_rejection_reason)
                        <div class="mt-4 p-4 rounded-md bg-red-50 text-red-900 text-sm">
                            <p class="font-medium">{{ __('Reason') }}:</p>
                            <p class="mt-1">{{ $user->registration_rejection_reason }}</p>
                        </div>
                    @endif
                    <p class="mt-4 text-sm text-gray-600">{{ __('Please contact WRRB staff if you need assistance, or register again with corrected information.') }}</p>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-amber-500">
                    <h3 class="text-lg font-semibold text-amber-900">{{ __('Registration pending verification') }}</h3>
                    <p class="mt-2 text-gray-700">
                        {{ __('Thank you for registering. Your account is awaiting verification by WRRB staff. You can log in, but training applications and profile updates will be available after approval.') }}
                    </p>
                    <dl class="mt-6 grid gap-3 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">{{ __('Category') }}</dt>
                            <dd class="mt-0.5 text-gray-900">{{ \App\Models\User::registrationCategoryOptions()[$user->registration_category] ?? $user->registration_category }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">{{ __('Submitted') }}</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-4">
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ __('Go to dashboard') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-gray-900 font-medium text-sm">{{ __('Log out') }}</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
