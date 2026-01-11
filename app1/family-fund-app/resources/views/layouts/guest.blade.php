<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val)); if(darkMode) $el.classList.add('dark')" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Family Fund') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-round.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex">
            <!-- Left Side: Hero Section with Image -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
                <!-- Background Image -->
                <img src="{{ asset('images/hero-bg.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">

                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-teal-900/70 via-cyan-900/60 to-teal-900/70"></div>

                <!-- Content -->
                <div class="relative z-10 flex flex-col justify-center items-center w-full px-12 text-white">
                    <!-- Logo -->
                    <div class="mb-8">
                        <div class="rounded-full p-1 bg-white/20">
                            <img src="{{ asset('images/logo.png') }}" alt="Family Fund" class="h-24 w-24 rounded-full drop-shadow-2xl">
                        </div>
                    </div>

                    <!-- Brand Name -->
                    <h1 class="text-4xl font-bold mb-4 text-center drop-shadow-lg text-teal-100">Family Fund</h1>

                    <!-- Tagline -->
                    <p class="text-xl text-teal-100 text-center max-w-md mb-8 drop-shadow">
                        Manage your family's financial future with confidence
                    </p>

                    <!-- Features -->
                    <div class="space-y-4 text-teal-100">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="drop-shadow">Track fund shares and portfolios</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="drop-shadow">Monitor beneficiary accounts</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="drop-shadow">Generate detailed reports</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="w-full lg:w-1/2 flex flex-col bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
                <!-- Dark Mode Toggle (Mobile & Desktop) -->
                <div class="absolute top-4 right-4">
                    <button
                        @click="darkMode = !darkMode"
                        class="p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 transition-all duration-200"
                        title="Toggle dark mode"
                    >
                        <!-- Sun Icon (shown in dark mode) -->
                        <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Moon Icon (shown in light mode) -->
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Header (visible on small screens) -->
                <div class="lg:hidden bg-gradient-to-r from-teal-600 to-cyan-700 dark:from-teal-800 dark:to-cyan-900 px-6 py-8 text-white text-center">
                    <div class="rounded-full p-0.5 bg-white/20 mx-auto mb-4 inline-block">
                        <img src="{{ asset('images/logo.png') }}" alt="Family Fund" class="h-16 w-16 rounded-full">
                    </div>
                    <h1 class="text-2xl font-bold">Family Fund</h1>
                    <p class="text-teal-100 text-sm mt-1">Manage your family's financial future</p>
                </div>

                <!-- Form Container -->
                <div class="flex-1 flex items-center justify-center px-6 py-12">
                    <div class="w-full max-w-md">
                        <!-- Welcome Text -->
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome back</h2>
                            <p class="text-gray-500 dark:text-gray-400 mt-2">Sign in to your account to continue</p>
                        </div>

                        <!-- Form Card -->
                        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl px-8 py-10 ring-1 ring-gray-100 dark:ring-gray-700">
                            {{ $slot }}
                        </div>

                        <!-- Footer -->
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-8">
                            &copy; {{ date('Y') }} Family Fund. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
