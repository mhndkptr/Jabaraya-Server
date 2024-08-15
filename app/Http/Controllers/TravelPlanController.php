<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\DetailLocation;
use App\Models\TravelPlan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TravelPlanController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Display all travel plan',
                'data' => $user->travelPlans->load([
                    'startLocation',
                    'destinations.financialRecord',
                    'destinations.detailLocation'
                ]),
            ], 200);
            
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'estimation' => 'required|numeric',
                'totalDistance' => 'required|numeric',
                'startLocationName' => 'required|string|max:255',
                'startLocationPlaceId' => 'required|string|max:255',
                'startLocationAddress' => 'required|string',
                'startLocationLng' => 'required|numeric',
                'startLocationLat' => 'required|numeric',
                'startAt' => 'required|date',
                'endAt' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            $detailLocation = DetailLocation::create([
                'place_id' => $request->startLocationPlaceId,
                'name' => $request->startLocationName,
                'address' => $request->startLocationAddress,
                'lat' => $request->startLocationLat,
                'lng' => $request->startLocationLng,
            ]);

            $travelPlan = new TravelPlan($request->all());
            $travelPlan->user_id = $user->id;
            $travelPlan->start_location_id = $detailLocation->id;
            
            $travelPlan->save();

            return response()->json([
                'status' => true,
                'statusCode' => 201,
                'message' => 'Travel plan successfully created',
                'data' => $travelPlan,
            ], 201);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $id)->where('user_id', $user->id)->first();
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }
            
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Display travel plan',
                'data' => $travelPlan->load([
                    'startLocation',
                    'destinations.financialRecord',
                    'destinations.detailLocation'
                ]),
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $id)->where('user_id', $user->id)->first();
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'estimation' => 'required|numeric',
                'totalDistance' => 'required|numeric',
                'startLocationName' => 'required|string|max:255',
                'startLocationPlaceId' => 'required|string|max:255',
                'startLocationAddress' => 'required|string',
                'startLocationLng' => 'required|numeric',
                'startLocationLat' => 'required|numeric',
                'startAt' => 'required|date',
                'endAt' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $detailLocation = DetailLocation::findOrFail($travelPlan->start_location_id);
            $detailLocation->update([
                'place_id' => $request->startLocationPlaceId,
                'name' => $request->startLocationName,
                'address' => $request->startLocationAddress,
                'lat' => $request->startLocationLat,
                'lng' => $request->startLocationLng,
            ]);
            
            $travelPlan->update([
                'name' => $request->name,
                'estimation' => $request->estimation,
                'totalDistance' => $request->totalDistance,
                'startAt' => $request->startAt,
                'endAt' => $request->endAt,
                'user_id' => $user->id,
                'start_location_id' => $detailLocation->id,
            ]);

            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Travel plan successfully updated',
                'data' => $travelPlan,
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $id)->where('user_id', $user->id)->first();
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }
            $travelPlan->delete();

            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Travel plan successfully deleted',
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateStartLocation(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $travelPlan = TravelPlan::where('id', $id)->where('user_id', $user->id)->first();
            if (!$travelPlan) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Travel plan not found or you do not have access to it.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'startLocationName' => 'required|string|max:255',
                'startLocationPlaceId' => 'required|string|max:255',
                'startLocationAddress' => 'required|string',
                'startLocationLng' => 'required|numeric',
                'startLocationLat' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            } 

            $detailLocation = DetailLocation::findOrFail($travelPlan->start_location_id);
            $detailLocation->update([
                'place_id' => $request->startLocationPlaceId,
                'name' => $request->startLocationName,
                'address' => $request->startLocationAddress,
                'lat' => $request->startLocationLat,
                'lng' => $request->startLocationLng,
            ]);

            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Travel plan start location successfully updated',
                'data' => $travelPlan,
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function showSingle()
    {
        try {
            $user = Auth::user();
            $travelPlan = TravelPlan::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
            if($travelPlan) {
                return response()->json([
                    'status' => true,
                    'statusCode' => 200,
                    'message' => 'Display latest travel plan',
                    'data' => $travelPlan->load([
                        'startLocation',
                        'destinations.financialRecord',
                        'destinations.detailLocation'
                    ]),
                ], 200);
            } else {
                return response()->json([
                    'status' => true,
                    'statusCode' => 200,
                    'message' => 'Display latest travel plan',
                    'data' => [],
                ], 200);
            }
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
