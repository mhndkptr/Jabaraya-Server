<?php

namespace App\Http\Controllers;

use App\Models\news;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseService;

class NewsController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return News::all();
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
            'link' => 'required|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            $fileUrl = $this->firebaseService->uploadFile($request->file('thumbnail'), 'newsThumbnails');
            $data['thumbnail'] = $fileUrl;
        }

        $news = News::create($data);

        return response()->json($news, 201);
    }
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $url = $this->firebaseService->uploadFile($request->file('upload'), 'newsImages');
            return response()->json(['uploaded' => true, 'url' => $url]);
        } else {
            return response()->json(['uploaded' => false, 'error' => ['message' => 'File not uploaded']], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(news $news)
    {
        return response()->json($news);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(news $news)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, news $news)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'content' => 'sometimes|string',
            'link' => 'sometimes|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            if ($news->thumbnail) {
                $this->firebaseService->deleteFile($news->thumbnail);
            }
            $fileUrl = $this->firebaseService->uploadFile($request->file('thumbnail'), 'newsThumbnails');
            $data['thumbnail'] = $fileUrl;
        }

        $news->update($data);

        return response()->json($news, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(news $news)
    {
        if ($news->thumbnail) {
            $this->firebaseService->deleteFile($news->thumbnail);
        }

        $news->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
