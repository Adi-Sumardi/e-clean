<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestComplaint;
use App\Models\User;
use App\Models\Lokasi;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class GuestComplaintController extends Controller
{
    use ApiResponse;

    /**
     * List guest complaints.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = GuestComplaint::with(['lokasi.unit', 'assignee', 'handler']);

            // If the user has role 'petugas', only show complaints assigned to them
            if ($user->hasRole('petugas')) {
                $query->where('assigned_to', $user->id);
            }

            // Optional status filtering
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Order newest first
            $complaints = $query->latest()->get();

            // Format complaints response
            $formatted = $complaints->map(function ($c) {
                return [
                    'id' => $c->id,
                    'nama_pelapor' => $c->nama_pelapor,
                    'email_pelapor' => $c->email_pelapor,
                    'telepon_pelapor' => $c->telepon_pelapor,
                    'jenis_keluhan' => $c->jenis_keluhan,
                    'deskripsi_keluhan' => $c->deskripsi_keluhan,
                    'foto_keluhan' => $c->foto_keluhan ? url('storage/' . $c->foto_keluhan) : null,
                    'status' => $c->status,
                    'assigned_to' => $c->assigned_to,
                    'assigned_at' => $c->assigned_at?->toISOString(),
                    'handled_by' => $c->handled_by,
                    'handled_at' => $c->handled_at?->toISOString(),
                    'catatan_penanganan' => $c->catatan_penanganan,
                    'foto_penanganan' => $c->foto_penanganan ? url('storage/' . $c->foto_penanganan) : null,
                    'created_at' => $c->created_at?->toISOString(),
                    'updated_at' => $c->updated_at?->toISOString(),
                    'lokasi' => $c->lokasi ? [
                        'id' => $c->lokasi->id,
                        'kode_lokasi' => $c->lokasi->kode_lokasi,
                        'nama_lokasi' => $c->lokasi->nama_lokasi,
                        'kategori' => $c->lokasi->kategori,
                        'lantai' => $c->lokasi->lantai,
                        'unit' => $c->lokasi->unit ? [
                            'id' => $c->lokasi->unit->id,
                            'nama_unit' => $c->lokasi->unit->nama_unit,
                        ] : null,
                    ] : null,
                    'assignee' => $c->assignee ? [
                        'id' => $c->assignee->id,
                        'name' => $c->assignee->name,
                    ] : null,
                    'handler' => $c->handler ? [
                        'id' => $c->handler->id,
                        'name' => $c->handler->name,
                    ] : null,
                ];
            });

            return $this->successResponse($formatted, 'Complaints retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve complaints: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign complaint to a petugas.
     */
    public function assign(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
                return $this->forbiddenResponse('Only admin/supervisor can assign complaints');
            }

            $complaint = GuestComplaint::find($id);
            if (!$complaint) {
                return $this->notFoundResponse('Complaint not found');
            }

            $validator = Validator::make($request->all(), [
                'assigned_to' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $petugasId = $request->input('assigned_to');
            $petugasUser = User::find($petugasId);

            $complaint->update([
                'assigned_to' => $petugasId,
                'assigned_at' => now(),
                'status' => GuestComplaint::STATUS_IN_PROGRESS,
            ]);

            // Notify assignee using Expo Push
            try {
                if ($petugasUser && $complaint->lokasi) {
                    app(\App\Services\ExpoPushService::class)->sendToUsers(
                        [$petugasUser],
                        'Keluhan Tamu Baru',
                        "{$complaint->lokasi->nama_lokasi}: " . \Illuminate\Support\Str::limit($complaint->deskripsi_keluhan, 80),
                        [
                            'type' => 'guest_complaint',
                            'complaint_id' => $complaint->id,
                            'lokasi_id' => $complaint->lokasi->id,
                        ]
                    );
                }
            } catch (\Exception $ne) {
                // Log and ignore notification failure
            }

            return $this->successResponse($complaint->load(['lokasi', 'assignee']), 'Complaint assigned successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign complaint: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update complaint status.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $complaint = GuestComplaint::find($id);
            if (!$complaint) {
                return $this->notFoundResponse('Complaint not found');
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,in_progress,resolved,rejected',
                'catatan_penanganan' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $status = $request->input('status');
            $data = [
                'status' => $status,
                'catatan_penanganan' => $request->input('catatan_penanganan'),
            ];

            if ($status === 'resolved') {
                $data['handled_by'] = $user->id;
                $data['handled_at'] = now();
            }

            $complaint->update($data);

            return $this->successResponse($complaint->load(['lokasi', 'assignee', 'handler']), 'Complaint status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update complaint status: ' . $e->getMessage(), 500);
        }
    }
}
