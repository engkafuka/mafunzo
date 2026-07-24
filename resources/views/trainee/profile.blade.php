<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My profile') }}
        </h2>
    </x-slot>

    @php
        $oldEducation = old('education');
        if ($oldEducation) {
            $educationInitial = collect($oldEducation)->values()->map(function ($row, $index) {
                return [
                    'id' => $index,
                    'record_id' => $row['id'] ?? '',
                    'level' => $row['level'] ?? '',
                    'program' => $row['program'] ?? '',
                    'program_other' => $row['program_other'] ?? '',
                    'institution' => $row['institution'] ?? '',
                    'filename' => ! empty($row['id']) ? __('Existing file kept') : '',
                    'existing_certificate' => ! empty($row['id']),
                ];
            })->all();
        } else {
            $educationInitial = $user->educationBackgrounds->values()->map(function ($background, $index) {
                return [
                    'id' => $index,
                    'record_id' => (string) $background->id,
                    'level' => $background->level,
                    'program' => $background->program,
                    'program_other' => $background->program_other ?? '',
                    'institution' => $background->institution,
                    'filename' => $background->certificate_path ? __('Existing file kept') : '',
                    'existing_certificate' => (bool) $background->certificate_path,
                ];
            })->all();
        }

        if ($educationInitial === []) {
            $educationInitial = [[
                'id' => 0,
                'record_id' => '',
                'level' => $user->isTrainedPerson() ? \App\Models\EducationBackground::LEVEL_WRRB_CERTIFICATE : '',
                'program' => $user->isTrainedPerson() ? 'others' : '',
                'program_other' => $user->isTrainedPerson() ? 'WRRB Certificate' : '',
                'institution' => $user->isTrainedPerson() ? 'WRRB' : '',
                'filename' => '',
                'existing_certificate' => false,
            ]];
        }
    @endphp

    <div class="page-shell">
        <div class="page-inner-4xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden"
                 x-data='{
                     category: @json($user->registration_category),
                     company_or_private: @json(old('company_or_private', $user->company_or_private)),
                     educationEntries: @json($educationInitial),
                     educationNextId: {{ count($educationInitial) }},
                     addEducation() {
                         this.educationEntries.push({
                             id: this.educationNextId++,
                             record_id: "",
                             level: "",
                             program: "",
                             program_other: "",
                             institution: "",
                             filename: "",
                             existing_certificate: false,
                         });
                     },
                     removeEducation(index) {
                         if (this.educationEntries.length > 1) {
                             this.educationEntries.splice(index, 1);
                         }
                     },
                 }'>
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Update your profile') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Keep your personal details up to date. Changes here are used when you apply for training courses.') }}</p>
                </div>

                <form method="POST" action="{{ route('trainee.profile.update') }}" enctype="multipart/form-data" class="p-6 space-y-8">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-medium">{{ __('Please fix the following and try again:') }}</p>
                            <ul class="mt-2 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <section>
                        <h4 class="text-sm font-semibold text-gray-900">{{ __('Personal information') }}</h4>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <x-input-label for="first_name" :value="__('First Name')" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name', $user->first_name)" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="middle_name" :value="__('Middle Name')" />
                                <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name', $user->middle_name)" />
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_name" :value="__('Last Name')" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name', $user->last_name)" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" :value="__('Phone Number')" />
                                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $user->phone)" required />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>

                        <x-tanzania-location-fields
                            :region="old('region', $user->region)"
                            :district="old('district', $user->district)"
                            region-class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            district-class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach(['male' => __('Male'), 'female' => __('Female')] as $value => $label)
                                        <option value="{{ $value }}" {{ old('gender', $user->gender) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth" :value="old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="position" :value="__('Position')" />
                            <select id="position" name="position" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Select...') }}</option>
                                @foreach(\App\Models\TrainingApplication::positionOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('position', $user->position) === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('position')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="profile_photo" :value="__('Profile photo (passport style)')" />
                            @if($user->hasProfilePhoto())
                                <div class="mt-2 flex items-center gap-4">
                                    <img src="{{ route('profile-photos.show', $user) }}" alt="{{ __('Current profile photo') }}" class="h-24 w-24 rounded-lg object-cover border border-gray-200">
                                    <p class="text-sm text-gray-600">{{ __('Upload a new photo to replace the current one.') }}</p>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-amber-700">{{ __('A passport-style photo is required for your warehouse identity card.') }}</p>
                            @endif
                            <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png"
                                   @if(! $user->hasProfilePhoto()) required @endif
                                   class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-500">{{ __('JPG or PNG, max 2 MB.') }}</p>
                            <x-input-error :messages="$errors->get('profile_photo')" class="mt-2" />
                        </div>

                        <fieldset class="mt-4">
                            <legend class="text-sm font-medium text-gray-700">{{ __('Company / Private') }}</legend>
                            <div class="mt-2 flex flex-wrap gap-3">
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm font-medium transition-all"
                                       :class="company_or_private === 'company' ? 'border-indigo-600 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-700'">
                                    <input type="radio" name="company_or_private" value="company" x-model="company_or_private" required class="text-indigo-600 focus:ring-indigo-500">
                                    {{ __('Company') }}
                                </label>
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm font-medium transition-all"
                                       :class="company_or_private === 'private' ? 'border-indigo-600 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-700'">
                                    <input type="radio" name="company_or_private" value="private" x-model="company_or_private" required class="text-indigo-600 focus:ring-indigo-500">
                                    {{ __('Private') }}
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('company_or_private')" class="mt-2" />
                        </fieldset>

                        <div x-show="company_or_private === 'company'" x-cloak class="mt-4 space-y-4 rounded-lg bg-gray-50 p-4 border border-gray-200">
                            <div>
                                <x-input-label for="company_name" :value="__('Company Name')" />
                                <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name', $user->company_name)"
                                              x-bind:required="company_or_private === 'company'" />
                                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="company_address" :value="__('Company Address')" />
                                <textarea id="company_address" name="company_address" rows="2"
                                          x-bind:required="company_or_private === 'company'"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('company_address', $user->company_address) }}</textarea>
                                <x-input-error :messages="$errors->get('company_address')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    @if($user->isNewApplicant())
                        <section>
                            <x-education-background-repeater :step="1" />
                        </section>
                    @else
                        <section class="space-y-6">
                            @if($legacyApplication?->course)
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm">
                                    <p class="font-medium text-gray-900">{{ __('Course previously trained') }}</p>
                                    <p class="mt-1 text-gray-700">{{ $legacyApplication->course->displayNameWithSession() }}</p>
                                    <p class="mt-2 text-xs text-gray-500">{{ __('Examination scores for this course are managed by WRRB staff after registration approval. Contact staff if the course needs to be corrected.') }}</p>
                                </div>
                            @endif
                            <x-education-background-repeater :step="1" :include-wrrb="true" />
                        </section>
                    @endif

                    <div class="flex flex-wrap items-center gap-4 pt-2 border-t border-gray-100">
                        <x-primary-button type="submit">{{ __('Save profile') }}</x-primary-button>
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Go to dashboard') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
