<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="font-bold">Aksi Cepat</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Akses cepat ke fitur yang sering digunakan
        </x-slot>

        <style>
            .action-card {
                display: block;
                padding: 1.5rem;
                border-radius: 1rem;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                border: 1px solid #e5e7eb;
            }

            .action-card:hover {
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }

            .action-card.emerald {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                border-color: #059669;
            }

            .action-card.blue {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                border-color: #2563eb;
            }

            .action-card.violet {
                background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
                border-color: #7c3aed;
            }

            .icon-wrapper {
                width: 64px;
                height: 64px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1rem;
                transition: transform 0.3s ease;
            }

            .action-card:hover .icon-wrapper {
                transform: scale(1.1);
            }

            .action-card .arrow {
                transition: transform 0.3s ease;
            }

            .action-card:hover .arrow {
                transform: translateX(4px);
            }
        </style>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            <!-- Buat Laporan Baru -->
            <a href="/admin/activity-reports" class="action-card emerald">
                <div class="icon-wrapper">
                    <svg style="width: 36px; height: 36px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>

                <h3 style="font-size: 1.125rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                    Buat Laporan
                </h3>
                <p style="font-size: 0.875rem; color: rgba(255,255,255,0.9); margin-bottom: 1rem;">
                    <span style="font-weight: 600;">{{ $todayReports }}</span> laporan hari ini
                </p>

                <div style="display: flex; align-items: center; font-size: 0.875rem; font-weight: 500; color: white;">
                    <span>Buat Sekarang</span>
                    <svg class="arrow" style="width: 16px; height: 16px; margin-left: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </div>
            </a>

            <!-- Scan QR Code -->
            <a href="/admin/q-r-scanner" class="action-card blue">
                <div class="icon-wrapper">
                    <svg style="width: 36px; height: 36px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                </div>

                <h3 style="font-size: 1.125rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                    Scan QR Code
                </h3>
                <p style="font-size: 0.875rem; color: rgba(255,255,255,0.9); margin-bottom: 1rem;">
                    Scan lokasi & peralatan
                </p>

                <div style="display: flex; align-items: center; font-size: 0.875rem; font-weight: 500; color: white;">
                    <span>Buka Scanner</span>
                    <svg class="arrow" style="width: 16px; height: 16px; margin-left: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </div>
            </a>

            <!-- Jadwal Kerja -->
            <a href="/admin/jadwal-kebersihanans/jadwal-kebersihans" class="action-card violet">
                <div class="icon-wrapper">
                    <svg style="width: 36px; height: 36px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>

                <h3 style="font-size: 1.125rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                    Jadwal Kerja
                </h3>
                <p style="font-size: 0.875rem; color: rgba(255,255,255,0.9); margin-bottom: 1rem;">
                    Lihat jadwal kebersihan
                </p>

                <div style="display: flex; align-items: center; font-size: 0.875rem; font-weight: 500; color: white;">
                    <span>Lihat Jadwal</span>
                    <svg class="arrow" style="width: 16px; height: 16px; margin-left: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </div>
            </a>

        </div>

        @if($pendingReports > 0)
            <div style="padding: 1rem; background-color: #fef3c7; border: 2px solid #fbbf24; border-radius: 0.75rem; margin-top: 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="flex-shrink: 0;">
                            <svg style="width: 24px; height: 24px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <p style="font-size: 0.875rem; color: #78350f; margin: 0;">
                            <strong style="color: #dc2626;">{{ $pendingReports }} laporan</strong> masih berstatus draft.
                        </p>
                    </div>
                    <a href="/admin/activity-reports"
                       style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); transition: all 0.2s ease; text-decoration: none; border: none;">
                        <span>Selesaikan Sekarang</span>
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>

            <style>
                a[href="/admin/activity-reports"]:hover {
                    background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                    transform: translateY(-1px);
                }
            </style>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
