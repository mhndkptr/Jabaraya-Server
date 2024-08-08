<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelPlan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'estimation', 'totalDistance', 'startAt', 'endAt', 'start_location_id'];

    public function startLocation()
    {
        return $this->belongsTo(DetailLocation::class, 'start_location_id');
    }

    public function destinations()
    {
        return $this->hasMany(Destination::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setStartAtAttribute($value)
    {
        $this->attributes['startAt'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setEndAtAttribute($value)
    {
        $this->attributes['endAt'] = Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
