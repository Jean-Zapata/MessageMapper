<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SentMessage;
use App\Models\Device;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    // Devuelve todas las campañas con relaciones
    public function index()
    {
        $campaigns = Campaign::with('contacts', 'device')->get();
        return response()->json($campaigns);
    }

    // Crea una nueva campaña y asocia los contactos filtrados por etiquetas
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'message'        => 'required|string',
            'device_id'      => 'required|exists:devices,id',
            'tags'           => 'required|array',
            'scheduled_time' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $campaign = Campaign::create([
            'name'           => $request->name,
            'message'        => $request->message,
            'device_id'      => $request->device_id,
            'status'         => 'scheduled',
            'scheduled_time' => Carbon::parse($request->scheduled_time),
        ]);

        // Filtrar contactos que tengan alguna de las etiquetas seleccionadas
        $contacts = Contact::whereHas('tags', function ($query) use ($request) {
            $query->whereIn('tags.id', $request->tags);
        })->get();

        $campaign->contacts()->sync($contacts);

        return response()->json($campaign, 201);
    }

    // Devuelve una campaña específica con sus relaciones
    public function show($id)
    {
        $campaign = Campaign::with('contacts', 'device')->findOrFail($id);
        return response()->json($campaign);
    }

    // Actualiza una campaña y reasocia sus contactos
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'message'        => 'required|string',
            'device_id'      => 'required|exists:devices,id',
            'tags'           => 'required|array',
            'scheduled_time' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $campaign = Campaign::findOrFail($id);
        $campaign->update([
            'name'           => $request->name,
            'message'        => $request->message,
            'device_id'      => $request->device_id,
            'scheduled_time' => Carbon::parse($request->scheduled_time),
        ]);

        $contacts = Contact::whereHas('tags', function ($query) use ($request) {
            $query->whereIn('tags.id', $request->tags);
        })->get();

        $campaign->contacts()->sync($contacts);

        return response()->json($campaign);
    }

    // Elimina una campaña
    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();
        return response()->json(['message' => 'Campaña eliminada exitosamente.'], 204);
    }

    // Envía mensajes basados en la campaña
    public function sendMessage(Request $request, $campaignId)
    {
        $request->validate([
            'custom_message' => 'nullable|string',
        ]);

        $campaign = Campaign::with('contacts', 'device')->findOrFail($campaignId);

        if ($campaign->device->status !== 'active') {
            return response()->json(['error' => 'El dispositivo asociado no está activo.'], 400);
        }

        $message = $request->custom_message ?? $campaign->message;

        if ($campaign->contacts->isEmpty()) {
            return response()->json(['error' => 'No hay contactos asociados a la campaña.'], 400);
        }

        $apiToken = env('QR_API_TOKEN');
        $apiUrl = rtrim(env('QR_API_URL'), '/') . '/api/message/send-text';

        if (empty($apiUrl) || empty($apiToken)) {
            Log::error('La URL o el token de la API no están configurados correctamente.');
            return response()->json(['error' => 'Configuración de API incorrecta.'], 500);
        }

        foreach ($campaign->contacts as $contact) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type'  => 'application/json',
                ])->post($apiUrl, [
                    'number'  => $contact->phone_number,
                    'message' => $message,
                ]);

                $responseData = $response->json();

                // Crear registro del mensaje enviado
                SentMessage::create([
                    'contact_id'  => $contact->id,
                    'campaign_id' => $campaign->id,
                    'status'      => $response->successful() ? 'success' : 'failed',
                    'sent_at'     => Carbon::now(),
                ]);

                if (!$response->successful()) {
                    Log::error("Error enviando mensaje a {$contact->phone_number}: " . json_encode($responseData));
                }
            } catch (\Exception $e) {
                Log::error("Error enviando mensaje a {$contact->phone_number}: " . $e->getMessage());
                SentMessage::create([
                    'contact_id'  => $contact->id,
                    'campaign_id' => $campaign->id,
                    'status'      => 'failed',
                    'sent_at'     => Carbon::now(),
                ]);
            }
        }

        return response()->json(['message' => 'Mensajes enviados exitosamente.']);
    }
}
