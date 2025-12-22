<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokasi Tidak Ditemukan - E-Clean</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <!-- Error Icon -->
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">Lokasi Tidak Ditemukan</h1>
            <p class="text-gray-600 mb-6">
                Maaf, lokasi dengan kode <span class="font-semibold text-red-600">"{{ $kode }}"</span> tidak ditemukan atau sudah tidak aktif.
            </p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-left">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium">Kemungkinan penyebab:</p>
                        <ul class="mt-1 list-disc list-inside text-yellow-700">
                            <li>Barcode rusak atau tidak terbaca dengan benar</li>
                            <li>Lokasi sudah dipindahkan atau dihapus</li>
                            <li>Kode lokasi salah ketik</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <a href="javascript:history.back()"
                   class="block w-full bg-gray-200 text-gray-700 font-semibold py-3 rounded-lg hover:bg-gray-300 transition">
                    &larr; Kembali
                </a>
            </div>
        </div>

        <p class="text-sm text-gray-500 mt-6">
            Jika masalah berlanjut, hubungi petugas kebersihan terdekat.
        </p>
    </div>
</body>
</html>