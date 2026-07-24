@props([
    'courses',
    'selected' => null,
    'required' => true,
    'name' => 'prior_course_id',
    'dataStep' => null,
])

@php
    $selected = old($name, $selected);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <x-input-label :for="$name" :value="__('Course previously trained')" />
    <p class="text-sm text-gray-500">
        {{ __('Select the WRRB course session you already completed. Staff will record your examination scores for this course after registration is approved.') }}
    </p>
    <select id="{{ $name }}" name="{{ $name }}"
            @if($required) required @endif
            @if($dataStep) data-step="{{ $dataStep }}" @endif
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
        <option value="">{{ __('Select course') }}</option>
        @foreach($courses as $course)
            <option value="{{ $course->id }}" @selected((string) $selected === (string) $course->id)>
                {{ $course->displayNameWithSession() }}
                @if($course->code)
                    — {{ $course->code }}
                @endif
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
