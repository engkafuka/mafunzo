<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Interview User') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Creates an interview-only account — no training or application management access.') }}</p>
        </div>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl max-w-2xl">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="mb-4">
                <x-back-link :href="route('interview.user-roles.index')">{{ __('Back to Interview Users') }}</x-back-link>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('interview.user-roles.store-user') }}" class="space-y-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="first_name" :value="__('First Name')" />
                            <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="middle_name" :value="__('Middle Name')" />
                            <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name')" />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="last_name" :value="__('Last Name')" />
                            <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                        <x-password-requirements />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Interview roles') }} <span class="text-red-500">*</span></p>
                        <p class="text-xs text-gray-500 mb-3">{{ __('Select one or more. A panelist who is also chair should tick both Panelist and Chairperson.') }}</p>
                        <div class="space-y-2 border border-gray-200 rounded-lg p-3">
                            @foreach($rolesConfig as $key => $label)
                                @php
                                    $descriptions = [
                                        'admin' => __('Create sessions, companies, question sets, assign panelists'),
                                        'panelist' => __('Score responses privately and submit'),
                                        'chair' => __('Review consolidated results and recommend'),
                                        'approver' => __('Approve or reject the final decision'),
                                        'viewer' => __('Read-only access to sessions and PDFs'),
                                    ];
                                @endphp
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="roles[]" value="{{ $key }}"
                                           @checked(in_array($key, old('roles', []), true))
                                           class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>
                                        <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                                        <span class="block text-xs text-gray-500">{{ $descriptions[$key] ?? '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm font-semibold">
                            {{ __('Create interview user') }}
                        </button>
                        <a href="{{ route('interview.user-roles.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
