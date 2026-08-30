<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Question sets') }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts') @include('interview._nav')
        <div class="mb-4"><a href="{{ route('interview.question-sets.create') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase">{{ __('Add question set') }}</a></div>
        <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Title') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Version') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Questions') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Active') }}</th>
                    <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($questionSets as $set)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium">{{ $set->title }}</td>
                        <td class="px-4 py-3 text-sm">{{ $set->version }}</td>
                        <td class="px-4 py-3 text-sm">{{ $set->questions_count }}</td>
                        <td class="px-4 py-3 text-sm">{{ $set->is_active ? __('Yes') : __('No') }}</td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <a href="{{ route('interview.question-sets.show', $set) }}" class="text-indigo-600">{{ __('Manage questions') }}</a>
                            <a href="{{ route('interview.question-sets.edit', $set) }}" class="text-gray-600">{{ __('Edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No question sets yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $questionSets->links() }}</div>
        </div>
    </div></div>
</x-app-layout>
