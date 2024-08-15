<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\DetailLocation;
use App\Models\FinancialRecord;
use App\Models\TravelPlan;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DestinationController extends Controller
{
    public function index($travelId)
    {
        try {
            if (!$travelId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Travel ID is required.',
                ], 400);
            }

            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();
            
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }


            $destinations = Destination::where('travel_plan_id', $travelPlan->id)->get();
            
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Display all destination',
                'data' => $destinations->load(['financialRecord', 'detailLocation']),
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $travelId)
    {
        try {
            if (!$travelId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Travel ID is required.',
                ], 400);
            }

            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();
            
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'startAt' => 'required|date',
                'endAt' => 'required|date',
                'vehicle' => 'required|string|max:255|in:car,bus,motorcycle,plane,train',
                'note' => 'sometimes|string',
                'financialTransportation' => 'required|numeric',
                'financialLodging' => 'required|numeric',
                'financialConsumption' => 'required|numeric',
                'financialEmergencyFund' => 'required|numeric',
                'financialSouvenir' => 'required|numeric',
                'financialTotal' => 'sometimes|numeric',
                'locationName' => 'required|string|max:255',
                'locationPlaceId' => 'required|string|max:255',
                'locationAddress' => 'required|string',
                'locationLng' => 'required|numeric',
                'locationLat' => 'required|numeric',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $detailLocation = DetailLocation::create([
                'place_id' => $request->locationPlaceId,
                'name' => $request->locationName,
                'address' => $request->locationAddress,
                'lat' => $request->locationLat,
                'lng' => $request->locationLng,
            ]);
    
            $destination = new Destination($request->all());
            $destination->detail_location_id = $detailLocation->id;
            $destination->travel_plan_id = $travelId;
            $destination->save();
    
            $financialRecord = new FinancialRecord([
                'transportation' => $request->financialTransportation,
                'lodging' => $request->financialLodging,
                'consumption' => $request->financialConsumption,
                'emergencyFund' => $request->financialEmergencyFund,
                'souvenir' => $request->financialSouvenir,
                'total' => $request->has('financialTotal') ? $request->financialTotal : $request->financialTransportation + $request->financialLodging + $request->financialConsumption + $request->financialEmergencyFund + $request->financialSouvenir,
                'destination_id' => $destination->id,
            ]);
            $financialRecord->save();

            $startLocation = DetailLocation::findOrFail($travelPlan->start_location_id);
            $placeLocations = [
                (object) [
                    'lat'=> $startLocation->lat,
                    'lng'=> $startLocation->lng,
                ],
            ];
            $newEstimation = 0;
            $travelPlan->destinations->map(function ($destination) use (&$placeLocations, &$newEstimation) {
                $newEstimation += $destination->financialRecord->total;
                array_push($placeLocations, (object) [
                    'lat' => $destination->detailLocation->lat,
                    'lng' => $destination->detailLocation->lng,
                ]);
            });

            $newTotalDistances = $this->calculateTotalDistance($placeLocations);
            $earliestAndLatestDates = $this->getEarliestAndLatestDates($travelPlan->destinations);

            $travelPlan->update([
                'totalDistance' => $newTotalDistances,
                'startAt' => $earliestAndLatestDates["startAt"],
                'endAt' => $earliestAndLatestDates["endAt"],
                'estimation' => $newEstimation,
            ]);
    
            return response()->json([
                'status' => true,
                'statusCode' => 201,
                'message' => 'Destination successfully created',
                'data' => $destination->load(['financialRecord', 'detailLocation']),
            ], 201);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show($travelId, $destinationId)
    {
        try {
            if (!$travelId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Travel ID is required.',
                ], 400);
            }
            
            if (!$destinationId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Destination ID is required.',
                ], 400);
            }
            
            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();

            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }
            
            $destination = Destination::where('id', $destinationId)->where('travel_plan_id', $travelPlan->id)->first();
            if (!$destination) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Destination not found or you do not have access to it.',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Display destination',
                'data' => $destination->load(['financialRecord', 'detailLocation']),
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $travelId, $destinationId)
    {
        try {
            if (!$travelId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Travel ID is required.',
                ], 400);
            }
            
            if (!$destinationId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Destination ID is required.',
                ], 400);
            }

            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();
            
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'startAt' => 'sometimes|date',
                'endAt' => 'sometimes|date',
                'vehicle' => 'sometimes|string|max:255|in:car,bus,motorcycle,plane,train',
                'note' => 'sometimes|string|nullable',
                'financialTransportation' => 'sometimes|numeric',
                'financialLodging' => 'sometimes|numeric',
                'financialConsumption' => 'sometimes|numeric',
                'financialEmergencyFund' => 'sometimes|numeric',
                'financialSouvenir' => 'sometimes|numeric',
                'financialTotal' => 'sometimes|numeric',
                'locationName' => 'sometimes|string|max:255',
                'locationPlaceId' => 'sometimes|string|max:255',
                'locationAddress' => 'sometimes|string',
                'locationLng' => 'sometimes|numeric',
                'locationLat' => 'sometimes|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            
            $destination = Destination::where('id', $destinationId)->where('travel_plan_id', $travelPlan->id)->first();
            if (!$destination) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Destination not found or you do not have access to it.',
                ], 404);
            }

            $detailLocation = DetailLocation::findOrFail($destination->detail_location_id);
            $detailLocationDataUpdate = [
                'place_id' => $request->has('locationPlaceId') ? $request->locationPlaceId : $detailLocation->place_id,
                'name' => $request->has('locationName') ? $request->locationName : $detailLocation->name,
                'address' => $request->has('locationAddress') ? $request->locationAddress : $detailLocation->address,
                'lat' => $request->has('locationLat') ? $request->locationLat : $detailLocation->lat,
                'lng' => $request->has('locationLng') ? $request->locationLng : $detailLocation->lng,
            ];
            $detailLocation->update($detailLocationDataUpdate);

            $destination->update([
                'startAt' => $request->has('startAt') ? $request->startAt : $destination->startAt,
                'endAt' => $request->has('endAt') ? $request->endAt : $destination->endAt,
                'note' => $request->has('note') ? (empty($request->note) ? null : $request->note) : $destination->note,
                'vehicle' => $request->has('vehicle') ? $request->vehicle : $destination->vehicle,
            ]);

            $financialRecord = FinancialRecord::where('destination_id', $destination->id)->firstOrFail();
            $financialRecord->update([
                'transportation' => $request->has('financialTransportation') ? $request->financialTransportation : $financialRecord->transportation,
                'lodging' => $request->has('financialLodging') ? $request->financialLodging : $financialRecord->lodging,
                'consumption' => $request->has('financialConsumption') ? $request->financialConsumption : $financialRecord->consumption,
                'emergencyFund' => $request->has('financialEmergencyFund') ? $request->financialEmergencyFund : $financialRecord->emergencyFund,
                'souvenir' => $request->has('financialSouvenir') ? $request->financialSouvenir : $financialRecord->souvenir,
                'total' => $request->has('financialTotal') ? $request->financialTotal : ($request->has('financialTransportation') ? $request->financialTransportation : $financialRecord->transportation) + ($request->has('financialLodging') ? $request->financialLodging : $financialRecord->lodging) + ($request->has('financialConsumption') ? $request->financialConsumption : $financialRecord->consumption) + ($request->has('financialEmergencyFund') ? $request->financialEmergencyFund : $financialRecord->emergencyFund) + ($request->has('financialSouvenir') ? $request->financialSouvenir : $financialRecord->souvenir),
            ]);

            $startLocation = DetailLocation::findOrFail($travelPlan->start_location_id);
            $placeLocations = [
                (object) [
                    'lat'=> $startLocation->lat,
                    'lng'=> $startLocation->lng,
                ],
            ];
            $newEstimation = 0;
            $travelPlan->destinations->map(function ($destination) use (&$placeLocations, &$newEstimation) {
                $newEstimation += $destination->financialRecord->total;
                array_push($placeLocations, (object) [
                    'lat' => $destination->detailLocation->lat,
                    'lng' => $destination->detailLocation->lng,
                ]);
            });

            $newTotalDistances = $this->calculateTotalDistance($placeLocations);
            $earliestAndLatestDates = $this->getEarliestAndLatestDates($travelPlan->destinations);

            $travelPlan->update([
                'totalDistance' => $newTotalDistances,
                'startAt' => $earliestAndLatestDates["startAt"],
                'endAt' => $earliestAndLatestDates["endAt"],
                'estimation' => $newEstimation,
            ]);

            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Destination successfully updated',
                'data' => $destination->load(['financialRecord', 'detailLocation']),
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($travelId, $destinationId)
    {
        try {
            if (!$travelId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Travel ID is required.',
                ], 400);
            }
            
            if (!$destinationId) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Destination ID is required.',
                ], 400);
            }

            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();
            
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }

            $destination = Destination::where('id', $destinationId)->where('travel_plan_id', $travelPlan->id)->first();
            if (!$destination) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Destination not found or you do not have access to it.',
                ], 404);
            }
            $destination->delete();

            
            $destinations = Destination::where('travel_plan_id', $travelId)->first();

            if ($destinations) {
                $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();
                $startLocation = DetailLocation::findOrFail($travelPlan->start_location_id);
                $placeLocations = [
                    (object) [
                        'lat'=> $startLocation->lat,
                        'lng'=> $startLocation->lng,
                    ],
                ];
                $newEstimation = 0;
                $travelPlan->destinations->map(function ($destination) use (&$placeLocations, &$newEstimation) {
                    $newEstimation += $destination->financialRecord->total;
                    array_push($placeLocations, (object) [
                        'lat' => $destination->detailLocation->lat,
                        'lng' => $destination->detailLocation->lng,
                    ]);
                });

                $newTotalDistances = $this->calculateTotalDistance($placeLocations);
                $earliestAndLatestDates = $this->getEarliestAndLatestDates($travelPlan->destinations);

                $travelPlan->update([
                    'totalDistance' => $newTotalDistances,
                    'startAt' => $earliestAndLatestDates["startAt"],
                    'endAt' => $earliestAndLatestDates["endAt"],
                    'estimation' => $newEstimation,
                ]);
            } else {
                $travelPlan = TravelPlan::where('id', $travelId)->where('user_id', $user->id)->first();
                $travelPlan->update([
                    'totalDistance' => 0,
                    'startAt' => null,
                    'endAt' => null,
                    'estimation' => 0,
                ]);
            }
            
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Destination successfully deleted',
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function calculateHaversineDistance($coords1, $coords2)
    {
        $toRad = function ($value) {
            return $value * pi() / 180;
        };

        $R = 6371;
        $lat1 = $toRad($coords1->lat);
        $lat2 = $toRad($coords2->lat);
        $deltaLat = $toRad($coords2->lat - $coords1->lat);
        $deltaLng = $toRad($coords2->lng - $coords1->lng);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $R * $c;
        return $distance;
    }

    public function calculateTotalDistance($placeLocations)
    {
        if (count($placeLocations) < 2) {
            return 0;
        }

        $totalKm = 0;

        for ($i = 0; $i < count($placeLocations) - 1; $i++) {
            $originCoords = $placeLocations[$i];
            $destinationCoords = $placeLocations[$i + 1];

            $distance = $this->calculateHaversineDistance($originCoords, $destinationCoords);
            $totalKm += $distance;
        }

        return $totalKm;
    }

    public function getEarliestAndLatestDates($destinations)
    {
        $earliest = $destinations[0]->startAt;
        $latest = $destinations[0]->endAt;

        foreach ($destinations as $destination) {
            if ($destination->startAt < $earliest) {
                $earliest = $destination->startAt;
            }

            if ($destination->endAt > $latest) {
                $latest = $destination->endAt;
            }
        }

        return [
            'startAt' => $earliest,
            'endAt' => $latest,
        ];
    }
}
