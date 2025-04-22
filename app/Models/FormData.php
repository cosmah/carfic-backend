<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormData extends Model
{
    protected $fillable = [
        'snapshot_id',
        'form_name',
        'field_name',
        'field_value',
    ];

    /**
     * Get the snapshot that owns the form data.
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }
}
