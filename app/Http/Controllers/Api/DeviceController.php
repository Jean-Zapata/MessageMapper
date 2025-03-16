<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeviceController extends Controller
{
    // Listado de dispositivos
    public function index()
    {
        return response()->json(Device::all());
    }

    // Mostrar un dispositivo en particular
    public function show($id)
    {
        return response()->json(Device::findOrFail($id));
    }

    // Crear un nuevo dispositivo
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('public/device_images');
                $imagePath = str_replace('public/', 'storage/', $imagePath);
            }

            $device = Device::create([
                'name'         => $request->name,
                'phone_number' => $request->phone_number,
                'status'       => 'active',
                'image_url'    => $imagePath,
            ]);

            return response()->json($device, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo crear el dispositivo'], 500);
        }
    }

    // Actualizar un dispositivo existente
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $device = Device::findOrFail($id);

        try {
            // Manejo de la imagen
            if ($request->hasFile('image')) {
                if ($device->image_url) {
                    Storage::delete(str_replace('storage/', 'public/', $device->image_url));
                }
                $imagePath = $request->file('image')->store('public/device_images');
                $device->image_url = str_replace('public/', 'storage/', $imagePath);
            }

            $device->update([
                'name'         => $request->name,
                'phone_number' => $request->phone_number,
            ]);

            return response()->json($device);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar el dispositivo'], 500);
        }
    }

    // Cambiar el estado del dispositivo
    public function toggleStatus($id)
    {
        $device = Device::findOrFail($id);
        $device->status = $device->status === 'active' ? 'inactive' : 'active';
        $device->save();

        return response()->json([
            'message' => 'Estado del dispositivo actualizado.',
            'device'  => $device
        ]);
    }

    // Reiniciar dispositivo (simulado)
    public function restart($id)
    {
        return response()->json(['message' => 'Dispositivo reiniciado.']);
    }

    // Eliminar un dispositivo
    public function destroy($id)
    {
        $device = Device::findOrFail($id);

        try {
            if ($device->image_url) {
                Storage::delete(str_replace('storage/', 'public/', $device->image_url));
            }

            $device->delete();

            return response()->json(['message' => 'Dispositivo eliminado.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el dispositivo'], 500);
        }
    }
}
