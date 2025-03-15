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
        $devices = Device::all();
        return response()->json($devices);
    }

    // Mostrar un dispositivo en particular
    public function show($id)
    {
        $device = Device::findOrFail($id);
        return response()->json($device);
    }

    // Crear un nuevo dispositivo
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/device_images');
        }

        $device = Device::create([
            'name'         => $request->name,
            'phone_number' => $request->phone_number,
            'status'       => 'active',
            'image_url'    => $imagePath,
        ]);

        return response()->json($device, 201);
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

        // Subir imagen si existe y eliminar la anterior
        if ($request->hasFile('image')) {
            if ($device->image_url) {
                Storage::delete($device->image_url);
            }
            $device->image_url = $request->file('image')->store('public/device_images');
        }

        $device->update([
            'name'         => $request->name,
            'phone_number' => $request->phone_number,
        ]);

        return response()->json($device);
    }

    // Cambiar el estado (toggle) del dispositivo
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

    // Reiniciar dispositivo (acción simulada)
    public function restart($id)
    {
        // Lógica de reinicio simulada
        return response()->json([
            'message' => 'Dispositivo reiniciado.'
        ]);
    }

    // Eliminar un dispositivo
    public function destroy($id)
    {
        $device = Device::findOrFail($id);

        if ($device->image_url) {
            Storage::delete($device->image_url);
        }

        $device->delete();

        return response()->json([
            'message' => 'Dispositivo eliminado.'
        ]);
    }
}
