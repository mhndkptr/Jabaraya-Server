<?php

namespace App\Http\Controllers;

use App\Models\article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseService;

class ArticleController extends Controller
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
        return Article::all();
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
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'content' => 'required|string',
            ]);
    
            $data = $request->all();
    
            if ($request->hasFile('thumbnail')) {
                $fileUrl = $this->firebaseService->uploadFile($request->file('thumbnail'), 'articleThumbnails');
                $data['thumbnail'] = $fileUrl;
            }
    
            $article = Article::create($data);
    
            return response()->json($article, 201);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
        
    }
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $url = $this->firebaseService->uploadFile($request->file('upload'), 'articleImages');

            return response()->json(['uploaded' => true, 'url' => $url]);
        } else {
            return response()->json(['uploaded' => false, 'error' => ['message' => 'File not uploaded']], 400);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(article $article)
    {
        return response()->json($article);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(article $article)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, article $article)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'content' => 'sometimes|string',
        ]);
        $data = $request->all();
        if ($request->hasFile('thumbnail')) {
            if ($article->thumbnail) {
                $this->firebaseService->deleteFile($article->thumbnail);
            }
            $fileUrl = $this->firebaseService->uploadFile($request->file('thumbnail'), 'articleThumbnails');
            $data['thumbnail'] = $fileUrl;
        }
        $article->update($data);
        return response()->json($article, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(article $article)
    {
        if ($article->thumbnail) {
            $this->firebaseService->deleteFile($article->thumbnail);
        }

        $article->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
