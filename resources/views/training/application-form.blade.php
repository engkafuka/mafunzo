<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Application Form') }} — {{ $course->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form method="POST" action="{{ route('training.apply.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">

                        <div class="grid gap-6 sm:grid-cols-3">
                            <div>
                                <x-input-label for="first_name" :value="__('First Name')" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name"
                                    :value="old('first_name', $user->first_name)" required autofocus />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="middle_name" :value="__('Middle Name')" />
                                <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name"
                                    :value="old('middle_name', $user->middle_name)" />
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_name" :value="__('Last Name')" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name"
                                    :value="old('last_name', $user->last_name ?? '')" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone Number')" />
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone"
                                :value="old('phone')" required placeholder="e.g. 0712345678" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="region" :value="__('Region')" />
                                <x-text-input id="region" class="block mt-1 w-full" type="text" name="region"
                                    :value="old('region')" required />
                                <x-input-error :messages="$errors->get('region')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="district" :value="__('District')" />
                                <x-text-input id="district" class="block mt-1 w-full" type="text" name="district"
                                    :value="old('district')" required />
                                <x-input-error :messages="$errors->get('district')" class="mt-2" />
                            </div>
                        </div>

                        <div x-data="{ company_or_private: '{{ old('company_or_private', '') }}' }">
                            <x-input-label for="company_or_private" :value="__('Company / Private')" />
                            <select id="company_or_private" name="company_or_private"
                                x-model="company_or_private"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select...') }}</option>
                                <option value="company" {{ old('company_or_private') === 'company' ? 'selected' : '' }}>{{ __('Company') }}</option>
                                <option value="private" {{ old('company_or_private') === 'private' ? 'selected' : '' }}>{{ __('Private') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('company_or_private')" class="mt-2" />

                            <div x-show="company_or_private === 'company'" x-cloak x-transition class="mt-4 space-y-4">
                                <div>
                                    <x-input-label for="company_name" :value="__('Company Name')" />
                                    <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name"
                                        :value="old('company_name')" />
                                    <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="company_address" :value="__('Company Address')" />
                                    <textarea id="company_address" name="company_address" rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('company_address') }}</textarea>
                                    <x-input-error :messages="$errors->get('company_address')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="gender" :value="__('Gender')" />
                            <select id="gender" name="gender"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select...') }}</option>
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                                <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                            <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth"
                                :value="old('date_of_birth')" required />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="position" :value="__('Position')" />
                            <select id="position" name="position"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select...') }}</option>
                                @foreach(\App\Models\TrainingApplication::positionOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('position') === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('position')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <a href="{{ route('training.select-course') }}" class="text-gray-600 hover:text-gray-900">
                                {{ __('Back to courses') }}
                            </a>
                            <x-primary-button type="submit">{{ __('Submit Application') }}</x-primary-button>
                        </div>
                        </form>
                    </div>
                </div>
                {{-- Right column: Course summary --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 h-fit">
                    <h3 class="font-medium text-gray-900 mb-4">{{ __('Course') }}</h3>
                    <p class="text-lg font-semibold text-indigo-700">{{ $course->name }}</p>
                    @if($course->code)
                        <p class="text-sm text-gray-500 mt-1">{{ $course->code }}</p>
                    @endif
                    @if($course->description)
                        <p class="text-gray-600 mt-3 text-sm">{{ $course->description }}</p>
                    @endif
                    <p class="mt-4 text-sm text-gray-500">{{ __('Fill in your details on the left and submit to apply for this training.') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
