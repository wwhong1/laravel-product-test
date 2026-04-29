<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category_id',
        'description',
        'price',
        'stock',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'price'   => 'float',
        'stock'   => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
