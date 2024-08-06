<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CustomConnectorController;


Route::get('/connectors', [CustomConnectorController::class, 'index']);
Route::post('connectors', [CustomConnectorController::class, 'createConnector'])->middleware('customValidation:create');
Route::get('/test-url/{id}', [CustomConnectorController::class, 'testUrl'])->middleware('customValidation:test');
Route::put('connectors/publish/{id}', [CustomConnectorController::class, 'publishConnector']);
Route::put('connectors/{id}', [CustomConnectorController::class, 'updateConnector'])->middleware('customValidation:update');
Route::delete('connectors/{id}', [CustomConnectorController::class, 'deleteConnector']);
Route::get('connectors/drafts', [CustomConnectorController::class, 'listDrafts']);
Route::get('connectors/published', [CustomConnectorController::class, 'listPublished']);
Route::get('connectors/selectedConnector/{id}', [CustomConnectorController::class, 'selectedConnectorDetails']);
Route::delete('/connectors/deleteStream/{connectorId}/streams/{streamIndex}', [CustomConnectorController::class, 'deleteStream']);