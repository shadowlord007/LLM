<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CustomConnectorController;
use App\Http\Controllers\api\TestUrlCustomConnectorController;

Route::controller(CustomConnectorController::class)->group(function () {
    Route::get('/custom-connectors', 'index');
    Route::post('/custom-connectors', 'createConnector')->middleware('customValidation:create');
    Route::put('/custom-connectors/base-url/{id}', 'updateConnector')->middleware('customValidation:baseUpdate');
    Route::put('/custom-connectors/{id}/update-stream/{index}', 'updateStreams')->middleware('customValidation:streamUpdate');
    Route::put('/custom-connectors/publish/{id}', 'publishConnector');
    Route::delete('/custom-connectors/{id}',  'deleteConnector');
    Route::get('/custom-connectors/selected-connector/{id}', 'selectedConnectorDetails');
    Route::delete('/custom-connectors/delete-stream/{connectorId}/streams/{streamIndex}',  'deleteStream');
});

Route::get('/test-url/{id}/stream-url/{index}', [TestUrlCustomConnectorController::class, 'testUrl']);
