<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('interview.dashboard') }}" class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('interview.dashboard') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
        {{ __('Dashboard') }}
    </a>
    <a href="{{ route('interview.sessions.index') }}" class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('interview.sessions.*') || request()->routeIs('interview.scoring.*') || request()->routeIs('interview.review.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
        {{ __('Sessions') }}
    </a>
    @if(Auth::user()->hasInterviewRole('admin'))
        <a href="{{ route('interview.companies.index') }}" class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('interview.companies.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
            {{ __('Companies') }}
        </a>
        <a href="{{ route('interview.question-sets.index') }}" class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('interview.question-sets.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
            {{ __('Question sets') }}
        </a>
        <a href="{{ route('interview.user-roles.index') }}" class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('interview.user-roles.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
            {{ __('Users') }}
        </a>
    @endif
</div>
