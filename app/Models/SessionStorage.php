<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionStorage extends Model
{
    protected $fillable = [
        'snapshot_id',
        'key',
        'value',
        'domain',
    ];

    /**
     * Get the snapshot that owns the session storage item.
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }
}
