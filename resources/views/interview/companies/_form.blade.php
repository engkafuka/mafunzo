@php($company = $company ?? null)

<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Company name') }}</label>
    <input type="text" name="name" value="{{ old('name', $company?->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Registration number') }}</label>
    <input type="text" name="registration_number" value="{{ old('registration_number', $company?->registration_number) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Contact person') }}</label>
    <input type="text" name="contact_person" value="{{ old('contact_person', $company?->contact_person) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Contact email') }}</label>
    <input type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Contact phone') }}</label>
    <input type="text" name="contact_phone" value="{{ old('contact_phone', $company?->contact_phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        <option value="active" @selected(old('status', $company?->status ?? 'active') === 'active')>{{ __('Active') }}</option>
        <option value="inactive" @selected(old('status', $company?->status) === 'inactive')>{{ __('Inactive') }}</option>
    </select>
</div>
