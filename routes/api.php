<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Controllers
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\CampaignController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//API routes Tags
Route::apiResource('tags', TagController::class);

//API routes Contacts
Route::apiResource('contacts', ContactController::class);

//API routes Devices
Route::apiResource('devices', DeviceController::class);
Route::patch('devices/{id}/toggle-status', [DeviceController::class, 'toggleStatus']);

//API routes Campaigns
Route::apiResource('campaigns', CampaignController::class);
Route::post('campaigns/{campaign}/send-message', [CampaignController::class, 'sendMessage']);
Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'sendMessage'])->name('campaigns.send');
Route::get('/campaigns/{id}/send', [CampaignController::class, 'send'])->name('campaigns.send');
