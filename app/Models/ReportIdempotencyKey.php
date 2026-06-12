<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catatan idempotency submit laporan: key unik → report yang dibuat.
 * Lihat [[migration]] create_report_idempotency_keys_table.
 */
class ReportIdempotencyKey extends Model
{
    protected $fillable = [
        'idempotency_key',
        'user_id',
        'report_type',
        'report_id',
    ];
}
