<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class RecomendationPlaceController extends Controller
{
    public function getRecomendationPlace(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
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
    
            $types = ['lodging', 'restaurant'];
            $allPlaces = [];
    
            foreach ($types as $type) {
                $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                    'radius'=> 1500,
                    'location' => $request->locationLat.','.$request->locationLng,
                    'key' => env('GOOGLE_MAPS_API_KEY'),
                    'type' => $type,
                ]);
    
                $places = $response->json()['results'];
                $allPlaces[$type] = array_slice($places, 0, 3);
            }

            $limitedPlaces = array_merge(
                array_slice($allPlaces['lodging'], 0, 1),
                array_slice($allPlaces['restaurant'], 0, 2)
            );

            $remainingPlaces = array_slice(array_merge($allPlaces['lodging'], $allPlaces['restaurant']), 0, 3 - count($limitedPlaces));
            $limitedPlaces = array_merge($limitedPlaces, $remainingPlaces);
    
            $filteredPlaces = array_map(function ($place) {
                $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                    'place_id' => $place['place_id'],
                    'key' => env('GOOGLE_MAPS_API_KEY'),
                ]);
    
                $details = $detailsResponse->json()['result'];
    
                return [
                    'place_id' => $place['place_id'],
                    'name' =>  $place['name'],
                    'lat' => $place['geometry']['location']['lat'],
                    'lng' => $place['geometry']['location']['lng'],
                    'rating' => $place['rating'] ?? null,
                    'address' => $details['formatted_address'] ?? null,
                    'phone' => $details['formatted_phone_number'] ?? null,
                    'website' => $details['website'] ?? null,
                    'opening_hours' => $details['opening_hours']['weekday_text'] ?? null,
                    'photos' => isset($details['photos']) 
                        ? array_map(fn($photo) => 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=' . $photo['photo_reference'] . '&key=' . env('GOOGLE_MAPS_API_KEY'), $details['photos'])
                        : null,
                    'thumbnail' => isset($place['photos'][0]['photo_reference']) 
                                    ? 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=200&photoreference=' . $place['photos'][0]['photo_reference'] . '&key=' . env('GOOGLE_MAPS_API_KEY')
                                    : null,
                    'maps_link' => 'https://www.google.com/maps/place/?q=place_id:' . $place['place_id'],
                ];
            }, $limitedPlaces);
    
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Display recommendation place',
                'data' => $filteredPlaces,
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
