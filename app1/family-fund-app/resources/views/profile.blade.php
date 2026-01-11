<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Profile') }}</li>
    </ol>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            {{-- Two-Factor Authentication --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Two-Factor Authentication') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Add an extra layer of security to your account using two-factor authentication.') }}
                            </p>
                        </header>

                        <div class="mt-6 space-y-4">
                            @if(auth()->user()->hasTwoFactorEnabled())
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-600 dark:text-green-400">
                                            {{ __('Two-factor authentication is enabled.') }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Your account is protected with an additional layer of security.') }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {{ __('Two-factor authentication is not enabled.') }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('We recommend enabling two-factor authentication for additional security.') }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center gap-4">
                                <a href="{{ route('two-factor.setup') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 dark:bg-teal-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 dark:hover:bg-teal-600 focus:bg-teal-700 dark:focus:bg-teal-600 active:bg-teal-900 dark:active:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    @if(auth()->user()->hasTwoFactorEnabled())
                                        {{ __('Manage 2FA') }}
                                    @else
                                        {{ __('Enable 2FA') }}
                                    @endif
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>

            {{-- Recent Login Activity --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Recent Login Activity') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('A log of recent login attempts and activity on your account.') }}
                            </p>
                        </header>

                        <div class="mt-6">
                            @php
                                $activities = auth()->user()->loginActivities()
                                    ->orderByDesc('created_at')
                                    ->limit(10)
                                    ->get();
                            @endphp

                            @if($activities->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('No recent login activity.') }}
                                </p>
                            @else
                                <div class="space-y-4">
                                    @foreach($activities as $activity)
                                        <div class="flex items-start space-x-4 p-3 rounded-lg {{ $activity->status === 'success' ? 'bg-green-50 dark:bg-green-900/20' : ($activity->status === 'failed' || $activity->status === 'two_factor_failed' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20') }}">
                                            <div class="flex-shrink-0">
                                                @if($activity->status === 'success')
                                                    <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @elseif($activity->status === 'failed' || $activity->status === 'two_factor_failed')
                                                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @else
                                                    <svg class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium {{ $activity->status === 'success' ? 'text-green-800 dark:text-green-200' : ($activity->status === 'failed' || $activity->status === 'two_factor_failed' ? 'text-red-800 dark:text-red-200' : 'text-yellow-800 dark:text-yellow-200') }}">
                                                    @switch($activity->status)
                                                        @case('success')
                                                            {{ __('Successful login') }}
                                                            @break
                                                        @case('failed')
                                                            {{ __('Failed login attempt') }}
                                                            @break
                                                        @case('two_factor_pending')
                                                            {{ __('2FA verification pending') }}
                                                            @break
                                                        @case('two_factor_failed')
                                                            {{ __('2FA verification failed') }}
                                                            @break
                                                    @endswitch
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $activity->created_at->diffForHumans() }}
                                                    &middot;
                                                    {{ $activity->ip_address ?? 'Unknown IP' }}
                                                    @if($activity->browser || $activity->device)
                                                        &middot;
                                                        {{ $activity->browser ?? '' }} {{ $activity->device ? 'on ' . $activity->device : '' }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
