<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2430; margin: 0; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 16px; margin: 0 0 2px; }
        .header .sub { font-size: 11px; color: #555; }
        .meta { font-size: 10px; color: #555; margin: 8px 0; }
        .summary { margin: 8px 0; }
        .summary span {
            display: inline-block; padding: 3px 8px; margin-right: 6px;
            border-radius: 6px; background: #eef1fb; font-size: 10px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cdd3e0; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #eef1fb; font-size: 10px; }
        td { font-size: 9.5px; }
        .status { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .status-approved { background: #d6f3ea; color: #138a68; }
        .status-submitted, .status-pending { background: #fdf0d5; color: #b07d12; }
        .status-rejected { background: #fbe0dd; color: #c0392b; }
        .foot { margin-top: 14px; font-size: 9px; color: #888; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="sub">Apps KopkarYAPI — Koperasi Karyawan YAPI</div>
        @if($period)<div class="sub">Periode: {{ $period }}</div>@endif
    </div>

    <div class="summary">
        <span>Total: {{ $totalReports }}</span>
        <span>Disetujui: {{ $approvedReports }}</span>
        <span>Menunggu: {{ $pendingReports }}</span>
        <span>Ditolak: {{ $rejectedReports }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:18px;">No</th>
                <th style="width:62px;">Tanggal</th>
                <th>Petugas</th>
                <th>Lokasi</th>
                <th>Unit</th>
                <th style="width:75px;">Jam</th>
                <th style="width:62px;">Status</th>
                <th style="width:34px;">Nilai</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $i => $report)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $report->tanggal ? \Carbon\Carbon::parse($report->tanggal)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $report->petugas->name ?? '-' }}</td>
                    <td>{{ $report->lokasi->nama_lokasi ?? '-' }}</td>
                    <td>{{ $report->lokasi->unit->nama_unit ?? '-' }}</td>
                    <td>
                        {{ $report->jam_mulai ? \Carbon\Carbon::parse($report->jam_mulai)->format('H:i') : '-' }}
                        @if($report->jam_selesai) - {{ \Carbon\Carbon::parse($report->jam_selesai)->format('H:i') }}@endif
                    </td>
                    <td><span class="status status-{{ $report->status }}">{{ ucfirst($report->status) }}</span></td>
                    <td>{{ $report->rating ? $report->rating.'/5' : '-' }}</td>
                    <td>{{ $report->catatan_petugas ? \Str::limit($report->catatan_petugas, 60) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#888;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">Dicetak: {{ $generatedAt }}</div>
</body>
</html>
