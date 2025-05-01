<?php
// app/Models/Part.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'price',
        'original_price',
        'stock',
        'rating',
        'review_count',
        'description',
        'features',
        'specifications',
        'images',
        'compatibility',
        'image',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
        'specifications' => 'array',
        'images' => 'array',
        'compatibility' => 'array',
        'price' => 'float',
        'original_price' => 'float',
        'stock' => 'integer',
        'rating' => 'float',
        'review_count' => 'integer',
    ];
}
