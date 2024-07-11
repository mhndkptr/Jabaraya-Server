<?php

namespace App\Http\Controllers;

use App\Models\article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
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
        $request->validate([
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'content' => 'required|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $article = Article::create($data);

        return response()->json($article, 201);
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
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'content' => 'sometimes|string',
        ]);
        $data = $request->all();
        if ($request->hasFile('thumbnail')) {
            if ($article->thumbnail) {
                Storage::disk('public')->delete($article->thumbnail);
            }
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
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
            Storage::disk('public')->delete($article->thumbnail);
        }

        $article->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
