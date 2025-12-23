<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#ef4444">
    <title>Lokasi Tidak Ditemukan - Clean Service System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        * { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

        body {
            background: linear-gradient(135deg, #fef2f2 0%, #ffffff 50%, #fff1f2 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
            color: white;
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .error-icon {
            width: 5rem;
            height: 5rem;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            box-shadow: 0 10px 25px rgba(239,68,68,0.4);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .error-icon svg {
            width: 2.5rem;
            height: 2.5rem;
            color: #ef4444;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            animation: fadeUp 0.5s ease-out 0.2s both;
        }

        .header p {
            color: #fecaca;
            font-size: 0.875rem;
            animation: fadeUp 0.5s ease-out 0.4s both;
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
            animation: fadeUp 0.5s ease-out 0.4s both;
        }

        .card-content { padding: 1.5rem; }

        .error-code-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .error-code-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #dc2626;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .error-code {
            font-size: 1.25rem;
            font-weight: 700;
            color: #b91c1c;
            font-family: 'Monaco', 'Consolas', monospace;
        }

        .reasons-box {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .reasons-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reasons-title svg { width: 1rem; height: 1rem; color: #f59e0b; }

        .reason {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .reason:last-child { margin-bottom: 0; }

        .reason-icon {
            width: 1.5rem;
            height: 1.5rem;
            background: #fef3c7;
            color: #d97706;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .reason-icon svg { width: 0.875rem; height: 0.875rem; }
        .reason-text { font-size: 0.875rem; color: #4b5563; }

        .tips-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .tips-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1d4ed8;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tips-title svg { width: 1rem; height: 1rem; }

        .tips-list {
            font-size: 0.875rem;
            color: #1e40af;
            list-style: none;
        }

        .tips-list li {
            margin-bottom: 0.25rem;
        }

        .tips-list li:last-child { margin-bottom: 0; }

        .btn {
            display: block;
            width: 100%;
            padding: 1rem 1.5rem;
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .btn svg { width: 1.25rem; height: 1.25rem; }

        .footer {
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 1.5rem;
        }

        .footer p:first-child { margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="header">
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <h1>Lokasi Tidak Ditemukan</h1>
        <p>Kode lokasi tidak valid atau sudah tidak aktif</p>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-content">
                <div class="error-code-box">
                    <p class="error-code-label">Kode Yang Dicari</p>
                    <p class="error-code">{{ $kode }}</p>
                </div>

                <div class="reasons-box">
                    <h3 class="reasons-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Kemungkinan Penyebab
                    </h3>
                    <div class="reason">
                        <div class="reason-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <p class="reason-text">QR Code rusak atau tidak terbaca dengan benar</p>
                    </div>
                    <div class="reason">
                        <div class="reason-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <p class="reason-text">Lokasi sudah dipindahkan atau dinonaktifkan</p>
                    </div>
                    <div class="reason">
                        <div class="reason-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <p class="reason-text">Kode lokasi salah ketik atau tidak lengkap</p>
                    </div>
                </div>

                <div class="tips-box">
                    <h3 class="tips-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Yang Bisa Anda Lakukan
                    </h3>
                    <ul class="tips-list">
                        <li>• Coba scan ulang QR Code dengan pencahayaan yang baik</li>
                        <li>• Hubungi petugas kebersihan terdekat untuk bantuan</li>
                        <li>• Pastikan QR Code tidak rusak atau terhalang</li>
                    </ul>
                </div>

                <a href="javascript:history.back()" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    Kembali
                </a>
            </div>
        </div>

        <div class="footer">
            <p>Jika masalah berlanjut, hubungi petugas kebersihan terdekat</p>
            <p>&copy; {{ date('Y') }} Clean Service System &bull; Sistem Manajemen Kebersihan</p>
        </div>
    </div>
</body>
</html>
