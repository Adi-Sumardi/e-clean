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
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .stat-box {
            display: table-cell;
            width: 20%;
            text-align: center;
            padding: 10px;
            background: #F3F4F6;
            border-right: 2px solid white;
        }
        .stat-box:last-child {
            border-right: none;
        }
        .stat-box .number {
            font-size: 18px;
            font-weight: bold;
        }
        .stat-box .label {
            font-size: 8px;
            color: #666;
            margin-top: 3px;
        }
        .stat-total .number { color: #4F46E5; }
        .stat-ontime .number { color: #059669; }
        .stat-late .number { color: #D97706; }
        .stat-expired .number { color: #DC2626; }
        .stat-rating .number { color: #F59E0B; }

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
            padding: 5px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 8px;
        }
        table.data-table tbody tr:nth-child(even) {
            background: #F9FAFB;
        }
        .unit-header td {
            background: #EEF2FF !important;
            font-weight: bold;
            font-size: 9px;
            color: #4338CA;
            padding: 6px 5px;
            border-bottom: 2px solid #C7D2FE;
        }
        .petugas-header td {
            background: #F3F4F6 !important;
            font-weight: bold;
            font-size: 8px;
            color: #374151;
            padding: 4px 5px 4px 15px;
            border-bottom: 1px solid #D1D5DB;
        }
        .status {
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
            display: inline-block;
        }
        .status-ontime {
            background: #D1FAE5;
            color: #065F46;
        }
        .status-late {
            background: #FEF3C7;
            color: #92400E;
        }
        .status-expired {
            background: #FEE2E2;
            color: #991B1B;
        }
        .rating {
            color: #F59E0B;
            font-weight: bold;
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
        <div class="stat-box stat-total">
            <div class="number">{{ $stats['total'] }}</div>
            <div class="label">Total Laporan</div>
        </div>
        <div class="stat-box stat-ontime">
            <div class="number">{{ $stats['ontime_pct'] }}%</div>
            <div class="label">Tepat Waktu ({{ $stats['ontime'] }})</div>
        </div>
        <div class="stat-box stat-late">
            <div class="number">{{ $stats['late_pct'] }}%</div>
            <div class="label">Terlambat ({{ $stats['late'] }})</div>
        </div>
        <div class="stat-box stat-expired">
            <div class="number">{{ $stats['expired_pct'] }}%</div>
            <div class="label">Tidak Lapor ({{ $stats['expired'] }})</div>
        </div>
        <div class="stat-box stat-rating">
            <div class="number">{{ $stats['avg_rating'] }}/5</div>
            <div class="label">Rata-rata Rating</div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 10%">Tanggal</th>
                <th style="width: 14%">Petugas</th>
                <th style="width: 12%">Unit</th>
                <th style="width: 14%">Lokasi</th>
                <th style="width: 30%">Kegiatan</th>
                <th style="width: 9%">Status Lapor</th>
                <th style="width: 8%">Rating</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($grouped as $unitName => $petugasGroups)
                <tr class="unit-header">
                    <td colspan="8">{{ $unitName }} ({{ $petugasGroups->flatten(1)->count() }} laporan)</td>
                </tr>

                @foreach($petugasGroups as $petugasName => $petugasReports)
                    <tr class="petugas-header">
                        <td colspan="8">{{ $petugasName }} ({{ count($petugasReports) }} laporan)</td>
                    </tr>

                    @foreach($petugasReports as $report)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ $report->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $report->petugas->name ?? '-' }}</td>
                            <td>{{ $report->lokasi->unit->nama_unit ?? '-' }}</td>
                            <td>{{ $report->lokasi->nama_lokasi ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($report->kegiatan, 60) }}</td>
                            <td>
                                @php
                                    $statusLabels = [
                                        'ontime' => 'Tepat Waktu',
                                        'late' => 'Terlambat',
                                        'expired' => 'Tidak Lapor',
                                    ];
                                @endphp
                                <span class="status status-{{ $report->reporting_status }}">
                                    {{ $statusLabels[$report->reporting_status] ?? ucfirst($report->reporting_status ?? '-') }}
                                </span>
                            </td>
                            <td class="rating">{{ $report->rating ? $report->rating . '/5' : '-' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>E-Cleaning Service Management System - Generated by {{ config('app.name') }}</p>
    </div>
</body>
</html>
