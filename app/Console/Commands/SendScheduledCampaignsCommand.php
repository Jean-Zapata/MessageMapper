<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Http\Controllers\Api\CampaignController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SendScheduledCampaignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     * php artisan make:command SendScheduledCampaignsCommand
     * @var string
     */
    protected $signature = 'campaigns:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía las campañas programadas que ya cumplieron su tiempo';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Verificando campañas programadas...');
        
        $now = Carbon::now();
        $campaigns = Campaign::where('status', 'scheduled')
                    ->where('scheduled_time', '<=', $now)
                    ->get();
        
        $this->info("Se encontraron {$campaigns->count()} campañas para enviar.");
        
        if ($campaigns->isEmpty()) {
            return 0;
        }
        
        foreach ($campaigns as $campaign) {
            try {
                $this->info("Enviando campaña #{$campaign->id}: {$campaign->name}");
                
                // Actualizar estado a "en progreso"
                $campaign->status = 'in_progress';
                $campaign->save();
                
                // Crear una instancia directa del controlador
                $campaignController = new CampaignController();
                
                // Crear una nueva request vacía
                $request = new Request();
                
                // Enviar la campaña
                $campaignController->sendMessage($request, $campaign->id);
                
                // Verificar y actualizar el estado de SentMessages
                $totalMessages = $campaign->contacts->count();
                $sentMessages = $campaign->sentMessages()->count();
                
                if ($sentMessages >= $totalMessages) {
                    // Actualizar estado a "completada" solo si todos los mensajes fueron enviados
                    $campaign->status = 'completed';
                    $campaign->save();
                    
                    $this->info("Campaña #{$campaign->id} enviada exitosamente y marcada como completada.");
                } else {
                    $this->warn("Campaña #{$campaign->id}: No todos los mensajes fueron enviados. Enviados: $sentMessages/$totalMessages");
                }
            } catch (\Exception $e) {
                $this->error("Error al enviar la campaña #{$campaign->id}: " . $e->getMessage());
                Log::error("Error al enviar la campaña #{$campaign->id}: " . $e->getMessage());
                
                // Marcar como fallida
                $campaign->status = 'failed';
                $campaign->save();
            }
        }
        
        return 0;
    }
}