<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Two-Factor Authentication
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6 sm:p-8">
                    @if($isEnabled)
                        <!-- 2FA is enabled -->
                        <div class="text-center mb-8">
                            <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Two-Factor Authentication is Enabled</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                Your account is protected with an additional layer of security.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <a href="{{ route('two-factor.recovery-codes') }}" class="block w-full text-center px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl transition-colors duration-200">
                                View Recovery Codes
                            </a>

                            <form method="POST" action="{{ route('two-factor.disable') }}" onsubmit="return confirm('Are you sure you want to disable two-factor authentication?');">
                                @csrf
                                @method('DELETE')
                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Confirm Password
                                    </label>
                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        required
                                        class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                        placeholder="Enter your password to disable 2FA"
                                    />
                                    @error('password')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors duration-200">
                                    Disable Two-Factor Authentication
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- 2FA setup -->
                        <div class="text-center mb-8">
                            <div class="mx-auto w-16 h-16 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Set Up Two-Factor Authentication</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                Add an extra layer of security to your account using an authenticator app.
                            </p>
                        </div>

                        <div class="space-y-6">
                            <!-- Step 1: Scan QR Code -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">1. Scan this QR code with your authenticator app</h4>
                                <div class="flex justify-center p-4 bg-white rounded-xl border border-gray-200 dark:border-gray-600">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code" class="w-48 h-48">
                                </div>
                            </div>

                            <!-- Manual Entry -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Or enter this code manually:</h4>
                                <code class="block text-center text-lg font-mono bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white select-all">
                                    {{ $secret }}
                                </code>
                            </div>

                            <!-- Step 2: Enter Code -->
                            <form method="POST" action="{{ route('two-factor.enable') }}">
                                @csrf
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">2. Enter the 6-digit code from your app</h4>
                                <div class="mb-4">
                                    <input
                                        type="text"
                                        name="code"
                                        inputmode="numeric"
                                        maxlength="6"
                                        required
                                        class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-center text-xl tracking-widest focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                        placeholder="000000"
                                    />
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="w-full px-4 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl shadow-lg shadow-teal-500/30 transition-all duration-200">
                                    Enable Two-Factor Authentication
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('profile') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-500 dark:hover:text-teal-300 font-medium">
                    Back to Profile
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
