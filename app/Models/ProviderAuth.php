<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderAuth extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'provider_id', 
        'provider_name', 
        'provider_token', 
        'provider_refresh_token',
    ];

    // Relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
