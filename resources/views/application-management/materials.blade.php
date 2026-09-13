<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training materials') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-6xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <x-back-link :href="route('app-management.index')">
                    {{ __('Back to Application Management') }}
                </x-back-link>
            </div>

            <form method="GET" class="mb-6">
                <label class="text-sm font-medium text-gray-700">{{ __('Select course') }}</label>
                <select name="course_id" class="mt-1 rounded-md border-gray-300" onchange="this.form.submit()">
                    <option value="">{{ __('— Select course —') }}</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ ($courseId ?? '') == $course->id ? 'selected' : '' }}>
                            {{ $course->displayNameWithSession() }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if($selectedCourse)
                <div class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-medium uppercase text-gray-500">{{ __('Total materials') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $materials->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-medium uppercase text-amber-800">{{ __('Draft') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-950">{{ $materials->whereNull('published_at')->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p class="text-xs font-medium uppercase text-green-800">{{ __('Eligible trainees') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-green-950">{{ $traineeCount ?? 0 }}</p>
                    </div>
                </div>

                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
                    <h3 class="font-medium text-gray-900">{{ __('Upload material') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Upload timetables, slides, handouts, and other training files. Each item is published separately.') }}
                    </p>
                    <form method="POST" action="{{ route('app-management.materials.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourse->id }}">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
                            <input type="text" name="title" value="{{ old('title') }}" required maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                            <select name="category" required class="mt-1 block w-full rounded-md border-gray-300">
                                @foreach($categoryOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('File') }}</label>
                            <input type="file" name="file" required
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                                   class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-500">{{ __('PDF, Word, Excel, or PowerPoint. Max 20 MB.') }}</p>
                            @error('file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Description') }} <span class="text-gray-400">({{ __('optional') }})</span></label>
                            <textarea name="description" rows="2" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                {{ __('Upload material') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Title') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Category') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('File') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($materials as $material)
                                    <tr>
                                        <td class="px-4 py-3 align-top">
                                            <p class="font-medium text-gray-900">{{ $material->title }}</p>
                                            @if($material->description)
                                                <p class="mt-1 text-sm text-gray-500">{{ $material->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">{{ $material->categoryLabel() }}</td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-700">
                                            <p>{{ $material->original_filename }}</p>
                                            @if($material->formattedFileSize())
                                                <p class="text-xs text-gray-500">{{ $material->formattedFileSize() }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            @if($material->isPublished())
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                    {{ __('Published') }}
                                                </span>
                                                <p class="mt-1 text-xs text-gray-500">{{ $material->published_at?->format('Y-m-d H:i') }}</p>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                                    {{ __('Draft') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="flex flex-col gap-2">
                                                <a href="{{ route('app-management.materials.download', $material) }}"
                                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                                    {{ __('Download') }}
                                                </a>
                                                @if(($canPublish ?? false) && ! $material->isPublished())
                                                    <form method="POST" action="{{ route('app-management.materials.publish', $material) }}"
                                                          onsubmit="return confirm('{{ __('Publish this material to all eligible trainees? They will receive a notification.') }}');">
                                                        @csrf
                                                        <button type="submit" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                                            {{ __('Publish') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(($canPublish ?? false) && $material->isPublished())
                                                    <form method="POST" action="{{ route('app-management.materials.unpublish', $material) }}"
                                                          onsubmit="return confirm('{{ __('Unpublish this material? Trainees will no longer see it.') }}');">
                                                        @csrf
                                                        <button type="submit" class="text-sm font-medium text-amber-700 hover:text-amber-900">
                                                            {{ __('Unpublish') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('app-management.materials.replace', $material) }}" enctype="multipart/form-data" class="space-y-1">
                                                    @csrf
                                                    <input type="file" name="file" required
                                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                                                           class="block w-full max-w-xs text-xs text-gray-700">
                                                    <button type="submit" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                                        {{ __('Replace file') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('app-management.materials.destroy', $material) }}"
                                                      onsubmit="return confirm('{{ __('Delete this material?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                            {{ __('No materials uploaded for this course yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
