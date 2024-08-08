<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialRecord extends Model
{
    use HasFactory;

    protected $fillable = ['transportation', 'lodging', 'consumption', 'emergencyFund', 'souvenir', 'total', 'destination_id'];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}
