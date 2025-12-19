<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporkan Keluhan - {{ $lokasi->nama_lokasi }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="gradient-bg py-6">
        <div class="max-w-lg mx-auto px-4">
            <h1 class="text-2xl font-bold text-white text-center">Laporkan Keluhan</h1>
            <p class="text-white/80 text-center mt-1">Bantu kami menjaga kebersihan</p>
        </div>
    </div>

    <div class="max-w-lg mx-auto px-4 -mt-4">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <!-- Lokasi Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Lokasi</p>
                        <p class="font-semibold text-gray-800">{{ $lokasi->nama_lokasi }}</p>
                        <p class="text-sm text-gray-500">{{ $lokasi->kode_lokasi }} @if($lokasi->lantai) - {{ $lokasi->lantai }} @endif</p>
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-red-800">Ada kesalahan:</p>
                            <ul class="mt-1 text-sm text-red-600 list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('guest-complaint.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="lokasi_id" value="{{ $lokasi->id }}">

                <!-- Nama Pelapor -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Anda <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_pelapor" value="{{ old('nama_pelapor') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="Masukkan nama Anda" required>
                </div>

                <!-- Kontak (opsional) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email_pelapor" value="{{ old('email_pelapor') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                            placeholder="email@contoh.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                        <input type="tel" name="telepon_pelapor" value="{{ old('telepon_pelapor') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                            placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <!-- Jenis Keluhan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Keluhan <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($jenisKeluhanOptions as $value => $label)
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition {{ old('jenis_keluhan') == $value ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                                <input type="radio" name="jenis_keluhan" value="{{ $value }}"
                                    class="text-blue-500 focus:ring-blue-500"
                                    {{ old('jenis_keluhan') == $value ? 'checked' : '' }} required>
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Deskripsi Keluhan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="deskripsi_keluhan" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-none"
                        placeholder="Jelaskan keluhan Anda secara detail..." required>{{ old('deskripsi_keluhan') }}</textarea>
                </div>

                <!-- Foto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto (opsional)</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer" onclick="document.getElementById('foto_keluhan').click()">
                        <input type="file" id="foto_keluhan" name="foto_keluhan" accept="image/*" class="hidden" onchange="previewImage(this)">
                        <div id="preview-container" class="hidden">
                            <img id="preview-image" class="max-h-40 mx-auto rounded-lg">
                            <p class="text-sm text-gray-500 mt-2">Klik untuk ganti foto</p>
                        </div>
                        <div id="upload-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm text-gray-500 mt-2">Klik untuk upload foto</p>
                            <p class="text-xs text-gray-400">Maks. 5MB</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold py-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition shadow-lg">
                    Kirim Laporan
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-500 mt-6 mb-8">
            Terima kasih telah membantu menjaga kebersihan.
        </p>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview-image');
            const previewContainer = document.getElementById('preview-container');
            const placeholder = document.getElementById('upload-placeholder');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
