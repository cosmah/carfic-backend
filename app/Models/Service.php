<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'image_path', 'services_list'];


    protected $casts = [
        'services_list' => 'array', // Automatically cast JSON to array and vice versa
    ];
}
