<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CustomConnectorController;
use App\Http\Controllers\api\TestUrlCustomConnectorController;

Route::controller(CustomConnectorController::class)->group(function () {
    Route::get('/connectors', 'index');
    Route::post('/connectors', 'createConnector')->middleware('customValidation:create');
    Route::put('/connectors/publish/{id}', 'publishConnector');
    Route::put('/connectors/{id}', 'updateConnector')->middleware('customValidation:baseUpdate');
    Route::put('/connectors/{id}/updateStream/{index}', 'updateStreams')->middleware('customValidation:streamUpdate');
    Route::delete('/connectors/{id}',  'deleteConnector');
    Route::get('/connectors/drafts', 'listDrafts');
    Route::get('/connectors/published',  'listPublished');
    Route::get('/connectors/selectedConnector/{id}', 'selectedConnectorDetails');
    Route::delete('/connectors/deleteStream/{connectorId}/streams/{streamIndex}',  'deleteStream');
});

Route::get('/test/url/{id}', [TestUrlCustomConnectorController::class, 'testUrl'])->middleware('customValidation:test');
