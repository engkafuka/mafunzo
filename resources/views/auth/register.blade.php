<x-guest-layout register>
    <style>
        .reg-action-btn {
            background-color: #086090 !important;
            color: #ffffff !important;
        }
        .reg-action-btn:hover {
            background-color: #065078 !important;
        }
    </style>
    @php
        $initialStep = 1;
        if ($errors->hasAny(['password', 'password_confirmation'])) {
            $initialStep = 4;
        } elseif ($errors->hasAny(['education_level', 'education_program', 'education_program_other', 'education_institution', 'education_certificate', 'course_id', 'trained_year', 'legacy_registration_number', 'training_certificate'])) {
            $initialStep = 3;
        } elseif ($errors->hasAny(['first_name', 'middle_name', 'last_name', 'email', 'phone', 'region', 'district', 'gender', 'date_of_birth', 'position', 'company_or_private', 'company_name', 'company_address'])) {
            $initialStep = 2;
        } elseif ($errors->has('registration_category')) {
            $initialStep = 1;
        }
    @endphp
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden"
         x-data="{
             step: {{ $initialStep }},
             category: '{{ old('registration_category', 'new_applicant') }}',
             company_or_private: '{{ old('company_or_private', '') }}',
             education_program: '{{ old('education_program', '') }}',
             validateStep(current) {
                 const form = this.$refs.regForm;
                 const fields = form.querySelectorAll('[data-step=\'' + current + '\']');
                 for (const field of fields) {
                     if (field.disabled) continue;
                     if (!field.checkValidity()) {
                         field.reportValidity();
                         return false;
                     }
                 }
                 return true;
             },
             nextStep() {
                 if (this.validateStep(this.step)) {
                     this.step = Math.min(this.step + 1, 4);
                 }
             },
             prevStep() {
                 this.step = Math.max(this.step - 1, 1);
             },
             goToStep(n) {
                 if (n < this.step || this.validateStep(this.step)) {
                     this.step = n;
                 }
             }
         }">

        {{-- Card header --}}
        <div class="px-6 py-5 sm:px-8 bg-[#0a71ab] text-white text-center">
            <p class="text-sm text-white/90">{{ __('Complete all steps. Staff will verify your registration before you can apply.') }}</p>
        </div>

        {{-- Progress --}}
        <div class="px-6 sm:px-8 pt-6 pb-2">
            <div class="flex items-center">
                @foreach([
                    1 => __('Category'),
                    2 => __('Personal'),
                    3 => __('Details'),
                    4 => __('Account'),
                ] as $num => $label)
                    @if(!$loop->first)
                        <div class="h-0.5 flex-1 mx-1 rounded-full transition-colors duration-300"
                             :class="step > {{ $num - 1 }} ? 'bg-[#0a71ab]' : 'bg-gray-200'"></div>
                    @endif
                    <button type="button" @click="goToStep({{ $num }})"
                            class="flex flex-col items-center gap-1.5 shrink-0 group"
                            :class="step >= {{ $num }} ? 'text-[#0a71ab]' : 'text-gray-400'">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold transition-all duration-300"
                              :class="step === {{ $num }} ? 'bg-[#0a71ab] text-white ring-4 ring-[#0a71ab]/20 scale-110' : (step > {{ $num }} ? 'bg-[#0a71ab] text-white' : 'bg-gray-200 text-gray-500 group-hover:bg-gray-300')">
                            <span x-show="step <= {{ $num }}">{{ $num }}</span>
                            <svg x-show="step > {{ $num }}" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span class="text-[10px] sm:text-xs font-medium">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" x-ref="regForm" class="px-6 sm:px-8 pb-8">
            @csrf

            {{-- Step 1: Category --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">{{ __('I am registering as') }}</legend>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Choose the option that best describes you.') }}</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach(\App\Models\User::registrationCategoryOptions() as $value => $label)
                            <label class="relative flex cursor-pointer flex-col rounded-xl border-2 p-4 transition-all duration-150"
                                   :class="category === '{{ $value }}'
                                       ? 'border-[#0a71ab] bg-[#0a71ab]/10 ring-2 ring-[#0a71ab]/20 shadow-sm'
                                       : 'border-gray-200 bg-white hover:border-[#0a71ab]/40 hover:bg-gray-50'">
                                <input type="radio" name="registration_category" value="{{ $value }}"
                                       x-model="category" data-step="1" required
                                       class="sr-only">
                                <span class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                          :class="category === '{{ $value }}' ? 'bg-[#0a71ab] text-white' : 'bg-gray-100 text-gray-500'">
                                        @if($value === 'new_applicant')
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                        @else
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                        @endif
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                                        <span class="mt-0.5 block text-xs text-gray-500">
                                            @if($value === 'new_applicant')
                                                {{ __('First-time applicant with education background') }}
                                            @else
                                                {{ __('Already completed WRRB training before') }}
                                            @endif
                                        </span>
                                    </span>
                                </span>
                                <span x-show="category === '{{ $value }}'" x-cloak class="absolute top-3 right-3 text-[#0a71ab]">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('registration_category')" class="mt-3" />
                </fieldset>
            </div>

            {{-- Step 2: Personal details --}}
            <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('Personal details') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('All fields are required.') }}</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="first_name" :value="__('First Name')" />
                        <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" data-step="2" />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="middle_name" :value="__('Middle Name')" />
                        <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name')" required autocomplete="additional-name" data-step="2" />
                        <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" :value="__('Last Name')" />
                        <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" data-step="2" />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" data-step="2" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone Number')" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required placeholder="e.g. 0712345678" data-step="2" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="region" :value="__('Region')" />
                        <x-text-input id="region" class="block mt-1 w-full" type="text" name="region" :value="old('region')" required data-step="2" />
                        <x-input-error :messages="$errors->get('region')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="district" :value="__('District')" />
                        <x-text-input id="district" class="block mt-1 w-full" type="text" name="district" :value="old('district')" required data-step="2" />
                        <x-input-error :messages="$errors->get('district')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="gender" :value="__('Gender')" />
                        <select id="gender" name="gender" required data-step="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                            <option value="">{{ __('Select...') }}</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                        <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth" :value="old('date_of_birth')" required data-step="2" />
                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label for="position" :value="__('Position')" />
                    <select id="position" name="position" required data-step="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                        <option value="">{{ __('Select...') }}</option>
                        @foreach(\App\Models\TrainingApplication::positionOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('position') === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('position')" class="mt-2" />
                </div>

                <fieldset class="mt-4">
                    <legend class="text-sm font-medium text-gray-700">{{ __('Company / Private') }}</legend>
                    <div class="mt-2 flex flex-wrap gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm font-medium transition-all"
                               :class="company_or_private === 'company' ? 'border-[#0a71ab] bg-[#0a71ab]/10 text-[#085a89]' : 'border-gray-200 text-gray-700 hover:border-gray-300'">
                            <input type="radio" name="company_or_private" value="company" x-model="company_or_private" data-step="2" required class="text-[#0a71ab] focus:ring-[#0a71ab]">
                            {{ __('Company') }}
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm font-medium transition-all"
                               :class="company_or_private === 'private' ? 'border-[#0a71ab] bg-[#0a71ab]/10 text-[#085a89]' : 'border-gray-200 text-gray-700 hover:border-gray-300'">
                            <input type="radio" name="company_or_private" value="private" x-model="company_or_private" data-step="2" required class="text-[#0a71ab] focus:ring-[#0a71ab]">
                            {{ __('Private') }}
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('company_or_private')" class="mt-2" />
                </fieldset>

                <div x-show="company_or_private === 'company'" x-cloak x-transition class="mt-4 space-y-4 rounded-lg bg-gray-50 p-4 border border-gray-200">
                    <div>
                        <x-input-label for="company_name" :value="__('Company Name')" />
                        <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')"
                                      x-bind:required="company_or_private === 'company'" x-bind:disabled="company_or_private !== 'company'" data-step="2" />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="company_address" :value="__('Company Address')" />
                        <textarea id="company_address" name="company_address" rows="2"
                                  x-bind:required="company_or_private === 'company'" x-bind:disabled="company_or_private !== 'company'" data-step="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">{{ old('company_address') }}</textarea>
                        <x-input-error :messages="$errors->get('company_address')" class="mt-2" />
                    </div>
                </div>
            </div>

            {{-- Step 3: Category-specific --}}
            <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                {{-- New applicant --}}
                <div x-show="category === 'new_applicant'" x-transition>
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('Education background') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Provide your highest relevant qualification.') }}</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="education_level" :value="__('Education level')" />
                            <select id="education_level" name="education_level" data-step="3"
                                    x-bind:required="category === 'new_applicant'" x-bind:disabled="category !== 'new_applicant'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                                <option value="">{{ __('Select level') }}</option>
                                @foreach(\App\Models\EducationBackground::levelOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('education_level') === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('education_level')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="education_institution" :value="__('Institution')" />
                            <x-text-input id="education_institution" class="block mt-1 w-full" type="text" name="education_institution" :value="old('education_institution')"
                                          x-bind:required="category === 'new_applicant'" x-bind:disabled="category !== 'new_applicant'" data-step="3" />
                            <x-input-error :messages="$errors->get('education_institution')" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="education_program" :value="__('Program')" />
                        <select id="education_program" name="education_program" x-model="education_program" data-step="3"
                                x-bind:required="category === 'new_applicant'" x-bind:disabled="category !== 'new_applicant'"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                            <option value="">{{ __('Select program') }}</option>
                            @foreach(\App\Models\EducationBackground::programOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('education_program') === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('education_program')" class="mt-2" />
                    </div>
                    <div x-show="education_program === 'others'" x-cloak x-transition class="mt-4">
                        <x-input-label for="education_program_other" :value="__('Program specification')" />
                        <x-text-input id="education_program_other" class="block mt-1 w-full" type="text" name="education_program_other" :value="old('education_program_other')"
                                      x-bind:required="category === 'new_applicant' && education_program === 'others'" x-bind:disabled="category !== 'new_applicant'" data-step="3" />
                        <x-input-error :messages="$errors->get('education_program_other')" class="mt-2" />
                    </div>
                    <div class="mt-4">
                        <x-input-label for="education_certificate" :value="__('Education certificate (certified by advocate)')" />
                        <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-6 transition hover:border-[#0a71ab]/60 hover:bg-[#0a71ab]/5">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span class="mt-2 text-sm text-gray-600">{{ __('Click to upload certificate') }}</span>
                            <span class="mt-1 text-xs text-gray-400">{{ __('PDF or image, max 5MB') }}</span>
                            <input id="education_certificate" name="education_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" data-step="3"
                                   x-bind:required="category === 'new_applicant'" x-bind:disabled="category !== 'new_applicant'"
                                   class="sr-only" onchange="this.closest('label').querySelector('[data-filename]').textContent = this.files[0]?.name || ''">
                            <span data-filename class="mt-2 text-xs font-medium text-[#0a71ab]"></span>
                        </label>
                        <x-input-error :messages="$errors->get('education_certificate')" class="mt-2" />
                    </div>
                </div>

                {{-- Trained person --}}
                <div x-show="category === 'trained_person'" x-cloak x-transition>
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('Previous training') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Details of your prior WRRB training.') }}</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="course_id" :value="__('Course trained')" />
                            <select id="course_id" name="course_id" data-step="3"
                                    x-bind:required="category === 'trained_person'" x-bind:disabled="category !== 'trained_person'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                                <option value="">{{ __('Select course') }}</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ (string) old('course_id') === (string) $course->id ? 'selected' : '' }}>
                                        {{ $course->name }} ({{ $course->session_year }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="trained_year" :value="__('Year trained')" />
                            <x-text-input id="trained_year" class="block mt-1 w-full" type="number" name="trained_year" :value="old('trained_year')"
                                          min="2000" max="2100" data-step="3"
                                          x-bind:required="category === 'trained_person'" x-bind:disabled="category !== 'trained_person'" />
                            <x-input-error :messages="$errors->get('trained_year')" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="legacy_registration_number" :value="__('Previous registration number')" />
                        <x-text-input id="legacy_registration_number" class="block mt-1 w-full" type="text" name="legacy_registration_number" :value="old('legacy_registration_number')"
                                      x-bind:required="category === 'trained_person'" x-bind:disabled="category !== 'trained_person'" data-step="3" />
                        <x-input-error :messages="$errors->get('legacy_registration_number')" class="mt-2" />
                    </div>
                    <div class="mt-4">
                        <x-input-label for="training_certificate" :value="__('Training certificate')" />
                        <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-6 transition hover:border-[#0a71ab]/60 hover:bg-[#0a71ab]/5">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span class="mt-2 text-sm text-gray-600">{{ __('Click to upload certificate') }}</span>
                            <span class="mt-1 text-xs text-gray-400">{{ __('PDF or image, max 5MB') }}</span>
                            <input id="training_certificate" name="training_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" data-step="3"
                                   x-bind:required="category === 'trained_person'" x-bind:disabled="category !== 'trained_person'"
                                   class="sr-only" onchange="this.closest('label').querySelector('[data-filename]').textContent = this.files[0]?.name || ''">
                            <span data-filename class="mt-2 text-xs font-medium text-[#0a71ab]"></span>
                        </label>
                        <x-input-error :messages="$errors->get('training_certificate')" class="mt-2" />
                    </div>
                </div>
            </div>

            {{-- Step 4: Account --}}
            <div x-show="step === 4" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('Create your account') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Choose a secure password for login.') }}</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" data-step="4" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" data-step="4" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 rounded-lg bg-[#0a71ab]/10 border border-[#0a71ab]/20 p-4 text-sm text-[#054a72]">
                    <p class="font-medium">{{ __('Ready to submit?') }}</p>
                    <p class="mt-1 text-[#085a89]">{{ __('After registration, staff will review your details before you can apply for training.') }}</p>
                </div>
            </div>

            {{-- Navigation --}}
            <div class="mt-8 flex items-center justify-between gap-4 border-t border-gray-100 pt-6">
                <div>
                    <button type="button" x-show="step > 1" x-cloak @click="prevStep()"
                            class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('Back') }}
                    </button>
                    <a x-show="step === 1" x-cloak href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('Already registered?') }}</a>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" x-show="step < 4" x-cloak @click="nextStep()"
                            class="reg-action-btn inline-flex items-center gap-1 rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-[#086090] focus:ring-offset-2 transition">
                        {{ __('Continue') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="step === 4" x-cloak>
                        <x-primary-button type="submit" class="reg-action-btn !rounded-lg !text-white border-transparent">
                            {{ __('Register') }}
                        </x-primary-button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-guest-layout>
