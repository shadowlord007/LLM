<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class CustomConnectorController extends Controller
{
    public function index()
    {
        $connectors = CustomConnector::all();
        return response()->json($connectors);
    }

    public function createConnector(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'base_url' => 'required|url',
            'auth_type' => 'required|string|in:none,api_key,bearer,basic,oauth,session_token',
            'auth_details' => 'nullable|array',
        ]);

        $connector = CustomConnector::create($validated);

        return response()->json(['message' => 'Connector created successfully', 'data' => $connector]);
    }

    public function addStream(Request $request, $id)
    {
        $connector = CustomConnector::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'url' => 'required|string',
        ]);

        $streams = json_decode($connector->streams, true) ?? [];
        $streams[] = $validated;

        $connector->update(['streams' => json_encode($streams)]);

        return response()->json(['message' => 'Stream added successfully', 'data' => $connector]);
    }

    public function testStreamByUrl($url)
{
    $decodedUrl = urldecode($url);

    // Extract the path part from the URL
    $urlPath = parse_url($decodedUrl, PHP_URL_PATH);

    // Find the connector that matches the stream URL or base URL
    $connectors = CustomConnector::all();
    $matchedConnector = null;
    $matchedStream = null;

    foreach ($connectors as $connector) {
        $streams = json_decode($connector->streams, true);

        // Check if any stream URL matches the decoded URL or URL path
        foreach ($streams as $stream) {
            $streamUrl = filter_var($stream['url'], FILTER_VALIDATE_URL) ? $stream['url'] : rtrim($connector->base_url, '/') . '/' . ltrim($stream['url'], '/');
            if ($streamUrl == $decodedUrl || $stream['url'] == '/' . $urlPath) {
                $matchedConnector = $connector;
                $matchedStream = $stream;
                break 2; // Break both loops
            }
        }
    }

    if (!$matchedConnector || !$matchedStream) {
        return response()->json(['message' => 'Connector not found for the provided URL'], 404);
    }

    // Determine if the stream URL is a full URL or a relative path
    $streamUrl = filter_var($matchedStream['url'], FILTER_VALIDATE_URL) ? $matchedStream['url'] : rtrim($matchedConnector->base_url, '/') . '/' . ltrim($matchedStream['url'], '/');

    // Make the authenticated request
    $response = $this->makeAuthenticatedRequest($matchedConnector, $streamUrl);

    if ($response->successful()) {
        $matchedConnector->update(['published' => true]);
        return response()->json(['message' => 'Stream tested successfully and connector published', 'data' => $response->json()]);
    }

    return response()->json(['message' => 'Failed to test stream', 'error' => $response->body()], 400);
}

    private function makeAuthenticatedRequest($connector, $url)
    {
        switch ($connector->auth_type) {
            case 'api_key':
                return Http::withHeaders([
                    'Authorization' => 'API-Key ' . $connector->auth_details['api_key']
                ])->get($url);
            case 'bearer':
                return Http::withToken($connector->auth_details['token'])->get($url);
            case 'basic':
                return Http::withBasicAuth($connector->auth_details['username'], $connector->auth_details['password'])->get($url);
            case 'oauth':
                // Implement OAuth logic here
                break;
            case 'session_token':
                return Http::withHeaders([
                    'Authorization' => 'Session ' . $connector->auth_details['token']
                ])->get($url);
            default:
                return Http::get($url);
        }
    }

}
