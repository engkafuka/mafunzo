@php
    $course = $course ?? null;
@endphp

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('Course Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $course?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="code" :value="__('Course Code')" />
        <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code', $course?->code)" placeholder="e.g. WMI-101" />
        <p class="mt-1 text-sm text-gray-500">{{ __('Same code can be reused in a different session year.') }}</p>
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-3">
    <div>
        <x-input-label for="session_year" :value="__('Session Year')" />
        <x-text-input id="session_year" class="block mt-1 w-full" type="number" name="session_year" min="2000" max="2100"
                      :value="old('session_year', $course?->session_year ?? date('Y'))" required />
        <p class="mt-1 text-sm text-gray-500">{{ __('Training intake year (e.g. 2026).') }}</p>
        <x-input-error :messages="$errors->get('session_year')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="application_opens_at" :value="__('Application Opens')" />
        <x-text-input id="application_opens_at" class="block mt-1 w-full" type="date" name="application_opens_at"
                      :value="old('application_opens_at', $course?->application_opens_at?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('application_opens_at')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="application_deadline_at" :value="__('Application Deadline')" />
        <x-text-input id="application_deadline_at" class="block mt-1 w-full" type="date" name="application_deadline_at"
                      :value="old('application_deadline_at', $course?->application_deadline_at?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('application_deadline_at')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="description" :value="__('Description')" />
    <textarea id="description" name="description" rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $course?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" type="checkbox" name="is_active" value="1"
           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
           {{ old('is_active', $course?->is_active ?? true) ? 'checked' : '' }}>
    <x-input-label for="is_active" :value="__('Active (course record enabled in the system)')" class="!mb-0" />
</div>
<x-input-error :messages="$errors->get('is_active')" class="mt-2" />
