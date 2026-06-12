<?php

namespace App\Traits;

use App\Models\ReportIdempotencyKey;
use Illuminate\Http\Request;

/**
 * Dukungan Idempotency-Key untuk endpoint submit laporan.
 *
 * Dipakai bersama ApiResponse di controller store. Lihat ReportIdempotencyKey.
 */
trait HandlesIdempotency
{
    /**
     * Jika request membawa Idempotency-Key yang sudah pernah diproses oleh user
     * ini, kembalikan report_id yang dulu dibuat; jika tidak, null.
     */
    protected function idempotentHit(Request $request, int $userId): ?int
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return null;
        }

        return ReportIdempotencyKey::where('idempotency_key', $key)
            ->where('user_id', $userId)
            ->value('report_id');
    }

    /** Catat key→report_id agar retry berikutnya tidak membuat duplikat. */
    protected function rememberIdempotency(
        Request $request,
        int $userId,
        string $type,
        int $reportId,
    ): void {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return;
        }

        ReportIdempotencyKey::firstOrCreate(
            ['idempotency_key' => $key],
            ['user_id' => $userId, 'report_type' => $type, 'report_id' => $reportId],
        );
    }
}
