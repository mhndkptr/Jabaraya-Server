<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailLocation extends Model
{
    use HasFactory;

    protected $fillable = ['place_id', 'name', 'lat', 'lng', 'address'];

    public function travelPlans()
    {
        return $this->hasOne(TravelPlan::class, 'start_location_id');
    }

    public function destination()
    {
        return $this->hasOne(Destination::class);
    }
}
