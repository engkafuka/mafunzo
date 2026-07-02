@props(['class' => ''])

<a href="{{ route('registration.resubmit') }}"
   {{ $attributes->merge(['class' => 'reg-resubmit-btn inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition '.$class]) }}>
    {{ $slot->isEmpty() ? __('Update application') : $slot }}
</a>
