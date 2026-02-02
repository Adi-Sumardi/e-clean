<x-filament-panels::page>
    {{-- Print Styles --}}
    <style>
        @media print {
            /* Hide Filament navigation, sidebar, topbar, breadcrumbs */
            .fi-topbar, .fi-sidebar, .fi-header, nav,
            header, aside, .fi-breadcrumbs,
            .fi-topbar-nav, .fi-global-search { display: none !important; }

            /* Hide filter section & buttons when printing */
            .no-print { display: none !important; }

            /* Print header visible only on print */
            .print-only { display: block !important; }

            /* Full width content */
            .fi-main { padding: 0 !important; margin: 0 !important; }
            .fi-page { padding: 0 !important; }
            main { margin-left: 0 !important; padding: 0 !important; }
            body { background: white !important; }

            /* Table styling for print */
            .print-table { border: 1px solid #ddd !important; }
            .print-table th { background: #4F46E5 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-table td, .print-table th { border: 1px solid #ddd !important; padding: 4px 8px !important; font-size: 10px !important; }
            .unit-row td { background: #EEF2FF !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .petugas-row td { background: #F3F4F6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            /* Stats for print */
            .print-stats { display: flex !important; gap: 8px !important; margin-bottom: 12px !important; }
            .print-stats > div { border: 1px solid #ddd !important; padding: 8px !important; flex: 1 !important; text-align: center !important; }

            /* Status badges for print */
            .badge-ontime { background: #D1FAE5 !important; color: #065F46 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-late { background: #FEF3C7 !important; color: #92400E !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-expired { background: #FEE2E2 !important; color: #991B1B !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            @page { size: landscape; margin: 10mm; }
        }
    </style>

    <div class="space-y-6">
        {{-- Print Header (hidden on screen, shown on print) --}}
        <div class="print-only hidden">
            <div style="text-align: center; margin-bottom: 16px; border-bottom: 2px solid #4F46E5; padding-bottom: 8px;">
                <h1 style="font-size: 18px; color: #4F46E5; margin-bottom: 4px;">Laporan Bulanan Kegiatan Cleaning Service</h1>
                @php
                    $bulanNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                @endphp
                <p style="font-size: 12px; color: #666;">Periode: {{ $bulanNames[$bulan] }} {{ $tahun }}</p>
                <p style="font-size: 10px; color: #999;">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="no-print bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Filter Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 py-3">
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <h3 class="font-semibold text-sm">Filter Laporan</h3>
                </div>
            </div>

            {{-- Filter Body --}}
            <div class="p-5">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {{-- Bulan - Searchable Select --}}
                    <div x-data="{
                        open: false,
                        search: '',
                        selected: @entangle('bulan'),
                        options: {1:'Januari',2:'Februari',3:'Maret',4:'April',5:'Mei',6:'Juni',7:'Juli',8:'Agustus',9:'September',10:'Oktober',11:'November',12:'Desember'},
                        get filteredOptions() {
                            if (!this.search) return this.options;
                            return Object.fromEntries(Object.entries(this.options).filter(([k,v]) => v.toLowerCase().includes(this.search.toLowerCase())));
                        },
                        get selectedLabel() { return this.options[this.selected] || 'Pilih Bulan'; },
                        select(val) { this.selected = parseInt(val); this.open = false; this.search = ''; }
                    }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Bulan
                            </span>
                        </label>
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white hover:border-indigo-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <span x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-gray-400" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                            <div class="p-2">
                                <input x-model="search" type="text" placeholder="Cari bulan..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm px-3 py-1.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"/>
                            </div>
                            <div class="max-h-48 overflow-y-auto">
                                <template x-for="[key, label] in Object.entries(filteredOptions)" :key="key">
                                    <button @click="select(key)" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" :class="selected == key ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-700 dark:text-gray-200'" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Tahun - Searchable Select --}}
                    @php $yearOptions = []; for($y = now()->year; $y >= now()->year - 2; $y--) { $yearOptions[$y] = (string)$y; } @endphp
                    <div x-data="{
                        open: false,
                        selected: @entangle('tahun'),
                        options: {{ json_encode($yearOptions) }},
                        get selectedLabel() { return this.options[this.selected] || 'Pilih Tahun'; },
                        select(val) { this.selected = parseInt(val); this.open = false; }
                    }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                Tahun
                            </span>
                        </label>
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white hover:border-indigo-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <span x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-gray-400" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                            <div class="max-h-48 overflow-y-auto">
                                <template x-for="[key, label] in Object.entries(options)" :key="key">
                                    <button @click="select(key)" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" :class="selected == key ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-700 dark:text-gray-200'" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Unit - Searchable Select --}}
                    <div x-data="{
                        open: false,
                        search: '',
                        selected: @entangle('unitFilter'),
                        options: {{ json_encode($this->getUnitOptions()) }},
                        get filteredOptions() {
                            if (!this.search) return this.options;
                            return Object.fromEntries(Object.entries(this.options).filter(([k,v]) => v.toLowerCase().includes(this.search.toLowerCase())));
                        },
                        get selectedLabel() { return this.selected ? (this.options[this.selected] || 'Pilih Unit') : 'Semua Unit'; },
                        select(val) { this.selected = val || null; this.open = false; this.search = ''; }
                    }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Unit
                            </span>
                        </label>
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white hover:border-indigo-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <span x-text="selectedLabel" :class="!selected ? 'text-gray-400' : ''"></span>
                            <svg class="w-4 h-4 text-gray-400" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                            <div class="p-2">
                                <input x-model="search" type="text" placeholder="Cari unit..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm px-3 py-1.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"/>
                            </div>
                            <div class="max-h-48 overflow-y-auto">
                                <button @click="select('')" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" :class="!selected ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-700 dark:text-gray-200'">Semua Unit</button>
                                <template x-for="[key, label] in Object.entries(filteredOptions)" :key="key">
                                    <button @click="select(key)" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" :class="selected == key ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-700 dark:text-gray-200'" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Petugas - Searchable Select --}}
                    <div x-data="{
                        open: false,
                        search: '',
                        selected: @entangle('petugasFilter'),
                        options: {{ json_encode($this->getPetugasOptions()) }},
                        get filteredOptions() {
                            if (!this.search) return this.options;
                            return Object.fromEntries(Object.entries(this.options).filter(([k,v]) => v.toLowerCase().includes(this.search.toLowerCase())));
                        },
                        get selectedLabel() { return this.selected ? (this.options[this.selected] || 'Pilih Petugas') : 'Semua Petugas'; },
                        select(val) { this.selected = val || null; this.open = false; this.search = ''; }
                    }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Petugas
                            </span>
                        </label>
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white hover:border-indigo-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <span x-text="selectedLabel" :class="!selected ? 'text-gray-400' : ''"></span>
                            <svg class="w-4 h-4 text-gray-400" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                            <div class="p-2">
                                <input x-model="search" type="text" placeholder="Cari petugas..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm px-3 py-1.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"/>
                            </div>
                            <div class="max-h-48 overflow-y-auto">
                                <button @click="select('')" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" :class="!selected ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-700 dark:text-gray-200'">Semua Petugas</button>
                                <template x-for="[key, label] in Object.entries(filteredOptions)" :key="key">
                                    <button @click="select(key)" type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" :class="selected == key ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-700 dark:text-gray-200'" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        wire:click="downloadPdf"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition"
                    >
                        <svg wire:loading.remove wire:target="downloadPdf" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <svg wire:loading wire:target="downloadPdf" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                        <span wire:loading wire:target="downloadPdf">Generating...</span>
                    </button>

                    <button
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm hover:shadow transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>

        @php
            $stats = $this->getSummaryStats();
            $reports = $this->getFilteredReports();
            $grouped = $reports->groupBy(fn ($r) => $r->lokasi->unit->nama_unit ?? 'Tanpa Unit')
                ->map(fn ($unitReports) => $unitReports->groupBy(fn ($r) => $r->petugas->name ?? 'Unknown'));
        @endphp

        {{-- Summary Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 print-stats">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Laporan</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['ontime_pct'] }}%</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tepat Waktu ({{ $stats['ontime'] }})</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['late_pct'] }}%</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Terlambat ({{ $stats['late'] }})</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['expired_pct'] }}%</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tidak Lapor ({{ $stats['expired'] }})</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-amber-500 dark:text-amber-400">{{ $stats['avg_rating'] }}/5</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Rata-rata Rating</div>
            </div>
        </div>

        {{-- Report Table --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Loading Overlay --}}
            <div wire:loading class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-lg no-print">
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin h-10 w-10 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memuat data...</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 print-table">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Petugas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lokasi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kegiatan</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Lapor</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rating</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($grouped as $unitName => $petugasGroups)
                            {{-- Unit Header --}}
                            <tr class="bg-indigo-50 dark:bg-indigo-900/20 unit-row">
                                <td colspan="7" class="px-4 py-2">
                                    <span class="font-bold text-indigo-700 dark:text-indigo-300 text-sm">{{ $unitName }}</span>
                                    <span class="text-xs text-indigo-500 dark:text-indigo-400 ml-2">({{ $petugasGroups->flatten(1)->count() }} laporan)</span>
                                </td>
                            </tr>

                            @foreach($petugasGroups as $petugasName => $petugasReports)
                                {{-- Petugas Sub-Header --}}
                                <tr class="bg-gray-50 dark:bg-gray-700/50 petugas-row">
                                    <td colspan="7" class="px-6 py-1.5">
                                        <span class="font-medium text-gray-700 dark:text-gray-300 text-sm">{{ $petugasName }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ count($petugasReports) }} laporan)</span>
                                    </td>
                                </tr>

                                @foreach($petugasReports as $report)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                            {{ $report->tanggal->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $report->petugas->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $report->lokasi->unit->nama_unit ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $report->lokasi->nama_lokasi ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ \Illuminate\Support\Str::limit($report->kegiatan, 40) }}
                                        </td>
                                        <td class="px-4 py-2 text-center whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'ontime' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    'late' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                    'expired' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                ];
                                                $badgeClass = [
                                                    'ontime' => 'badge-ontime',
                                                    'late' => 'badge-late',
                                                    'expired' => 'badge-expired',
                                                ];
                                                $statusLabels = \App\Models\ActivityReport::getReportingStatusOptions();
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$report->reporting_status] ?? 'bg-gray-100 text-gray-800' }} {{ $badgeClass[$report->reporting_status] ?? '' }}">
                                                {{ $statusLabels[$report->reporting_status] ?? ucfirst($report->reporting_status ?? '-') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            @if($report->rating)
                                                <span class="text-sm font-semibold text-amber-500">{{ $report->rating }}/5</span>
                                            @else
                                                <span class="text-sm text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Tidak ada data laporan untuk periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Print Footer --}}
        <div class="print-only hidden text-center" style="font-size: 9px; color: #999; margin-top: 16px; padding-top: 8px; border-top: 1px solid #ddd;">
            E-Cleaning Service Management System - Generated by {{ config('app.name') }}
        </div>
    </div>
</x-filament-panels::page>
