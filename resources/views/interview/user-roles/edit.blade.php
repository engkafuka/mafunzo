<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Interview User') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $user->name }}</p>
        </div>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl max-w-2xl">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="mb-4">
                <x-back-link :href="route('interview.user-roles.index')">{{ __('Back to Interview Users') }}</x-back-link>
            </div>

            @unless($user->isInterviewOnly())
                <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-100 text-sm text-amber-900">
                    {{ __('This is a shared training account. Name, email, and phone are shown for reference — change them in Application Management → Users.') }}
                </div>
            @endunless

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('interview.user-roles.update', $user) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="first_name" :value="__('First Name')" />
                            <x-text-input id="first_name" class="block mt-1 w-full {{ $user->isInterviewOnly() ? '' : 'bg-gray-50' }}"
                                          type="text" name="first_name"
                                          :value="old('first_name', $user->displayFirstName())"
                                          :readonly="! $user->isInterviewOnly()"
                                          :required="$user->isInterviewOnly()"
                                          autofocus />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="middle_name" :value="__('Middle Name')" />
                            <x-text-input id="middle_name" class="block mt-1 w-full {{ $user->isInterviewOnly() ? '' : 'bg-gray-50' }}"
                                          type="text" name="middle_name"
                                          :value="old('middle_name', $user->displayMiddleName())"
                                          :readonly="! $user->isInterviewOnly()" />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="last_name" :value="__('Last Name')" />
                            <x-text-input id="last_name" class="block mt-1 w-full {{ $user->isInterviewOnly() ? '' : 'bg-gray-50' }}"
                                          type="text" name="last_name"
                                          :value="old('last_name', $user->displayLastName())"
                                          :readonly="! $user->isInterviewOnly()"
                                          :required="$user->isInterviewOnly()" />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full {{ $user->isInterviewOnly() ? '' : 'bg-gray-50' }}"
                                      type="email" name="email"
                                      :value="old('email', $user->email)"
                                      :readonly="! $user->isInterviewOnly()"
                                      :required="$user->isInterviewOnly()" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" class="block mt-1 w-full {{ $user->isInterviewOnly() ? '' : 'bg-gray-50' }}"
                                      type="text" name="phone"
                                      :value="old('phone', $user->phone)"
                                      :readonly="! $user->isInterviewOnly()" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    @if($user->isInterviewOnly())
                        <div>
                            <x-input-label for="password" :value="__('New Password (leave blank to keep current)')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" value="" autocomplete="new-password" />
                            <x-password-requirements />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" value="" autocomplete="new-password" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="interview_status" :value="__('Interview status')" />
                        <select id="interview_status" name="interview_status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active" @selected(old('interview_status', $user->interview_status ?? 'active') === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected(old('interview_status', $user->interview_status ?? 'active') === 'inactive')>{{ __('Inactive') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            @if($user->isInterviewOnly())
                                {{ __('Inactive users cannot sign in.') }}
                            @else
                                {{ __('Inactive users keep their training access but lose interview module access.') }}
                            @endif
                        </p>
                        <x-input-error :messages="$errors->get('interview_status')" class="mt-2" />
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Interview roles') }}</p>
                        <p class="text-xs text-gray-500 mb-3">{{ __('Select one or more. Users with no roles cannot access the interview module.') }}</p>
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
                                           @checked(in_array($key, old('roles', $user->interviewRoles->pluck('role')->all()), true))
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
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-semibold">
                            {{ __('Save changes') }}
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
