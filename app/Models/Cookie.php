<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cookie extends Model
{
    protected $fillable = [
        'snapshot_id',
        'name',
        'value',
        'domain',
        'path',
        'expires',
        'secure',
        'http_only',
        'same_site',
    ];

    /**
     * Get the snapshot that owns the cookie.
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }
}
