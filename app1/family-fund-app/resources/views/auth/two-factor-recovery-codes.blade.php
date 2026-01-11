<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Recovery Codes
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
                    <div class="text-center mb-6">
                        <div class="mx-auto w-16 h-16 bg-amber-100 dark:bg-amber-900/50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Save Your Recovery Codes</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            Store these recovery codes in a safe place. Each code can only be used once.
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($recoveryCodes as $code)
                                <code class="bg-white dark:bg-gray-800 px-3 py-2 rounded-lg text-center font-mono text-sm text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600 select-all">
                                    {{ $code }}
                                </code>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="text-sm text-amber-700 dark:text-amber-300">
                                <p class="font-medium mb-1">Important:</p>
                                <ul class="list-disc list-inside space-y-1 text-amber-600 dark:text-amber-400">
                                    <li>Save these codes before leaving this page</li>
                                    <li>Each code can only be used once</li>
                                    <li>Use a recovery code if you lose access to your authenticator app</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button onclick="copyRecoveryCodes()" class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl transition-colors duration-200 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Copy to Clipboard
                        </button>

                        <form method="POST" action="{{ route('two-factor.regenerate-codes') }}" onsubmit="return confirm('This will invalidate your current recovery codes. Are you sure?');">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/50 dark:hover:bg-amber-900/70 text-amber-700 dark:text-amber-300 font-medium rounded-xl transition-colors duration-200">
                                Regenerate Recovery Codes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('two-factor.setup') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-500 dark:hover:text-teal-300 font-medium">
                    Back to 2FA Settings
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyRecoveryCodes() {
            const codes = @json($recoveryCodes);
            const text = codes.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                alert('Recovery codes copied to clipboard!');
            });
        }
    </script>
    @endpush
</x-app-layout>
