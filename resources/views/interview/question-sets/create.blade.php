<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create question set') }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts') @include('interview._nav')
        <div class="bg-white shadow-sm sm:rounded-lg p-6 max-w-2xl">
            <form method="POST" action="{{ route('interview.question-sets.store') }}" class="space-y-4">
                @csrf
                @include('interview.question-sets._form')
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Create') }}</button>
            </form>
        </div>
    </div></div>
</x-app-layout>
