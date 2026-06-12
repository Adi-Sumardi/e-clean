<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanKeterlambatan;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Laporan keterlambatan/tidak lapor. Record DIBUAT OTOMATIS oleh sistem
 * (command CheckMissedSchedules) saat petugas melewati batas waktu jadwal —
 * tidak ada create/edit manual, sama seperti resource Filament-nya.
 * Supervisor/admin hanya melihat dan menghapus.
 *
 * @group Laporan Keterlambatan
 */
class LaporanKeterlambatanController extends Controller
{
    use ApiResponse;

    private const VIEWER_ROLES = ['super_admin', 'admin', 'supervisor'];

    private function canView(Request $request): bool
    {
        return $request->user()->hasAnyRole(self::VIEWER_ROLES);
    }

    private function baseQuery(Request $request): Builder
    {
        $query = LaporanKeterlambatan::query()->with(['petugas:id,name', 'lokasi.unit']);

        // Parity Filament: selain super_admin/admin dibatasi 30 hari terakhir.
        if (! $request->user()->hasAnyRole(['super_admin', 'admin'])) {
            $query->where('tanggal', '>=', Carbon::now()->subDays(30));
        }

        if ($request->filled('domain')) {
            $query->where('domain', $request->domain);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }
        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereMonth('tanggal', $request->bulan)->whereYear('tanggal', $request->tahun);
        }

        return $query;
    }

    private function transform(LaporanKeterlambatan $item): array
    {
        return [
            'id' => $item->id,
            'domain' => $item->domain ?? 'kebersihan',
            'tanggal' => $item->tanggal?->format('Y-m-d'),
            'shift' => $item->shift,
            'status' => $item->status,
            'keterangan' => $item->keterangan,
            'batas_waktu_mulai' => $item->batas_waktu_mulai?->format('H:i'),
            'batas_waktu_selesai' => $item->batas_waktu_selesai?->format('H:i'),
            'waktu_terdeteksi' => $item->waktu_terdeteksi?->toIso8601String(),
            'petugas' => $item->petugas ? [
                'id' => $item->petugas->id,
                'name' => $item->petugas->name,
            ] : null,
            'lokasi' => $item->lokasi ? [
                'id' => $item->lokasi->id,
                'nama_lokasi' => $item->lokasi->nama_lokasi,
                'unit' => $item->lokasi->unit ? [
                    'id' => $item->lokasi->unit->id,
                    'nama_unit' => $item->lokasi->unit->nama_unit,
                ] : null,
            ] : null,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        try {
            if (! $this->canView($request)) {
                return $this->forbiddenResponse('You are not allowed to view late reports.');
            }

            $items = $this->baseQuery($request)
                ->orderBy('tanggal', 'desc')
                ->orderBy('waktu_terdeteksi', 'desc')
                ->limit(200)
                ->get();

            return $this->successResponse(
                $items->map(fn ($item) => $this->transform($item))->values(),
                'Late reports retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve late reports: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            if (! $this->canView($request)) {
                return $this->forbiddenResponse('You are not allowed to view late reports.');
            }

            $item = $this->baseQuery($request)->find($id);
            if (! $item) {
                return $this->notFoundResponse('Late report not found');
            }

            return $this->successResponse($this->transform($item), 'Late report retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve late report: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Parity Filament: hanya admin & supervisor yang boleh hapus.
            if (! $request->user()->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
                return $this->forbiddenResponse('You are not allowed to delete late reports.');
            }

            $item = LaporanKeterlambatan::find($id);
            if (! $item) {
                return $this->notFoundResponse('Late report not found');
            }

            $item->delete();

            return $this->successResponse(null, 'Late report deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete late report: ' . $e->getMessage(), 500);
        }
    }
}
