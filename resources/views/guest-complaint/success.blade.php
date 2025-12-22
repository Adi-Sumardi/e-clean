<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10b981">
    <title>Laporan Terkirim - E-Clean</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        * { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

        body {
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 50%, #f0fdf4 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .success-icon {
            width: 5rem;
            height: 5rem;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            box-shadow: 0 10px 25px rgba(16,185,129,0.4);
            animation: successPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes successPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-icon svg {
            width: 2.5rem;
            height: 2.5rem;
            color: #10b981;
        }

        .checkmark {
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: drawCheck 0.6s ease-out 0.3s forwards;
        }

        @keyframes drawCheck {
            to { stroke-dashoffset: 0; }
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            animation: fadeUp 0.5s ease-out 0.4s both;
        }

        .header p {
            color: #a7f3d0;
            font-size: 0.875rem;
            animation: fadeUp 0.5s ease-out 0.6s both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            max-width: 28rem;
            margin: 0 auto;
            padding: 0 1rem 2rem;
            margin-top: -1.5rem;
            position: relative;
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            animation: fadeUp 0.5s ease-out 0.6s both;
        }

        .card-content { padding: 1.5rem; }

        .message {
            text-align: center;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .location-box {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .location-icon {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(16,185,129,0.4);
            flex-shrink: 0;
        }

        .location-icon svg { width: 1.5rem; height: 1.5rem; color: white; }
        .location-label { font-size: 0.75rem; font-weight: 600; color: #10b981; text-transform: uppercase; letter-spacing: 0.05em; }
        .location-name { font-size: 1.125rem; font-weight: 700; color: #111827; }
        .location-code { font-size: 0.875rem; color: #6b7280; }

        .steps-box {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .steps-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .steps-title svg { width: 1rem; height: 1rem; color: #6b7280; }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .step:last-child { margin-bottom: 0; }

        .step-number {
            width: 1.5rem;
            height: 1.5rem;
            background: #d1fae5;
            color: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .step-text { font-size: 0.875rem; color: #4b5563; }

        .btn {
            display: block;
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37,99,235,0.4);
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.5);
        }

        .footer {
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path class="checkmark" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1>Laporan Terkirim!</h1>
        <p>Terima kasih telah melaporkan keluhan Anda</p>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-content">
                <p class="message">
                    Tim kebersihan kami akan segera menangani laporan Anda.
                    @if($lokasi)
                    Kami akan mengirimkan update status ke nomor WhatsApp yang Anda berikan.
                    @endif
                </p>

                @if($lokasi)
                <div class="location-box">
                    <div class="location-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="location-label">Lokasi Dilaporkan</p>
                        <h2 class="location-name">{{ $lokasi->nama_lokasi }}</h2>
                        <p class="location-code">{{ $lokasi->kode_lokasi }}</p>
                    </div>
                </div>
                @endif

                <div class="steps-box">
                    <h3 class="steps-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Langkah Selanjutnya
                    </h3>
                    <div class="step">
                        <span class="step-number">1</span>
                        <p class="step-text">Tim kami akan menerima notifikasi keluhan Anda</p>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <p class="step-text">Petugas akan segera menuju lokasi untuk penanganan</p>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <p class="step-text">Anda akan menerima update via WhatsApp saat selesai</p>
                    </div>
                </div>

                @if($lokasi)
                <a href="{{ route('guest-complaint.form', ['lokasi' => $lokasi->kode_lokasi]) }}" class="btn">
                    Laporkan Keluhan Lain
                </a>
                @endif
            </div>
        </div>

        <p class="footer">&copy; {{ date('Y') }} E-Clean &bull; Sistem Manajemen Kebersihan</p>
    </div>
</body>
</html>
