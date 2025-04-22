<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailFinding extends Model
{
    protected $fillable = [
        'snapshot_id',
        'domain',
        'data_type',
        'source_name',
        'value',
        'match',
        'match_type',
        'confidence',
    ];
}
