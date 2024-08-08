<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = ['startAt', 'endAt', 'note', 'vehicle', 'travel_plan_id', 'detail_location_id'];

    public function travelPlan()
    {
        return $this->belongsTo(TravelPlan::class);
    }

    public function financialRecord()
    {
        return $this->hasOne(FinancialRecord::class);
    }

    public function detailLocation()
    {
        return $this->belongsTo(DetailLocation::class);
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
