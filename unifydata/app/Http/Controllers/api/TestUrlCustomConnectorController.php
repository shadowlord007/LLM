<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Traits\ResponseGenerationTrait;
use App\Traits\AuthenticationHandlerTrait;

class TestUrlCustomConnectorController extends Controller
{
    use ResponseGenerationTrait;
    use AuthenticationHandlerTrait;
    public function testUrl(Request $request, $id)
    {
        $url = $request->all();

        $connector = CustomConnector::find($id);
        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $streamUrl = $url['stream_url'];
        $streamName = $url['name'];
        $method = $url['method'];
        $primaryKey = $url['primary_key'] ?? [];
        $fullUrl = $this->getFullUrl($connector->base_url, $streamUrl);

        try {
            $response = $this->makeAuthenticatedRequest($fullUrl, $connector->auth_type, $connector->auth_credentials, $method);
            $responseData = json_decode($response->getBody(), true);

            if (is_null($responseData)) {
                return response()->json(['message' => 'Invalid JSON response from the API'], 422);
            }

            $responseSchema = $this->getApiSchema($responseData);
            $headers = $response->getHeaders();
            $state = $this->createState($streamName);
            // Validate primary key
            if (!$this->validatePrimaryKey($responseData, $primaryKey)) {
                return response()->json(['message' => 'Primary key validation failed', 'data' => $responseData, 'schema' => $responseSchema, 'headers' => $headers, 'state' => $state], 422);
            }

            if ($response->successful()) {
                return response()->json(['message' => 'Connection successful', 'data' => $responseData, 'schema' => $responseSchema, 'headers' => $headers, 'status' => $state]);
            } else {
                return response()->json(['message' => 'Connection failed', 'status' => $response->status()]);
            }
        } catch (\Exception $e) {
            return response()->json(['message'=> "Error".$e->getMessage()],500);
        }
    }

    private function getFullUrl($baseUrl, $streamUrl)
    {
        if (filter_var($streamUrl, FILTER_VALIDATE_URL)) {
            return $streamUrl;
        } else {
            return rtrim($baseUrl, '/') . '/' . ltrim($streamUrl, '/');
        }
    }
}
