<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\Tag;
use App\Models\SentMessage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::with('contacts', 'device')->get();
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $devices = Device::where('status', 'active')->get();
        $tags = Tag::all();
        return view('campaigns.create', compact('devices', 'tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'device_id' => 'required|exists:devices,id',
            'tags' => 'required|array',
            'scheduled_time' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $campaign = Campaign::create([
            'name' => $request->name,
            'message' => $request->message,
            'device_id' => $request->device_id,
            'status' => 'scheduled',
            'scheduled_time' => Carbon::parse($request->scheduled_time),
        ]);

        $contacts = Contact::whereHas('tags', function ($query) use ($request) {
            $query->whereIn('tags.id', $request->tags);
        })->get();

        $campaign->contacts()->sync($contacts);

        return redirect()->route('campaigns.index')->with('success', 'Campaña creada exitosamente.');
    }

    public function show($id)
    {
        $campaign = Campaign::with('contacts', 'device')->findOrFail($id);
        return view('campaigns.show', compact('campaign'));
    }

    public function edit($id)
    {
        $campaign = Campaign::findOrFail($id);
        $devices = Device::where('status', 'active')->get();
        $tags = Tag::all();

        return view('campaigns.edit', compact('campaign', 'devices', 'tags'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'device_id' => 'required|exists:devices,id',
            'tags' => 'required|array',
            'scheduled_time' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $campaign = Campaign::findOrFail($id);
        $campaign->update([
            'name' => $request->name,
            'message' => $request->message,
            'device_id' => $request->device_id,
            'scheduled_time' => Carbon::parse($request->scheduled_time),
        ]);

        $contacts = Contact::whereHas('tags', function ($query) use ($request) {
            $query->whereIn('tags.id', $request->tags);
        })->get();

        $campaign->contacts()->sync($contacts);

        return redirect()->route('campaigns.index')->with('success', 'Campaña actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaña eliminada exitosamente.');
    }

    public function sendMessage(Request $request, $campaignId)
    {
        $request->validate([
            'custom_message' => 'nullable|string',
        ]);

        $campaign = Campaign::with('contacts', 'device')->findOrFail($campaignId);

        if ($campaign->device->status !== 'active') {
            return redirect()->route('campaigns.index')->with('error', 'El dispositivo asociado no está activo.');
        }

        $message = $request->custom_message ?? $campaign->message;

        if ($campaign->contacts->isEmpty()) {
            return redirect()->route('campaigns.index')->with('error', 'No hay contactos asociados a la campaña.');
        }

        $apiToken = env('QR_API_TOKEN');
        $apiUrl = rtrim(env('QR_API_URL'), '/') . '/api/message/send-text';

        if (empty($apiUrl) || empty($apiToken)) {
            Log::error('La URL o el token de la API no están configurados correctamente.');
            return redirect()->route('campaigns.index')->with('error', 'Configuración de API incorrecta.');
        }

        foreach ($campaign->contacts as $contact) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ])->post($apiUrl, [
                    'number' => $contact->phone_number,
                    'message' => $message,
                ]);

                $responseData = $response->json();

                SentMessage::create([
                    'contact_id' => $contact->id,
                    'campaign_id' => $campaign->id,
                    'status' => $response->successful() ? 'success' : 'failed',
                    'sent_at' => Carbon::now(),
                ]);

                if (!$response->successful()) {
                    Log::error("Error enviando mensaje a {$contact->phone_number}: " . json_encode($responseData));
                }
            } catch (\Exception $e) {
                Log::error("Error enviando mensaje a {$contact->phone_number}: " . $e->getMessage());
                SentMessage::create([
                    'contact_id' => $contact->id,
                    'campaign_id' => $campaign->id,
                    'status' => 'failed',
                    'sent_at' => Carbon::now(),
                ]);
            }
        }

        return redirect()->route('campaigns.index')->with('success', 'Mensajes enviados exitosamente.');
    }
}
