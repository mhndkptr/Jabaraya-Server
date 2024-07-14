<?php

namespace App\Http\Controllers;

use App\Models\culture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CultureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Culture::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'content' => 'required|string',
            'category_id' => 'required|exists:categorys,id'
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $culture = Culture::create($data);

        return response()->json($culture, 201);
    }
    public function uploadImage(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('upload')) {
            $path = $request->file('upload')->store('images', 'public');
            $url = Storage::url($path);

            return response()->json(['url' => $url], 200);
        }
        return response()->json(['message' => 'No image uploaded'], 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(culture $culture)
    {
        return response()->json($culture);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(culture $culture)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, culture $culture)
    {

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'content' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categorys,id'
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            // Delete the old thumbnail if it exists
            if ($culture->thumbnail) {
                Storage::disk('public')->delete($culture->thumbnail);
            }
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $culture->update($data);

        return response()->json($culture, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(culture $culture)
    {
        if ($culture->thumbnail) {
            Storage::disk('public')->delete($culture->thumbnail);
        }

        $culture->delete();

        return response()->json(null, 204);
    }
}
