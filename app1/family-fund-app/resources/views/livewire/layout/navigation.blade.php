<?php
use App\Livewire\Actions\Logout;

$logout = function (Logout $logout) {
    $logout();
    $this->redirect('/', navigate: true);
};

?>

<nav x-data="{ open: false, activeMenu: null }" @click.outside="activeMenu = null" class="bg-gradient-to-r from-teal-700 via-teal-600 to-cyan-600 dark:from-teal-900 dark:via-teal-800 dark:to-cyan-900 shadow-lg sticky top-0 z-50 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left: Logo & Brand -->
            <div class="flex items-center">
                <a href="{{ route('funds.index') }}" wire:navigate class="flex items-center space-x-3 group">
                    <div class="rounded-full p-0.5 bg-white/20 group-hover:bg-white/30 transition-colors">
                        <x-application-logo class="h-10 w-10 rounded-full" />
                    </div>
                    <span class="text-xl font-bold text-white hidden sm:block drop-shadow">Family Fund</span>
                </a>
            </div>

            <!-- Center: Navigation Links -->
            <div class="hidden lg:flex lg:items-center lg:space-x-1">
                @include('livewire.layout.nav')
                @php($menu = View::shared('menu'))
                @foreach ($menu as $label => $menuData)
                <div class="relative" x-data="{ isOpen: false }">
                    <button
                        @mouseenter="activeMenu = '{{ $label }}'"
                        @click="activeMenu = activeMenu === '{{ $label }}' ? null : '{{ $label }}'"
                        class="flex items-center px-3 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-150"
                        :class="{ 'text-white bg-white/20': activeMenu === '{{ $label }}' }"
                    >
                        <i class="{{ $menuData['icon'] }} mr-2 text-teal-200"></i>
                        <span>{{ str_replace(' Menu', '', $label) }}</span>
                        <svg class="ml-1 h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': activeMenu === '{{ $label }}' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        x-show="activeMenu === '{{ $label }}'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        @mouseleave="activeMenu = null"
                        class="absolute left-0 mt-1 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/10 py-2 z-50"
                        style="display: none;"
                    >
                        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-teal-600 dark:text-teal-400 uppercase tracking-wider">{{ str_replace(' Menu', '', $label) }}</p>
                        </div>
                        <div class="py-1">
                            @foreach ($menuData['items'] as $sublabel => $subroute)
                            <a
                                href="{{ route($subroute['route']) }}"
                                class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/30 hover:text-teal-700 dark:hover:text-teal-300 transition-colors duration-150"
                            >
                                <i class="{{ $subroute['icon'] }} w-5 text-teal-500 dark:text-teal-400 mr-3"></i>
                                {{ $sublabel }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Right: User Menu & Dark Mode Toggle -->
            <div class="flex items-center space-x-2">
                <!-- Environment Badge -->
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ app()->environment('production') ? 'bg-emerald-400/20 text-emerald-100' : 'bg-amber-400/20 text-amber-100' }}">
                    {{ strtoupper(app()->environment()) }}
                </span>

                <!-- Dark Mode Toggle -->
                <button
                    @click="$dispatch('toggle-dark-mode')"
                    class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-150"
                    title="Toggle dark mode"
                >
                    <!-- Sun Icon (shown in dark mode) -->
                    <svg x-show="document.documentElement.classList.contains('dark')" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (shown in light mode) -->
                    <svg x-show="!document.documentElement.classList.contains('dark')" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div class="hidden sm:block relative" x-data="{ userOpen: false }">
                    <button
                        @click="userOpen = !userOpen"
                        @click.outside="userOpen = false"
                        class="flex items-center space-x-2 px-3 py-1.5 text-sm font-medium text-white hover:bg-white/10 rounded-lg transition-colors duration-150"
                    >
                        <div class="h-8 w-8 rounded-full bg-white/20 border-2 border-white/40 flex items-center justify-center text-white font-semibold text-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="hidden md:block" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': userOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- User Dropdown Menu -->
                    <div
                        x-show="userOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-black/10 py-1 z-50"
                        style="display: none;"
                    >
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/30 dark:to-cyan-900/30 rounded-t-xl">
                            <p class="text-sm font-semibold text-teal-800 dark:text-teal-200">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-teal-600 dark:text-teal-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/30 hover:text-teal-700">
                            <i class="fa fa-user mr-3 text-teal-500"></i>Profile
                        </a>
                        <button wire:click="logout" class="w-full flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-teal-50 dark:hover:bg-teal-900/30 hover:text-teal-700">
                            <i class="fa fa-sign-out mr-3 text-teal-500"></i>Log Out
                        </button>
                    </div>
                </div>

                <!-- Mobile Hamburger -->
                <button
                    @click="open = !open"
                    class="lg:hidden p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-150"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="lg:hidden bg-teal-800 dark:bg-teal-900 border-t border-white/10"
        style="display: none;"
    >
        <div class="px-4 py-3 space-y-1">
            @php($menu = View::shared('menu'))
            @foreach ($menu as $label => $menuData)
            <div x-data="{ mobileOpen: false }">
                <button
                    @click="mobileOpen = !mobileOpen"
                    class="w-full flex items-center justify-between px-3 py-2 text-base font-medium text-white/90 hover:text-white hover:bg-white/10 rounded-lg"
                >
                    <span class="flex items-center">
                        <i class="{{ $menuData['icon'] }} mr-3 text-teal-300"></i>
                        {{ str_replace(' Menu', '', $label) }}
                    </span>
                    <svg class="h-5 w-5 transition-transform duration-200" :class="{ 'rotate-180': mobileOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="mobileOpen" x-collapse class="pl-6 space-y-1 mt-1">
                    @foreach ($menuData['items'] as $sublabel => $subroute)
                    <a href="{{ route($subroute['route']) }}" class="flex items-center px-3 py-2 text-sm text-teal-100 hover:text-white hover:bg-white/10 rounded-lg">
                        <i class="{{ $subroute['icon'] }} mr-3 text-teal-300"></i>
                        {{ $sublabel }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <!-- Mobile User Section -->
        <div class="border-t border-white/10 px-4 py-3">
            <div class="flex items-center space-x-3 mb-3">
                <div class="h-10 w-10 rounded-full bg-white/20 border-2 border-white/40 flex items-center justify-center text-white font-semibold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-teal-200">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <div class="space-y-1">
                <a href="{{ route('profile') }}" wire:navigate class="flex items-center px-3 py-2 text-sm text-teal-100 hover:text-white hover:bg-white/10 rounded-lg">
                    <i class="fa fa-user mr-3 text-teal-300"></i>Profile
                </a>
                <button wire:click="logout" class="w-full flex items-center px-3 py-2 text-sm text-teal-100 hover:text-white hover:bg-white/10 rounded-lg">
                    <i class="fa fa-sign-out mr-3 text-teal-300"></i>Log Out
                </button>
            </div>
        </div>
    </div>
</nav>
