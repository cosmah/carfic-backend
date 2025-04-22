<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalStorage extends Model
{
    protected $fillable = [
        'snapshot_id',
        'key',
        'value',
        'domain',
    ];

    /**
     * Get the snapshot that owns the local storage item.
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }
}
