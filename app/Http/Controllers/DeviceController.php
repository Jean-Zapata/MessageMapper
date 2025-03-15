<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::all();
        return view('device.index', compact('devices'));
    }

    public function show($id)
    {
        $device = Device::findOrFail($id);
        return view('device.show', compact('device'));
    }

    public function create()
    {
        return view('device.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Subir la imagen si existe
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/device_images');
        }

        // Crear el nuevo dispositivo
        $device = Device::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'status' => 'active',
            'image_url' => $imagePath,
        ]);

        return redirect()->route('device.index')->with('success', 'Dispositivo añadido.');
    }

    public function edit($id)
    {
        $device = Device::findOrFail($id);
        return view('device.edit', compact('device'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $device = Device::findOrFail($id);

        // Subir la imagen si existe
        if ($request->hasFile('image')) {
            if ($device->image_url) {
                Storage::delete($device->image_url);
            }
            $device->image_url = $request->file('image')->store('public/device_images');
        }

        $device->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->route('device.index')->with('success', 'Dispositivo actualizado.');
    }

    public function toggleStatus($id)
    {
        $device = Device::findOrFail($id);
        $device->status = $device->status === 'active' ? 'inactive' : 'active';
        $device->save();

        return redirect()->route('device.show', $device->id)->with('success', 'Estado del dispositivo actualizado.');
    }

    public function restart($id)
    {
        // Lógica de reinicio simulada
        return redirect()->route('device.show', $id)->with('success', 'Dispositivo reiniciado.');
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);

        // Eliminar la imagen si existe
        if ($device->image_url) {
            Storage::delete($device->image_url);
        }

        $device->delete();

        return redirect()->route('device.index')->with('success', 'Dispositivo eliminado.');
    }
}
