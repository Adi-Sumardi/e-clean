<div class="space-y-6">
    {{-- Header Info Card --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 dark:bg-blue-950 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-500 rounded-full p-2 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-0.5">📅 Tanggal</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($record->tanggal)->locale('id')->isoFormat('D MMMM YYYY') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-950 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <div class="flex items-center space-x-3">
                <div class="bg-green-500 rounded-full p-2 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-0.5">⏰ Waktu Kerja</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($record->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($record->jam_selesai)->format('H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-950 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center space-x-3">
                <div class="bg-purple-500 rounded-full p-2 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-0.5">📍 Lokasi</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $record->lokasi->nama_lokasi }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Deskripsi Kegiatan --}}
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-start space-x-3">
            <div class="bg-gray-500 rounded-full p-2 mt-0.5">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deskripsi Kegiatan</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $record->kegiatan }}</p>
            </div>
        </div>
    </div>

    {{-- Foto Dokumentasi --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Dokumentasi Foto
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Foto Sebelum --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">📸 Sebelum Dibersihkan</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ is_array($record->foto_sebelum) ? count($record->foto_sebelum) : 0 }} foto
                    </span>
                </div>
                @if($record->foto_sebelum && is_array($record->foto_sebelum) && count($record->foto_sebelum) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($record->foto_sebelum as $foto)
                            <a href="{{ Storage::url($foto) }}" target="_blank" class="group relative block overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 transition-all duration-200">
                                <img src="{{ Storage::url($foto) }}"
                                     alt="Foto Sebelum"
                                     class="w-full h-40 object-cover group-hover:scale-110 transition-transform duration-300">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center justify-center h-40 bg-gray-100 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2 text-xs text-gray-500">Tidak ada foto</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Foto Sesudah --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">✨ Sesudah Dibersihkan</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ is_array($record->foto_sesudah) ? count($record->foto_sesudah) : 0 }} foto
                    </span>
                </div>
                @if($record->foto_sesudah && is_array($record->foto_sesudah) && count($record->foto_sesudah) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($record->foto_sesudah as $foto)
                            <a href="{{ Storage::url($foto) }}" target="_blank" class="group relative block overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 transition-all duration-200">
                                <img src="{{ Storage::url($foto) }}"
                                     alt="Foto Sesudah"
                                     class="w-full h-40 object-cover group-hover:scale-110 transition-transform duration-300">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center justify-center h-40 bg-gray-100 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2 text-xs text-gray-500">Tidak ada foto</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Catatan Petugas --}}
    @if($record->catatan_petugas)
        <div class="bg-amber-50 dark:bg-amber-950 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
            <div class="flex items-start space-x-3">
                <div class="bg-amber-500 rounded-full p-2 mt-0.5">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-2">💬 Catatan dari Petugas</p>
                    <p class="text-sm text-amber-700 dark:text-amber-400 leading-relaxed">{{ $record->catatan_petugas }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Divider --}}
    <div class="border-t-2 border-dashed border-gray-300 dark:border-gray-600 my-6"></div>

    {{-- Review Section Title --}}
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="w-6 h-6 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Form Review & Approval
        </h3>
        <div class="flex items-center space-x-2">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-xs text-gray-600 dark:text-gray-400">Review oleh: {{ auth()->user()->name }}</span>
        </div>
    </div>
</div>
