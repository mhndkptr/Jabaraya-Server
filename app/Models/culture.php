<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class culture extends Model
{
    use HasFactory;
    protected $table = 'cultures';
    protected $fillable = [
        'title',
        'thumbnail',
        'content',
        'category_id'
    ];
}
