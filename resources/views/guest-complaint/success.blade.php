<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Terkirim - E-Clean</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .success-animation {
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <!-- Success Icon -->
            <div class="success-animation w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">Laporan Terkirim!</h1>
            <p class="text-gray-600 mb-6">
                Terima kasih telah melaporkan keluhan. Tim kebersihan kami akan segera menangani laporan Anda.
            </p>

            @if($lokasi)
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <p class="text-sm text-gray-500">Lokasi yang dilaporkan:</p>
                    <p class="font-medium text-gray-800">{{ $lokasi->nama_lokasi }}</p>
                    <p class="text-sm text-gray-500">{{ $lokasi->kode_lokasi }}</p>
                </div>
            @endif

            <div class="space-y-3">
                <a href="{{ route('guest-complaint.form', ['lokasi' => $lokasi?->kode_lokasi ?? '']) }}"
                   class="block w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 transition">
                    Laporkan Keluhan Lain
                </a>
            </div>
        </div>

        <p class="text-sm text-gray-500 mt-6">
            Powered by E-Clean
        </p>
    </div>
</body>
</html>
