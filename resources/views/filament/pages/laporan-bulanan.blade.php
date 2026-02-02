<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bulan</label>
                    <select wire:model.live="bulan" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tahun</label>
                    <select wire:model.live="tahun" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @for($y = now()->year; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unit</label>
                    <select wire:model.live="unitFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Semua Unit</option>
                        @foreach($this->getUnitOptions() as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Petugas</label>
                    <select wire:model.live="petugasFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Semua Petugas</option>
                        @foreach($this->getPetugasOptions() as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button
                        wire:click="downloadPdf"
                        wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium rounded-lg transition"
                    >
                        <svg wire:loading.remove wire:target="downloadPdf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <svg wire:loading wire:target="downloadPdf" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                        <span wire:loading wire:target="downloadPdf">Generating...</span>
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
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
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
            <div wire:loading class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-lg">
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin h-10 w-10 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memuat data...</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
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
                            <tr class="bg-indigo-50 dark:bg-indigo-900/20">
                                <td colspan="7" class="px-4 py-2">
                                    <span class="font-bold text-indigo-700 dark:text-indigo-300 text-sm">{{ $unitName }}</span>
                                    <span class="text-xs text-indigo-500 dark:text-indigo-400 ml-2">({{ $petugasGroups->flatten(1)->count() }} laporan)</span>
                                </td>
                            </tr>

                            @foreach($petugasGroups as $petugasName => $petugasReports)
                                {{-- Petugas Sub-Header --}}
                                <tr class="bg-gray-50 dark:bg-gray-700/50">
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
                                                $statusLabels = \App\Models\ActivityReport::getReportingStatusOptions();
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$report->reporting_status] ?? 'bg-gray-100 text-gray-800' }}">
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
    </div>
</x-filament-panels::page>
