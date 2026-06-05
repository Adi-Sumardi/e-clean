<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityReport;
use App\Models\GuestComplaint;
use App\Models\LaporanOb;
use App\Models\LaporanSatpam;
use App\Models\LaporanToko;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Synthesizes a per-user notification feed from real domain events (no
 * separate notifications table). Drives the bell icon on the mobile
 * dashboards.
 *
 * @group Notifications
 */
class NotificationController extends Controller
{
    use ApiResponse;

    private const MANAGER_ROLES = ['super_admin', 'admin', 'supervisor', 'pengurus'];

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $items = $user->hasAnyRole(self::MANAGER_ROLES)
                ? $this->managerFeed()
                : $this->fieldFeed($user->id, $user->getRoleNames()->first());

            // Newest first.
            usort($items, fn ($a, $b) => strcmp($b['time'] ?? '', $a['time'] ?? ''));

            return $this->successResponse([
                'count' => count($items),
                'items' => array_slice($items, 0, 50),
            ], 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notifications: ' . $e->getMessage(), 500);
        }
    }

    /** Managers: everything awaiting their action. */
    private function managerFeed(): array
    {
        $items = [];

        $domains = [
            ['model' => ActivityReport::class, 'scope' => 'kebersihan', 'label' => 'Kebersihan'],
            ['model' => LaporanSatpam::class, 'scope' => 'satpam', 'label' => 'Satpam'],
            ['model' => LaporanOb::class, 'scope' => 'ob', 'label' => 'Office Boy'],
            ['model' => LaporanToko::class, 'scope' => 'toko', 'label' => 'Toko'],
        ];

        foreach ($domains as $d) {
            $model = $d['model'];
            $reports = $model::with(['petugas', 'lokasi'])
                ->where('status', 'submitted')
                ->latest()
                ->limit(20)
                ->get();

            foreach ($reports as $r) {
                $items[] = [
                    'id' => "approval-{$d['scope']}-{$r->id}",
                    'type' => 'approval',
                    'scope' => $d['scope'],
                    'ref_id' => $r->id,
                    'title' => "Laporan {$d['label']} menunggu approval",
                    'body' => trim(($r->petugas->name ?? 'Petugas') . ' · ' . ($r->lokasi->nama_lokasi ?? '-')),
                    'time' => $r->created_at?->toISOString(),
                    'read' => false,
                ];
            }
        }

        // Open guest complaints
        GuestComplaint::with(['lokasi'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->limit(20)
            ->get()
            ->each(function ($c) use (&$items) {
                $items[] = [
                    'id' => "complaint-{$c->id}",
                    'type' => 'guest_complaint',
                    'ref_id' => $c->id,
                    'lokasi_id' => $c->lokasi_id,
                    'title' => 'Keluhan tamu baru',
                    'body' => trim(($c->lokasi->nama_lokasi ?? '-') . ': ' . Str::limit($c->deskripsi_keluhan, 60)),
                    'time' => $c->created_at?->toISOString(),
                    'read' => false,
                ];
            });

        return $items;
    }

    /** Field staff: status of their own reports + complaints assigned to them. */
    private function fieldFeed(int $userId, ?string $role): array
    {
        $items = [];
        $since = Carbon::now()->subDays(14);

        $map = [
            'petugas' => ActivityReport::class,
            'satpam' => LaporanSatpam::class,
            'office_boy' => LaporanOb::class,
            'petugas_toko' => LaporanToko::class,
        ];
        $model = $map[$role] ?? ActivityReport::class;

        $model::with(['lokasi'])
            ->where('petugas_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('updated_at', '>=', $since)
            ->latest('updated_at')
            ->limit(30)
            ->get()
            ->each(function ($r) use (&$items) {
                $approved = $r->status === 'approved';
                $items[] = [
                    'id' => "status-{$r->id}",
                    'type' => $approved ? 'report_approved' : 'report_rejected',
                    'ref_id' => $r->id,
                    'title' => $approved ? 'Laporan disetujui' : 'Laporan ditolak',
                    'body' => $approved
                        ? (($r->lokasi->nama_lokasi ?? 'Laporan') . ' telah disetujui supervisor.')
                        : 'Ditolak: ' . Str::limit($r->rejected_reason ?? '-', 60),
                    'time' => $r->updated_at?->toISOString(),
                    'read' => false,
                ];
            });

        // Guest complaints assigned to this user (cleaning petugas)
        GuestComplaint::with(['lokasi'])
            ->where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->limit(20)
            ->get()
            ->each(function ($c) use (&$items) {
                $items[] = [
                    'id' => "complaint-{$c->id}",
                    'type' => 'guest_complaint',
                    'ref_id' => $c->id,
                    'lokasi_id' => $c->lokasi_id,
                    'title' => 'Keluhan tamu untuk Anda',
                    'body' => trim(($c->lokasi->nama_lokasi ?? '-') . ': ' . Str::limit($c->deskripsi_keluhan, 60)),
                    'time' => $c->created_at?->toISOString(),
                    'read' => false,
                ];
            });

        return $items;
    }
}
