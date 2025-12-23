<x-filament-panels::page>
    <style>
        @media print {
            /* FORCE: Remove ALL possible overlays and backdrops */
            *[style*="backdrop"],
            *[class*="backdrop"],
            *[class*="overlay"],
            *[class*="modal"],
            .fi-topbar,
            .fi-sidebar,
            .fi-breadcrumbs,
            .fi-modal,
            .fi-overlay,
            .fi-modal-close-overlay,
            header,
            nav,
            .no-print,
            [role="dialog"],
            [aria-modal="true"],
            [data-state="open"],
            div[style*="position: fixed"],
            div[style*="z-index"] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                content: none !important;
                background: transparent !important;
            }

            /* Remove ALL pseudo elements that could be overlays */
            *::before,
            *::after {
                background: transparent !important;
                backdrop-filter: none !important;
                opacity: 1 !important;
            }

            /* Except QR Code elements */
            .qrcode-grid::before,
            .qrcode-grid::after,
            .qrcode-card::before,
            .qrcode-card::after {
                display: block !important;
            }

            /* Reset body and html for clean print - A4: 210mm x 297mm */
            @page {
                size: A4;
                margin: 10mm;
            }

            body, html {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                width: 210mm;
                height: 297mm;
                overflow: visible !important;
            }

            /* Force light mode */
            body {
                color-scheme: light !important;
            }

            /* Make main content full width */
            main, .fi-main, .fi-page, .fi-section-content-ctn {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                background: white !important;
                box-shadow: none !important;
            }

            /* Keep QR Code grid and cards visible */
            .qrcode-grid,
            .qrcode-card,
            .qrcode-card * {
                opacity: 1 !important;
                visibility: visible !important;
            }

            .qrcode-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Grid: 3 columns x 5 rows = 15 items per page (A4 with safe margin)
               A4 printable area with 10mm margin: 190mm x 277mm
               Width per column: 190mm / 3 = ~63mm
               Height per row: 277mm / 5 = ~55mm
            */
            .qrcode-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 3mm !important;
                padding: 2mm !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .qrcode-card {
                border: 0.5px solid #000 !important;
                padding: 2mm !important;
                border-radius: 0 !important;
                background: white !important;
                text-align: center !important;
                height: auto !important;
                max-height: 52mm !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .qrcode-card img {
                display: block !important;
                width: 100% !important;
                max-width: 50mm !important;
                height: auto !important;
                max-height: 28mm !important;
                object-fit: contain !important;
                margin: 0 auto 1mm auto !important;
            }

            .qrcode-card h3 {
                font-size: 7pt !important;
                line-height: 1.1 !important;
                margin: 0.5mm 0 0 0 !important;
                padding: 0 !important;
                font-weight: 600 !important;
                color: #000 !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }

            .qrcode-card p {
                font-size: 6pt !important;
                line-height: 1.1 !important;
                margin: 0.5mm 0 0 0 !important;
                padding: 0 !important;
                color: #333 !important;
            }

            .qrcode-card .kode {
                font-size: 9pt !important;
                line-height: 1.1 !important;
                margin: 0 0 0.5mm 0 !important;
                padding: 0 !important;
                font-weight: 700 !important;
                color: #000 !important;
                font-family: 'Courier New', monospace !important;
            }
        }

        .qrcode-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .qrcode-card {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            background: white;
        }

        .qrcode-card img {
            width: 100%;
            max-width: 300px;
            height: auto;
            margin: 0 auto;
            display: block;
        }

        .qrcode-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-top: 0.75rem;
            color: #111827;
        }

        .qrcode-card p {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .qrcode-card .kode {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
            margin-top: 0.5rem;
            font-family: monospace;
        }

        @media (max-width: 1024px) {
            .qrcode-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="no-print mb-4 flex flex-wrap gap-3 items-center">
        <x-filament::button
            color="primary"
            icon="heroicon-o-printer"
            onclick="handlePrint()"
        >
            Cetak Semua QR Code
        </x-filament::button>

        @php
            $missingCount = collect($lokasis)->where('qr_code_exists', false)->count();
        @endphp

        @if($missingCount > 0)
            <x-filament::button
                color="success"
                icon="heroicon-o-qr-code"
                wire:click="generateAllMissing"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="generateAllMissing">Generate {{ $missingCount }} QR Code yang Hilang</span>
                <span wire:loading wire:target="generateAllMissing">Generating...</span>
            </x-filament::button>
        @endif

        <div class="text-sm text-gray-600">
            Total {{ count($lokasis) }} lokasi
            @if($missingCount > 0)
                <span class="text-red-600">({{ $missingCount }} QR Code hilang)</span>
            @endif
        </div>
    </div>

    <script>
        function handlePrint() {
            // Force remove all dark overlays before print
            const body = document.body;
            const html = document.documentElement;

            // Store original classes
            const originalBodyClass = body.className;
            const originalHtmlClass = html.className;

            // Remove dark mode classes
            body.classList.remove('dark');
            html.classList.remove('dark');

            // Remove any background overlays
            const overlays = document.querySelectorAll('.fi-modal-close-overlay, [role="dialog"], [aria-modal="true"]');
            overlays.forEach(el => el.style.display = 'none');

            // Set light theme
            body.setAttribute('data-theme', 'light');
            html.setAttribute('data-theme', 'light');

            // Print
            window.print();

            // Restore original classes after print dialog closes
            setTimeout(() => {
                body.className = originalBodyClass;
                html.className = originalHtmlClass;
                overlays.forEach(el => el.style.display = '');
            }, 100);
        }
    </script>

    <div class="qrcode-grid">
        @foreach($lokasis as $lokasi)
            <div class="qrcode-card">
                @if($lokasi['qr_code_exists'] && $lokasi['qr_code_url'])
                    <img src="{{ $lokasi['qr_code_url'] }}" alt="QR Code {{ $lokasi['kode_lokasi'] }}">
                @else
                    <div class="w-full h-32 bg-gray-100 flex flex-col items-center justify-center mx-auto rounded-lg border-2 border-dashed border-gray-300 no-print">
                        <x-heroicon-o-qr-code class="w-8 h-8 text-gray-400 mb-2"/>
                        <span class="text-gray-500 text-sm mb-2">QR Code tidak tersedia</span>
                        <x-filament::button
                            color="success"
                            size="sm"
                            icon="heroicon-o-arrow-path"
                            wire:click="generateQRCode({{ $lokasi['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="generateQRCode({{ $lokasi['id'] }})"
                        >
                            <span wire:loading.remove wire:target="generateQRCode({{ $lokasi['id'] }})">Generate</span>
                            <span wire:loading wire:target="generateQRCode({{ $lokasi['id'] }})">...</span>
                        </x-filament::button>
                    </div>
                @endif

                <div class="kode">{{ $lokasi['kode_lokasi'] }}</div>
                <h3>{{ $lokasi['nama_lokasi'] }}</h3>
                <p class="capitalize">{{ str_replace('_', ' ', $lokasi['kategori']) }}</p>
            </div>
        @endforeach
    </div>

    @if(count($lokasis) === 0)
        <x-filament::section>
            <x-slot name="heading">
                Tidak ada lokasi
            </x-slot>

            <p class="text-gray-600">
                Belum ada lokasi aktif yang tersedia untuk dicetak.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
