<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Course') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('courses.store') }}" class="space-y-6">
                        @csrf
                        @include('courses._form')
                        <div class="flex items-center justify-between pt-4">
                            <a href="{{ route('courses.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                            <x-primary-button type="submit">{{ __('Create Course') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
