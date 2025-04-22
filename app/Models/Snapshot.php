<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Snapshot extends Model
{
    protected $fillable = [
        'user_id',
        'domain_filter',
        'summary',
        'data', // Full snapshot data as JSON
    ];

    // Relationship with email findings
    public function emailFindings(): HasMany
    {
        return $this->hasMany(EmailFinding::class);
    }
}
