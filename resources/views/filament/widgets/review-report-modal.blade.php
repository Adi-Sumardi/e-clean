<div class="space-y-6">
    {{-- Informasi Umum --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Petugas</label>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->petugas->name }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->tanggal->format('d M Y') }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi</label>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->lokasi->nama_lokasi }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Waktu</label>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ $record->jam_mulai }} - {{ $record->jam_selesai ?? 'Belum selesai' }}
            </p>
        </div>
    </div>

    {{-- Deskripsi Kegiatan --}}
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi Kegiatan</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $record->kegiatan }}</p>
    </div>

    {{-- Foto Sebelum --}}
    @if($record->foto_sebelum && count($record->foto_sebelum) > 0)
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Foto Sebelum Dibersihkan</label>
        <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($record->foto_sebelum as $foto)
            <img src="{{ Storage::url($foto) }}" alt="Foto Sebelum" class="w-full h-48 object-cover rounded-lg">
            @endforeach
        </div>
    </div>
    @endif

    {{-- Foto Sesudah --}}
    @if($record->foto_sesudah && count($record->foto_sesudah) > 0)
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Foto Sesudah Dibersihkan</label>
        <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($record->foto_sesudah as $foto)
            <img src="{{ Storage::url($foto) }}" alt="Foto Sesudah" class="w-full h-48 object-cover rounded-lg">
            @endforeach
        </div>
    </div>
    @endif

    {{-- Status --}}
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
        <p class="mt-1">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $record->status === 'submitted' ? 'bg-yellow-100 text-yellow-800' :
                   ($record->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                {{ ucfirst($record->status) }}
            </span>
        </p>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('filament.admin.resources.activity-reports.index') }}"
           class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Buka di Halaman Laporan
        </a>
    </div>
</div>
