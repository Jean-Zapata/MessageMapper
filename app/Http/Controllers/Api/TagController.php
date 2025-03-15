<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    // Devuelve todas las etiquetas
    public function index()
    {
        $tags = Tag::all();
        return response()->json($tags);
    }

    // Crea una nueva etiqueta
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag = Tag::create($request->only('name'));

        return response()->json($tag, 201);
    }

    // Muestra una etiqueta específica
    public function show(Tag $tag)
    {
        return response()->json($tag);
    }

    // Actualiza una etiqueta existente
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag->update($request->only('name'));

        return response()->json($tag);
    }

    // Elimina una etiqueta
    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->json(null, 204);
    }
}
