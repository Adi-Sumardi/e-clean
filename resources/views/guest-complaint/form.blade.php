<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#3b82f6">
    <title>Laporkan Keluhan - {{ $lokasi->nama_lokasi }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        * { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

        body {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 50%, #eef2ff 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #4338ca 100%);
            color: white;
            text-align: center;
            padding: 2rem 1rem;
        }

        .header-icon {
            width: 4rem;
            height: 4rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header-icon svg { width: 2rem; height: 2rem; }
        .header h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .header p { color: #bfdbfe; font-size: 0.875rem; }

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
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .location-card {
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .location-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(59,130,246,0.4);
            flex-shrink: 0;
        }

        .location-icon svg { width: 1.75rem; height: 1.75rem; color: white; }
        .location-label { font-size: 0.75rem; font-weight: 600; color: #2563eb; text-transform: uppercase; letter-spacing: 0.05em; }
        .location-name { font-size: 1.125rem; font-weight: 700; color: #111827; }
        .location-code { font-size: 0.875rem; color: #6b7280; }

        .error-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 0.75rem;
        }

        .error-box svg { width: 1.25rem; height: 1.25rem; color: #ef4444; flex-shrink: 0; margin-top: 0.125rem; }
        .error-title { font-weight: 600; color: #991b1b; font-size: 0.875rem; }
        .error-list { margin-top: 0.25rem; font-size: 0.875rem; color: #dc2626; list-style: disc; padding-left: 1.25rem; }

        .form-content { padding: 1.25rem; }
        .form-section { margin-bottom: 1.5rem; }
        .form-section:last-child { margin-bottom: 0; }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .section-number {
            width: 1.75rem;
            height: 1.75rem;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
        }

        .section-title { font-weight: 600; color: #1f2937; }
        .required { color: #ef4444; }

        .form-group { margin-bottom: 1rem; }
        .form-group:last-child { margin-bottom: 0; }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            color: #111827;
            transition: all 0.2s;
        }

        .form-input::placeholder { color: #9ca3af; }
        .form-input:focus {
            outline: none;
            background: white;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }

        textarea.form-input { resize: none; min-height: 100px; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .divider {
            height: 1px;
            background: #f1f5f9;
            margin: 1.5rem 0;
        }

        .radio-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .radio-card {
            position: relative;
            cursor: pointer;
        }

        .radio-card input { position: absolute; opacity: 0; }

        .radio-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            transition: all 0.2s;
        }

        .radio-card input:checked + .radio-content {
            background: #eff6ff;
            border-color: #3b82f6;
        }

        .radio-emoji { font-size: 1.5rem; }
        .radio-label { font-size: 0.875rem; font-weight: 500; color: #374151; }
        .radio-card input:checked + .radio-content .radio-label { color: #1d4ed8; }

        .radio-check {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            background: #3b82f6;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .radio-card input:checked ~ .radio-check { display: flex; }
        .radio-check svg { width: 0.75rem; height: 0.75rem; color: white; }

        .upload-zone {
            border: 2px dashed #d1d5db;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-zone:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }

        .upload-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
        }

        .upload-icon svg { width: 2rem; height: 2rem; color: #3b82f6; }
        .upload-text { font-size: 0.875rem; font-weight: 500; color: #4b5563; }
        .upload-hint { font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem; }

        .preview-container { display: none; }
        .preview-container.show { display: block; }
        .preview-image { max-height: 12rem; margin: 0 auto; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .preview-text { font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem; }

        .form-footer {
            padding: 1.25rem;
            background: #f9fafb;
            border-top: 1px solid #f1f5f9;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(37,99,235,0.4);
            transition: all 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.5);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn svg { width: 1.25rem; height: 1.25rem; }

        .submit-hint {
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.75rem;
        }

        .footer {
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 1.5rem;
        }

        .hidden { display: none !important; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin { animation: spin 1s linear infinite; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </div>
        <h1>Laporkan Keluhan</h1>
        <p>Bantu kami menjaga kebersihan</p>
    </div>

    <div class="container">
        <!-- Location Card -->
        <div class="card">
            <div class="location-card">
                <div class="location-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="location-label">Lokasi</p>
                    <h2 class="location-name">{{ $lokasi->nama_lokasi }}</h2>
                    <p class="location-code">{{ $lokasi->kode_lokasi }}@if($lokasi->lantai) &bull; {{ $lokasi->lantai }}@endif</p>
                </div>
            </div>
        </div>

        <!-- Error Alert -->
        @if($errors->any())
        <div class="error-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="error-title">Mohon perbaiki kesalahan:</p>
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Form Card -->
        <div class="card">
            <form id="complaintForm" action="{{ route('guest-complaint.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="lokasi_id" value="{{ $lokasi->id }}">

                <div class="form-content">
                    <!-- Section 1: Data Pelapor -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-number">1</span>
                            <h3 class="section-title">Data Pelapor</h3>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                            <input type="text" name="nama_pelapor" value="{{ old('nama_pelapor') }}" class="form-input" placeholder="Masukkan nama lengkap Anda" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">No. WhatsApp</label>
                                <input type="tel" name="telepon_pelapor" value="{{ old('telepon_pelapor') }}" class="form-input" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email_pelapor" value="{{ old('email_pelapor') }}" class="form-input" placeholder="email@mail.com">
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Section 2: Jenis Keluhan -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-number">2</span>
                            <h3 class="section-title">Jenis Keluhan <span class="required">*</span></h3>
                        </div>

                        <div class="radio-grid">
                            @php
                            $iconMap = [
                                'tumpahan' => '💧',
                                'kotor' => '🗑️',
                                'bau' => '👃',
                                'rusak' => '🔧',
                                'lainnya' => '📝',
                            ];
                            @endphp

                            @foreach($jenisKeluhanOptions as $value => $label)
                            <label class="radio-card">
                                <input type="radio" name="jenis_keluhan" value="{{ $value }}" {{ old('jenis_keluhan') == $value ? 'checked' : '' }} required>
                                <div class="radio-content">
                                    <span class="radio-emoji">{{ $iconMap[$value] ?? '📋' }}</span>
                                    <span class="radio-label">{{ $label }}</span>
                                </div>
                                <div class="radio-check">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Section 3: Detail Keluhan -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-number">3</span>
                            <h3 class="section-title">Detail Keluhan</h3>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Deskripsi <span class="required">*</span></label>
                            <textarea name="deskripsi_keluhan" class="form-input" placeholder="Jelaskan keluhan Anda secara detail..." required>{{ old('deskripsi_keluhan') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Foto <span style="color: #9ca3af; font-weight: 400;">(Opsional)</span></label>
                            <div class="upload-zone" onclick="document.getElementById('foto_keluhan').click()">
                                <input type="file" id="foto_keluhan" name="foto_keluhan" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this)">

                                <div id="preview-container" class="preview-container">
                                    <img id="preview-image" class="preview-image">
                                    <p class="preview-text">Ketuk untuk ganti foto</p>
                                </div>

                                <div id="upload-placeholder">
                                    <div class="upload-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="upload-text">Ketuk untuk ambil foto</p>
                                    <p class="upload-hint">JPG, PNG (Maks. 5MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" id="submitBtn" class="submit-btn">
                        <span id="btnText">Kirim Laporan</span>
                        <svg id="btnIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <svg id="btnSpinner" class="hidden animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <p class="submit-hint">Dengan mengirim, Anda membantu menjaga kebersihan</p>
                </div>
            </form>
        </div>

        <p class="footer">&copy; {{ date('Y') }} E-Clean &bull; Sistem Manajemen Kebersihan</p>
    </div>

    <script>
        function previewImage(input) {
            var preview = document.getElementById('preview-image');
            var previewContainer = document.getElementById('preview-container');
            var placeholder = document.getElementById('upload-placeholder');

            if (input.files && input.files[0]) {
                var file = input.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran foto terlalu besar. Maksimal 5MB.');
                    input.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.add('show');
                    placeholder.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('complaintForm').addEventListener('submit', function() {
            var btn = document.getElementById('submitBtn');
            var btnText = document.getElementById('btnText');
            var btnIcon = document.getElementById('btnIcon');
            var btnSpinner = document.getElementById('btnSpinner');

            btn.disabled = true;
            btnText.textContent = 'Mengirim...';
            btnIcon.classList.add('hidden');
            btnSpinner.classList.remove('hidden');
        });

        // Format phone number
        var phoneInput = document.querySelector('input[name="telepon_pelapor"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                var value = e.target.value.replace(/\D/g, '');
                if (value.length > 13) value = value.slice(0, 13);
                e.target.value = value;
            });
        }
    </script>
</body>
</html>
