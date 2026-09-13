<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training materials') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div
            class="page-inner-4xl space-y-6"
            x-data="{
                open: false,
                title: '',
                viewUrl: '',
                downloadUrl: '',
                isPdf: false,
                openViewer(title, viewUrl, downloadUrl, isPdf) {
                    this.title = title;
                    this.viewUrl = viewUrl;
                    this.downloadUrl = downloadUrl;
                    this.isPdf = isPdf;
                    this.open = true;
                    document.body.classList.add('overflow-hidden');
                },
                closeViewer() {
                    this.open = false;
                    this.viewUrl = '';
                    document.body.classList.remove('overflow-hidden');
                },
            }"
            @keydown.escape.window="if (open) closeViewer()"
        >
            <div>
                <x-back-link :href="route('training.my-applications')" class="text-sm font-medium">
                    {{ __('Back to my applications') }}
                </x-back-link>
            </div>

            @forelse($courseGroups as $group)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">{{ $group['course']->name }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Session') }} {{ $group['course']->session_year }}
                            @if($group['application']->registration_number)
                                · {{ $group['application']->registration_number }}
                            @endif
                        </p>
                    </div>
                    <div class="p-6 space-y-6">
                        @foreach($categoryOrder as $category)
                            @php($items = $group['materialsByCategory']->get($category, collect()))
                            @if($items->isNotEmpty())
                                <div>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                        {{ \App\Models\CourseMaterial::categoryOptions()[$category] ?? $category }}
                                    </h4>
                                    <ul class="mt-3 space-y-3">
                                        @foreach($items as $material)
                                            <li class="flex flex-wrap items-start justify-between gap-3 rounded-md border border-gray-200 p-4">
                                                <div class="min-w-0">
                                                    <p class="font-medium text-gray-900">{{ $material->title }}</p>
                                                    @if($material->description)
                                                        <p class="mt-1 text-sm text-gray-600">{{ $material->description }}</p>
                                                    @endif
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        {{ $material->original_filename }}
                                                        @if($material->formattedFileSize())
                                                            · {{ $material->formattedFileSize() }}
                                                        @endif
                                                        · {{ __('Published') }} {{ $material->published_at?->format('Y-m-d') }}
                                                    </p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <button type="button"
                                                            @click="openViewer(@js($material->title), @js(route('training.materials.view', $material)), @js(route('training.materials.download', $material)), @js($material->isPdf()))"
                                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                                        {{ __('View') }}
                                                    </button>
                                                    <a href="{{ route('training.materials.download', $material) }}"
                                                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                                        {{ __('Download') }}
                                                    </a>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500 text-sm">
                        {{ __('No training materials have been published for your applications yet.') }}
                    </p>
                </div>
            @endforelse

            <div
                x-show="open"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-modal="true"
                role="dialog"
                :aria-label="title"
            >
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="fixed inset-0 bg-black/60"
                        @click="closeViewer()"
                    ></div>
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between gap-3 p-4 border-b border-gray-200">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 truncate" x-text="title"></p>
                                <p class="text-sm text-gray-500">{{ __('Document preview') }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="downloadUrl"
                                    class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50"
                                >
                                    {{ __('Download') }}
                                </a>
                                <button
                                    type="button"
                                    @click="closeViewer()"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 text-2xl leading-none"
                                    aria-label="{{ __('Close') }}"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 min-h-0 p-4">
                            <template x-if="isPdf">
                                <iframe
                                    :src="viewUrl"
                                    class="w-full h-[75vh] rounded border border-gray-200 bg-gray-50"
                                    :title="title"
                                ></iframe>
                            </template>
                            <template x-if="! isPdf">
                                <div class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-16 text-center">
                                    <p class="text-sm text-gray-600">
                                        {{ __('This file type cannot be previewed in the browser.') }}
                                    </p>
                                    <a
                                        :href="downloadUrl"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                                    >
                                        {{ __('Download file') }}
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
