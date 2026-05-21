<div class="relative">

    <div wire:loading.delay wire:target="refreshStats" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/80 backdrop-blur-[2px] rounded-lg">
        <svg class="w-10 h-10 animate-spin" style="color: #7c3aed;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm font-medium text-gray-500">Loading...</p>
    </div>

    <div class="renewal-dashboard-grid">
        <!-- Box 1: New -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container purple">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">New</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $newStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $newStats['total_via_reseller'] }}
                                ($ {{ number_format($newStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $newStats['total_via_end_user'] }}
                                ($ {{ number_format($newStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount purple">$ {{ number_format($newStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: Pending Confirmation -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container orange">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Pending Confirmation</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $pendingConfirmationStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $pendingConfirmationStats['total_via_reseller'] }}
                                ($ {{ number_format($pendingConfirmationStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $pendingConfirmationStats['total_via_end_user'] }}
                                ($ {{ number_format($pendingConfirmationStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount orange">$ {{ number_format($pendingConfirmationStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: Renewal Forecast -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container blue">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Renewal Forecast (90 Days)</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $renewalForecastStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $renewalForecastStats['total_via_reseller'] }}
                                ($ {{ number_format($renewalForecastStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $renewalForecastStats['total_via_end_user'] }}
                                ($ {{ number_format($renewalForecastStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount blue">$ {{ number_format($renewalForecastStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 4: Pending Payment -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container red">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Pending Payment</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $pendingPaymentStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $pendingPaymentStats['total_via_reseller'] }}
                                ($ {{ number_format($pendingPaymentStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $pendingPaymentStats['total_via_end_user'] }}
                                ($ {{ number_format($pendingPaymentStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount red">$ {{ number_format($pendingPaymentStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 5: Renewal Forecast Current Month -->
        <div class="renewal-card">
            <div class="renewal-card-content">
                <div class="renewal-card-layout">
                    <div class="renewal-icon-container green">
                        <svg class="renewal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="renewal-details">
                        <dt class="renewal-title">Renewal Forecast (30 Days)</dt>
                        <dd>
                            <div class="renewal-subtitle">Total Company: {{ $renewalForecastCurrentMonthStats['total_companies'] }}</div>
                            <div class="renewal-subtitle">
                                Via Reseller: {{ $renewalForecastCurrentMonthStats['total_via_reseller'] }}
                                ($ {{ number_format($renewalForecastCurrentMonthStats['total_via_reseller_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-subtitle">
                                Via End User: {{ $renewalForecastCurrentMonthStats['total_via_end_user'] }}
                                ($ {{ number_format($renewalForecastCurrentMonthStats['total_via_end_user_amount'] ?? 0, 2) }})
                            </div>
                            <div class="renewal-amount green">$ {{ number_format($renewalForecastCurrentMonthStats['total_amount'], 2) }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
