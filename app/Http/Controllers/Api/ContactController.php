<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // Listado de contactos, opcionalmente filtrados por etiqueta
    public function index(Request $request)
    {
        $tagFilter = $request->input('tag_filter');

        if ($tagFilter) {
            // Filtrar contactos por etiqueta usando la relación
            $contacts = Contact::whereHas('tags', function ($query) use ($tagFilter) {
                $query->where('tag_id', $tagFilter);
            })->get();
        } else {
            $contacts = Contact::all();
        }

        return response()->json($contacts);
    }

    // Crear un contacto
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string',
            'email'        => 'nullable|email',
        ]);

        $contact = Contact::create($validated);

        // Si se envían etiquetas, asociarlas
        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        return response()->json($contact, 201);
    }

    // Mostrar un contacto específico
    public function show(Contact $contact)
    {
        return response()->json($contact);
    }

    // Actualizar un contacto
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string',
            'email'        => 'nullable|email',
        ]);

        $contact->update($validated);

        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        return response()->json($contact);
    }

    // Eliminar un contacto
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
