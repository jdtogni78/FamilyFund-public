<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val)); if(darkMode) $el.classList.add('dark')" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Two-Factor Authentication - {{ config('app.name', 'Family Fund') }}</title>

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

                    <!-- Security Message -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 max-w-md">
                        <div class="flex items-center mb-3">
                            <svg class="w-8 h-8 text-teal-300 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span class="text-xl font-semibold text-teal-100">Secure Login</span>
                        </div>
                        <p class="text-teal-100/80">
                            Your account is protected with two-factor authentication. Please enter your verification code to continue.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Challenge Form -->
            <div class="w-full lg:w-1/2 flex flex-col bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
                <!-- Dark Mode Toggle -->
                <div class="absolute top-4 right-4">
                    <button
                        @click="darkMode = !darkMode"
                        class="p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 transition-all duration-200"
                        title="Toggle dark mode"
                    >
                        <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Header -->
                <div class="lg:hidden bg-gradient-to-r from-teal-600 to-cyan-700 dark:from-teal-800 dark:to-cyan-900 px-6 py-8 text-white text-center">
                    <div class="rounded-full p-0.5 bg-white/20 mx-auto mb-4 inline-block">
                        <img src="{{ asset('images/logo.png') }}" alt="Family Fund" class="h-16 w-16 rounded-full">
                    </div>
                    <h1 class="text-2xl font-bold">Two-Factor Authentication</h1>
                </div>

                <!-- Form Container -->
                <div class="flex-1 flex items-center justify-center px-6 py-12">
                    <div class="w-full max-w-md">
                        <!-- Welcome Text -->
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Verify Your Identity</h2>
                            <p class="text-gray-500 dark:text-gray-400 mt-2">Enter your authentication code</p>
                        </div>

                        <!-- Form Card -->
                        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl px-8 py-10 ring-1 ring-gray-100 dark:ring-gray-700">
                            <!-- Status Message -->
                            @if (session('status'))
                                <div class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-6">
                                @csrf

                                <div class="text-center mb-6">
                                    <div class="mx-auto w-16 h-16 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Enter the 6-digit code from your authenticator app, or use a recovery code.
                                    </p>
                                </div>

                                <!-- Code Input -->
                                <div>
                                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Authentication Code
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="code"
                                            type="text"
                                            name="code"
                                            inputmode="numeric"
                                            autocomplete="one-time-code"
                                            autofocus
                                            required
                                            placeholder="Enter code"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors duration-200 text-center text-lg tracking-widest"
                                        />
                                    </div>
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full flex justify-center items-center px-4 py-3 bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 text-white font-semibold rounded-xl shadow-lg shadow-teal-500/30 hover:shadow-teal-500/40 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200"
                                >
                                    Verify
                                </button>

                                <!-- Back to Login -->
                                <div class="text-center">
                                    <a href="{{ route('login') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-500 dark:hover:text-teal-300 font-medium transition-colors duration-200">
                                        Back to login
                                    </a>
                                </div>
                            </form>
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
