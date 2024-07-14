<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class event extends Model
{
    use HasFactory;
    protected $table = 'events';
    protected $fillable = [
        'name',
        'thumbnail',
        'start_date',
        'end_date',
        'location',
        'content',
        'link',
        'category_id'
    ];
}
