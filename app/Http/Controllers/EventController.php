<?php

namespace App\Http\Controllers;

use App\Models\event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Event::all();
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
            'name' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'link' => 'nullable|string',
            'category_id' => 'required|exists:categorys,id'
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $event = Event::create($data);

        return response()->json($event, 201);
    }
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $filePath = storage_path('app/public/images'); // Change to storage path
            $file->move($filePath, $filename);

            $url = asset('storage/images/' . $filename); // Adjust URL for storage
            return response()->json(['uploaded' => true, 'url' => $url]);
        } else {
            return response()->json(['uploaded' => false, 'error' => ['message' => 'File not uploaded']], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(event $event)
    {
        return response()->json($event);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, event $event)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'link' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categorys,id'
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            // Delete the old thumbnail if it exists
            if ($event->thumbnail) {
                Storage::disk('public')->delete($event->thumbnail);
            }
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $event->update($data);

        return response()->json($event, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(event $event)
    {
        if ($event->thumbnail) {
            Storage::disk('public')->delete($event->thumbnail);
        }

        $event->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
