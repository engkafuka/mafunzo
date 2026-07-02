<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(Auth::user()->role === 'trainee')
                @if(Auth::user()->hasPendingRegistration())
                    <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-900">
                        <p class="font-medium">{{ __('Registration pending verification') }}</p>
                        <p class="mt-1 text-sm">{{ __('Your registration is awaiting staff approval. Training applications will be available once approved.') }}</p>
                        <a href="{{ route('registration.pending') }}" class="inline-block mt-2 text-sm font-medium text-amber-800 hover:text-amber-900">{{ __('View status') }} &rarr;</a>
                    </div>
                @elseif(Auth::user()->hasRejectedRegistration())
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-900">
                        <p class="font-medium">{{ __('Registration rejected') }}</p>
                        <p class="mt-1 text-sm">{{ __('Your registration could not be approved.') }}</p>
                        <a href="{{ route('registration.pending') }}" class="inline-block mt-2 text-sm font-medium text-red-800 hover:text-red-900">{{ __('View details') }} &rarr;</a>
                    </div>
                @else
                {{-- CSS Grid Layout: 5 grid items — row 1: 3 columns, row 2: 2 columns --}}
                <div class="dashboard-grid grid grid-cols-1 sm:grid-cols-3 sm:grid-rows-2 gap-6" role="grid" aria-label="{{ __('Dashboard cards') }}">
                    {{-- Grid item 1 --}}
                    <div class="dashboard-grid-item bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sm:col-start-1 sm:row-start-1" role="group" aria-label="{{ __('Training courses') }}">
                        <h3 class="font-medium text-gray-900 mb-3">{{ __('Training courses') }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            {{ __('The Warehouse Receipt Regulatory Board (WRRB) provides training courses to build capacity in warehouse receipt systems and related practices. Our courses are designed for professionals in agriculture, trade, and regulatory roles. Complete your profile and apply for the course that fits your needs.') }}
                        </p>
                    </div>

                    {{-- Grid item 2 --}}
                    <div class="dashboard-grid-item bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sm:col-start-2 sm:row-start-1" role="group" aria-label="{{ __('Current course') }}">
                        <h3 class="font-medium text-gray-900 mb-2">{{ __('Current course') }}</h3>
                        @if(isset($currentApplication) && $currentApplication)
                            <p class="text-indigo-700 font-semibold">{{ $currentApplication->course->name }}</p>
                            @if($currentApplication->registration_number)
                                <p class="text-sm text-gray-500 mt-1">{{ __('Registration number') }}: {{ $currentApplication->registration_number }}</p>
                            @endif
                            <p class="text-sm text-gray-500 mt-1">{{ __('Status') }}: {{ $currentApplication->application_review_status ?? $currentApplication->status }}</p>
                            <a href="{{ route('training.my-applications') }}" class="inline-block mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('View my applications') }} &rarr;</a>
                        @else
                            <p class="text-gray-500 text-sm">{{ __('You have not applied for any course yet.') }}</p>
                            <a href="{{ route('training.select-course') }}" class="inline-block mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Apply for training') }} &rarr;</a>
                        @endif
                    </div>

                    {{-- Grid item 3 --}}
                    <div class="dashboard-grid-item bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sm:col-start-3 sm:row-start-1" role="group" aria-label="{{ __('Next course') }}">
                        <h3 class="font-medium text-gray-900 mb-2">{{ __('Next course') }}</h3>
                        @if(isset($nextCourse) && $nextCourse)
                            <p class="text-gray-900 font-semibold">{{ $nextCourse->name }}</p>
                            @if($nextCourse->code)
                                <p class="text-sm text-gray-500 mt-1">{{ $nextCourse->code }} · {{ __('Session') }} {{ $nextCourse->session_year }}</p>
                            @else
                                <p class="text-sm text-gray-500 mt-1">{{ __('Session') }} {{ $nextCourse->session_year }}</p>
                            @endif
                            <a href="{{ route('training.apply', ['course_id' => $nextCourse->id]) }}" class="inline-block mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Apply for this course') }} &rarr;</a>
                        @else
                            <p class="text-gray-500 text-sm">{{ __('No courses are open for application at the moment.') }}</p>
                            <a href="{{ route('training.select-course') }}" class="inline-block mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('View all courses') }} &rarr;</a>
                        @endif
                    </div>

                    {{-- Grid item 4 --}}
                    <div class="dashboard-grid-item sm:col-span-2 sm:col-start-1 sm:row-start-2 bg-amber-50 border border-amber-200 overflow-hidden shadow-sm sm:rounded-lg p-6" role="group" aria-label="{{ __('Required information to apply') }}">
                        <h3 class="font-medium text-amber-900 mb-2">{{ __('Required information to apply') }}</h3>
                        <p class="text-amber-800 text-sm leading-relaxed">
                            {{ __('To apply for a course you need: your full name, email, phone, region, district, company or private, gender, date of birth, and position. You must also complete your profile with at least one education background and a certificate certified by an advocate. Ensure your profile is complete before applying.') }}
                        </p>
                        @if(!Auth::user()->profile_completed_at)
                            <a href="{{ route('trainee.profile.edit') }}" class="inline-block mt-3 text-sm font-medium text-amber-800 hover:text-amber-900">{{ __('Complete my profile') }} &rarr;</a>
                        @endif
                    </div>

                    {{-- Grid item 5 --}}
                    <div class="dashboard-grid-item sm:col-start-3 sm:row-start-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" role="group" aria-label="{{ __('Quick links') }}">
                        <h3 class="font-medium text-gray-900 mb-3">{{ __('Quick links') }}</h3>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('training.select-course') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ __('Apply for training') }}</a>
                            <a href="{{ route('training.my-applications') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ __('My applications') }}</a>
                            @if(Auth::user()->profile_completed_at)
                                <a href="{{ route('trainee.profile.edit') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ __('My profile') }}</a>
                            @else
                                <a href="{{ route('trainee.profile.edit') }}" class="text-amber-600 hover:text-amber-800 font-medium text-sm">{{ __('Complete my profile') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            @elseif(in_array(Auth::user()->role, ['super_admin', 'admin', 'staff']))
                {{-- Admin / staff dashboard --}}
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
                        <a href="{{ route('users.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('User Management') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ __('Create and manage system users and roles.') }}</p>
                        </a>
                        <a href="{{ route('courses.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Course Management') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ __('Initialize training courses for trainees to apply.') }}</p>
                            @if(isset($courseStats))
                                <p class="mt-2 text-indigo-600 font-medium">{{ $courseStats['published'] }} {{ __('published') }} / {{ $courseStats['total'] }} {{ __('total') }}</p>
                            @endif
                        </a>
                    @endif
                    <a href="{{ route('app-management.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Application Management') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Review applications, attendance, exams, and certificates.') }}</p>
                    </a>
                    {{-- WRMS / TMX modules hidden for now
                    @if(Auth::user()->role === 'super_admin')
                        <a href="{{ route('wrms-api.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('WRMS API Data') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ __('Browse warehouse receipt data from WRMS.') }}</p>
                        </a>
                        <a href="{{ route('tmx-auction.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('TMX Auction Data') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ __('View and export TMX auction delivery data.') }}</p>
                        </a>
                    @endif
                    --}}
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                    <h3 class="font-medium text-gray-900 mb-2">{{ __("You're logged in!") }}</h3>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
