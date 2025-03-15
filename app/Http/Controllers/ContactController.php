<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $tagFilter = $request->input('tag_filter');

        if ($tagFilter) {
            // Si se selecciona un filtro de etiqueta, se filtran los contactos
            $contacts = Contact::whereHas('tags', function ($query) use ($tagFilter) {
                $query->where('tag_id', $tagFilter);
            })->get();
        } else {
            // Si no hay filtro, se traen todos los contactos
            $contacts = Contact::all();
        }

        $tags = Tag::all();
        return view('contacts.index', compact('contacts', 'tags'));
    }

    public function create()
    {
        // Obtener todas las etiquetas para mostrarlas en el formulario
        $tags = Tag::all();
        return view('contacts.create', compact('tags'));
    }

    public function store(Request $request)
    {
        // Crear el nuevo contacto
        $contact = Contact::create($request->only('name', 'phone_number', 'email'));

        // Asociar las etiquetas seleccionadas al contacto
        $contact->tags()->sync($request->tags);

        return redirect()->route('contacts.index')->with('success', 'Contacto creado exitosamente');
    }

    public function edit(Contact $contact)
    {
        // Obtener las etiquetas y pasar el contacto a editar
        $tags = Tag::all();
        return view('contacts.edit', compact('contact', 'tags'));
    }

    public function update(Request $request, Contact $contact)
    {
        // Actualizar los datos del contacto
        $contact->update($request->only('name', 'phone_number', 'email'));

        // Actualizar las etiquetas seleccionadas
        $contact->tags()->sync($request->tags);

        return redirect()->route('contacts.index')->with('success', 'Contacto actualizado exitosamente');
    }

    public function destroy(Contact $contact)
    {
        // Eliminar el contacto
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contacto eliminado exitosamente');
    }
}
