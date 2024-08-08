<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\DetailLocation;
use App\Models\FinancialRecord;
use App\Models\TravelPlan;
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
                'vehicle' => 'required|string|max:255',
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
                'startAt' => 'required|date',
                'endAt' => 'required|date',
                'vehicle' => 'required|string|max:255',
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

            
            $destination = Destination::where('id', $destinationId)->where('travel_plan_id', $travelPlan->id)->first();
            if (!$destination) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 404,
                    'message' => 'Destination not found or you do not have access to it.',
                ], 404);
            }

            $detailLocation = DetailLocation::findOrFail($destination->detail_location_id);
            $detailLocation->update([
                'place_id' => $request->locationPlaceId,
                'name' => $request->locationName,
                'address' => $request->locationAddress,
                'lat' => $request->locationLat,
                'lng' => $request->locationLng,
            ]);

            $destination->update($request->all());

            $financialRecord = FinancialRecord::where('destination_id', $destination->id)->firstOrFail();
            $financialRecord->update([
                'transportation' => $request->financialTransportation,
                'lodging' => $request->financialLodging,
                'consumption' => $request->financialConsumption,
                'emergencyFund' => $request->financialEmergencyFund,
                'souvenir' => $request->financialSouvenir,
                'total' => $request->has('financialTotal') ? $request->financialTotal : $request->financialTransportation + $request->financialLodging + $request->financialConsumption + $request->financialEmergencyFund + $request->financialSouvenir,
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
}
