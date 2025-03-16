<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // Listado de contactos, opcionalmente filtrados por etiqueta y cargando las etiquetas asociadas
    public function index(Request $request)
    {
        $tagFilter = $request->input('tag_filter');

        if ($tagFilter) {
            $contacts = Contact::with('tags')
                ->whereHas('tags', function ($query) use ($tagFilter) {
                    $query->where('tag_id', $tagFilter);
                })->get();
        } else {
            $contacts = Contact::with('tags')->get();
        }

        return response()->json($contacts);
    }

    // Crear un contacto sin email y asociando etiquetas (si se envían)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string',
        ]);

        // Si el número no comienza con "+51", se agrega el prefijo
        if (strpos($validated['phone_number'], '+51') !== 0) {
            $validated['phone_number'] = '+51' . $validated['phone_number'];
        }

        $contact = Contact::create($validated);

        // Asociar etiquetas, si se envían en la petición
        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        // Cargamos las etiquetas para devolver el contacto completo
        $contact->load('tags');

        return response()->json($contact, 201);
    }

    // Mostrar un contacto específico (con sus etiquetas)
    public function show(Contact $contact)
    {
        $contact->load('tags');
        return response()->json($contact);
    }

    // Actualizar un contacto sin email y asociando etiquetas (si se envían)
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string',
        ]);

        if (strpos($validated['phone_number'], '+51') !== 0) {
            $validated['phone_number'] = '+51' . $validated['phone_number'];
        }

        $contact->update($validated);

        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        $contact->load('tags');

        return response()->json($contact);
    }

    // Eliminar un contacto
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json(null, 204);
    }
}
