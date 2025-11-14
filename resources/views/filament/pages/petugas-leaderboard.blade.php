<x-filament-panels::page>
    <div class="space-y-6" wire:poll.5s>
        {{-- Real-time Update Indicator --}}
        <div class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 rounded-lg px-4 py-2 border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-2">
                <div class="relative">
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                </div>
                <span class="text-sm font-medium text-green-900 dark:text-green-100">
                    🔄 Live Update Aktif - Data diperbarui otomatis setiap 5 detik
                </span>
            </div>
            <span class="text-xs text-green-700 dark:text-green-300">
                Terakhir update: {{ $lastUpdated ?? now()->format('H:i:s') }}
            </span>
        </div>

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Periode</label>
                    <select wire:model.live="period" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="today">Hari Ini</option>
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                        <option value="year">Tahun Ini</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                @if($period === 'custom')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Mulai</label>
                        <input type="date" wire:model.live="startDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Selesai</label>
                        <input type="date" wire:model.live="endDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                @endif
            </div>
        </div>

        @php
            $leaderboard = $this->getLeaderboardData();
        @endphp

        {{-- Header Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Peringkat 1</p>
                        <h3 class="text-2xl font-bold mt-1">{{ $leaderboard[0]['name'] ?? 'N/A' }}</h3>
                        <p class="text-yellow-100 mt-1">Skor: {{ $leaderboard[0]['score'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-gray-300 to-gray-500 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-100 text-sm font-medium">Peringkat 2</p>
                        <h3 class="text-2xl font-bold mt-1">{{ $leaderboard[1]['name'] ?? 'N/A' }}</h3>
                        <p class="text-gray-100 mt-1">Skor: {{ $leaderboard[1]['score'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Peringkat 3</p>
                        <h3 class="text-2xl font-bold mt-1">{{ $leaderboard[2]['name'] ?? 'N/A' }}</h3>
                        <p class="text-orange-100 mt-1">Skor: {{ $leaderboard[2]['score'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Leaderboard Table --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Loading Overlay --}}
            <div wire:loading class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-lg">
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin h-10 w-10 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Memperbarui data...
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Peringkat
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Nama Petugas
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Laporan Disetujui
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tingkat Persetujuan
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Rating Laporan
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Skor
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($leaderboard as $index => $data)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition {{
                                $data['rank'] === 1 ? 'bg-yellow-50 dark:bg-yellow-900/10' :
                                ($data['rank'] === 2 ? 'bg-gray-50 dark:bg-gray-900/10' :
                                ($data['rank'] === 3 ? 'bg-orange-50 dark:bg-orange-900/10' : ''))
                            }}">
                                {{-- Rank --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($data['rank'] === 1)
                                            <div class="flex items-center justify-center w-10 h-10 bg-yellow-500 text-white rounded-full font-bold text-lg">
                                                🥇
                                            </div>
                                        @elseif($data['rank'] === 2)
                                            <div class="flex items-center justify-center w-10 h-10 bg-gray-400 text-white rounded-full font-bold text-lg">
                                                🥈
                                            </div>
                                        @elseif($data['rank'] === 3)
                                            <div class="flex items-center justify-center w-10 h-10 bg-orange-500 text-white rounded-full font-bold text-lg">
                                                🥉
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center w-10 h-10 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full font-bold">
                                                {{ $data['rank'] }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Name --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $data['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $data['email'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Approved Reports --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $data['approved_reports'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        dari {{ $data['total_reports'] }} laporan
                                    </div>
                                </td>

                                {{-- Approval Rate --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $data['approval_rate'] }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $data['approval_rate'] }}%
                                        </span>
                                    </div>
                                </td>

                                {{-- Avg Report Rating --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($data['avg_report_rating'])
                                        <div class="flex items-center justify-center gap-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= round($data['avg_report_rating']) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $data['avg_report_rating'] }}/5.0
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">N/A</span>
                                    @endif
                                </td>

                                {{-- Score --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold {{
                                        $data['rank'] === 1 ? 'text-yellow-600 dark:text-yellow-400' :
                                        ($data['rank'] === 2 ? 'text-gray-600 dark:text-gray-400' :
                                        ($data['rank'] === 3 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white'))
                                    }}">
                                        {{ number_format($data['score'], 1) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Score Formula Info --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">📊 Cara Perhitungan Skor</h4>
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>Skor Total</strong> = (Laporan Disetujui × 10) + (Rating Laporan × 20)
            </p>
            <ul class="text-xs text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                <li>• Setiap laporan yang disetujui: <strong>+10 poin</strong></li>
                <li>• Rating rata-rata dari laporan yang disetujui: <strong>×20 multiplier</strong></li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
