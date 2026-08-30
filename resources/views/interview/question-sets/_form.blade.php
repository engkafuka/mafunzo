@php($questionSet = $questionSet ?? null)
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
    <input type="text" name="title" value="{{ old('title', $questionSet?->title) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Version') }}</label>
    <input type="text" name="version" value="{{ old('version', $questionSet?->version ?? '1.0') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
    <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $questionSet?->description) }}</textarea>
</div>
<div class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $questionSet?->is_active ?? true))>
    <label for="is_active" class="text-sm text-gray-700">{{ __('Active') }}</label>
</div>
