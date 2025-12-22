<?php

namespace App\Observers;

use App\Models\GuestComplaint;
use App\Services\WatZapService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GuestComplaintObserver
{
    /**
     * Handle the GuestComplaint "updated" event.
     */
    public function updated(GuestComplaint $complaint): void
    {
        // Check if status changed
        if ($complaint->isDirty('status')) {
            $oldStatus = $complaint->getOriginal('status');
            $newStatus = $complaint->status;

            // Set handled_by and handled_at if status changed from pending
            if ($oldStatus === GuestComplaint::STATUS_PENDING && $newStatus !== GuestComplaint::STATUS_PENDING) {
                $complaint->updateQuietly([
                    'handled_by' => Auth::id(),
                    'handled_at' => now(),
                ]);
            }

            // Send notification to guest if they have phone number
            if ($complaint->telepon_pelapor) {
                $this->sendStatusUpdateToGuest($complaint);
            }
        }
    }

    /**
     * Send status update notification to guest
     */
    protected function sendStatusUpdateToGuest(GuestComplaint $complaint): void
    {
        try {
            $watzapService = new WatZapService();

            if (!$watzapService->isConfigured()) {
                Log::warning('WatZap not configured, skipping status update notification');
                return;
            }

            $result = $watzapService->sendComplaintStatusUpdate($complaint);

            Log::info('Complaint status update notification sent', [
                'complaint_id' => $complaint->id,
                'status' => $complaint->status,
                'success' => $result['success'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send status update notification: ' . $e->getMessage(), [
                'complaint_id' => $complaint->id,
            ]);
        }
    }
}
