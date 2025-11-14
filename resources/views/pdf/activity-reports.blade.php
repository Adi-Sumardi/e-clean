<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            color: #4F46E5;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10px;
            color: #666;
        }
        .info-box {
            background: #F3F4F6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .info-box table {
            width: 100%;
        }
        .info-box td {
            padding: 3px 0;
        }
        .info-box strong {
            color: #4F46E5;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table thead {
            background: #4F46E5;
            color: white;
        }
        table.data-table th {
            padding: 8px 5px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
        }
        table.data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 8px;
        }
        table.data-table tbody tr:nth-child(even) {
            background: #F9FAFB;
        }
        .status {
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
            display: inline-block;
        }
        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }
        .status-approved {
            background: #D1FAE5;
            color: #065F46;
        }
        .status-rejected {
            background: #FEE2E2;
            color: #991B1B;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 7px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #E5E7EB;
        }
        .rating {
            color: #F59E0B;
            font-weight: bold;
        }
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .stat-box {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            background: #F3F4F6;
            border-right: 2px solid white;
        }
        .stat-box:last-child {
            border-right: none;
        }
        .stat-box .number {
            font-size: 20px;
            font-weight: bold;
            color: #4F46E5;
        }
        .stat-box .label {
            font-size: 8px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        @if($period)
            <p>Periode: {{ $period }}</p>
        @endif
        <p>Digenerate pada: {{ $generatedAt }}</p>
    </div>

    <div class="summary-stats">
        <div class="stat-box">
            <div class="number">{{ $totalReports }}</div>
            <div class="label">Total Laporan</div>
        </div>
        <div class="stat-box">
            <div class="number">{{ $approvedReports }}</div>
            <div class="label">Approved</div>
        </div>
        <div class="stat-box">
            <div class="number">{{ $pendingReports }}</div>
            <div class="label">Pending</div>
        </div>
        <div class="stat-box">
            <div class="number">{{ $rejectedReports }}</div>
            <div class="label">Rejected</div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 10%">Tanggal</th>
                <th style="width: 12%">Petugas</th>
                <th style="width: 15%">Lokasi</th>
                <th style="width: 8%">Kode</th>
                <th style="width: 8%">Waktu</th>
                <th style="width: 5%">Rating</th>
                <th style="width: 25%">Catatan</th>
                <th style="width: 8%">Status</th>
                <th style="width: 6%">GPS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $index => $report)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $report->created_at ? $report->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $report->petugas->name ?? '-' }}</td>
                    <td>{{ $report->lokasi->nama_lokasi ?? '-' }}</td>
                    <td>{{ $report->lokasi->kode_lokasi ?? '-' }}</td>
                    <td>
                        @if($report->waktu_mulai && $report->waktu_selesai)
                            {{ \Carbon\Carbon::parse($report->waktu_mulai)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($report->waktu_selesai)->format('H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="rating">{{ $report->rating ? $report->rating . '/5' : '-' }}</td>
                    <td>{{ $report->catatan ? \Str::limit($report->catatan, 50) : '-' }}</td>
                    <td>
                        <span class="status status-{{ $report->status }}">
                            {{ ucfirst($report->status) }}
                        </span>
                    </td>
                    <td style="font-size: 6px;">
                        @if($report->latitude && $report->longitude)
                            ✓
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>E-Cleaning Service Management System - Generated by {{ config('app.name') }}</p>
    </div>
</body>
</html>
