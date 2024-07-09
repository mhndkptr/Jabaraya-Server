<?php

namespace App\Http\Controllers;

use App\Models\category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $category = category::get();
        return response()->json($category);
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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category = Category::create($validatedData);

        return response()->json($category, 201)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Display the specified resource.
     */
    public function show(category $category)
    {
        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, category $category)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category->update($validatedData);

        return response()->json($category, 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
