<nav x-data="{ open: false }" class="bg-[#0a71ab] border-b border-[#086090]">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="h-10 w-10 sm:h-12 sm:w-12 md:h-14 md:w-14" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'staff'], true))
                        <x-nav-link :href="route('app-management.index')" :active="request()->routeIs('app-management.*') && ! request()->routeIs('app-management.reports.*')">
                            {{ __('Application Management') }}
                        </x-nav-link>
                        <x-nav-link :href="route('app-management.reports.index')" :active="request()->routeIs('app-management.reports.*')">
                            {{ __('Reports') }}
                        </x-nav-link>
                    @endif

                    @if(in_array(Auth::user()->role, ['super_admin', 'admin'], true))
                        <x-nav-link :href="route('courses.index')" :active="request()->routeIs('courses.*')">
                            {{ __('Course Management') }}
                        </x-nav-link>
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('User Management') }}
                        </x-nav-link>
                    @endif

                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                        {{ __('Notifications') }}
                        @if(($unread = Auth::user()->unreadNotifications()->count()) > 0)
                            <span class="ms-1 inline-flex items-center justify-center rounded-full bg-white px-1.5 py-0.5 text-[10px] font-semibold text-[#0a71ab]">{{ $unread }}</span>
                        @endif
                    </x-nav-link>

                    @if(Auth::user()->isTrainer())
                        <x-nav-link :href="route('trainer.exam-results')" :active="request()->routeIs('trainer.*')">
                            {{ __('Examination marks') }}
                        </x-nav-link>
                    @endif

                    @if(Auth::user()->role === 'trainee')
                        @if(Auth::user()->canResubmitRegistration())
                            <x-nav-link :href="route('registration.resubmit')" :active="request()->routeIs('registration.resubmit')">
                                {{ __('Update application') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link :href="route('training.select-course')" :active="request()->routeIs('training.*')">
                            {{ __('Apply for Training') }}
                        </x-nav-link>
                        <x-nav-link :href="route('training.my-applications')" :active="request()->routeIs('training.my-applications')">
                            {{ __('My Applications') }}
                        </x-nav-link>
                        @if(Auth::user()->hasApprovedRegistration())
                            <x-nav-link :href="route('training.exam-results')" :active="request()->routeIs('training.exam-results')">
                                {{ __('Exam results') }}
                            </x-nav-link>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-[#0a71ab] hover:bg-[#086090] focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="Auth::user()->role === 'trainee' ? route('trainee.profile.edit') : route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @if(in_array(Auth::user()->role, ['super_admin', 'admin'], true))
                            <x-dropdown-link :href="route('audit-logs.index')">
                                {{ __('Audit Trail') }}
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-white hover:bg-[#086090] focus:outline-none focus:bg-[#086090] focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-[#086090]">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if(in_array(Auth::user()->role, ['super_admin', 'admin', 'staff'], true))
                <x-responsive-nav-link :href="route('app-management.index')" :active="request()->routeIs('app-management.*') && ! request()->routeIs('app-management.reports.*')">
                    {{ __('Application Management') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('app-management.reports.index')" :active="request()->routeIs('app-management.reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endif

            @if(in_array(Auth::user()->role, ['super_admin', 'admin'], true))
                <x-responsive-nav-link :href="route('courses.index')" :active="request()->routeIs('courses.*')">
                    {{ __('Course Management') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('User Management') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                {{ __('Notifications') }}
                @if(($unread = Auth::user()->unreadNotifications()->count()) > 0)
                    ({{ $unread }})
                @endif
            </x-responsive-nav-link>

            @if(Auth::user()->isTrainer())
                <x-responsive-nav-link :href="route('trainer.exam-results')" :active="request()->routeIs('trainer.*')">
                    {{ __('Examination marks') }}
                </x-responsive-nav-link>
            @endif

            @if(Auth::user()->role === 'trainee')
                @if(Auth::user()->canResubmitRegistration())
                    <x-responsive-nav-link :href="route('registration.resubmit')" :active="request()->routeIs('registration.resubmit')">
                        {{ __('Update application') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('training.select-course')" :active="request()->routeIs('training.*')">
                    {{ __('Apply for Training') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('training.my-applications')" :active="request()->routeIs('training.my-applications')">
                    {{ __('My Applications') }}
                </x-responsive-nav-link>
                @if(Auth::user()->hasApprovedRegistration())
                    <x-responsive-nav-link :href="route('training.exam-results')" :active="request()->routeIs('training.exam-results')">
                        {{ __('Exam results') }}
                    </x-responsive-nav-link>
                @endif
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-white/20">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-white/80">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="Auth::user()->role === 'trainee' ? route('trainee.profile.edit') : route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if(in_array(Auth::user()->role, ['super_admin', 'admin'], true))
                    <x-responsive-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">
                        {{ __('Audit Trail') }}
                    </x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
