<?php

namespace App\Http\Controllers\api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class CustomConnectorController extends Controller
{
    public function index()
    {
        $connectors = CustomConnector::select(['name', 'status'])->get();
        return response()->json($connectors);
    }

    public function testUrl(Request $request, $id)
    {
        $url = $request->all();

        $connector = CustomConnector::find($id);
        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $streamUrl = $url['stream_url'];
        $fullUrl = $this->getFullUrl($connector->base_url, $streamUrl);

        $response = $this->makeAuthenticatedRequest($fullUrl, $connector->auth_type, $connector->auth_credentials);

        if ($response->successful()) {
            return response()->json(['message' => 'Connection successful', 'data' => $response->json()]);
        } else {
            return response()->json(['message' => 'Connection failed', 'status' => $response->status()]);
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

    private function makeAuthenticatedRequest($url, $authType, $authCredentials)
    {
        $client = Http::withOptions(['base_uri' => $url]);

        switch ($authType) {
            case 'No_Auth':
                $response = $client->get($url);
                break;
            case 'API_Key':
                $response = $this->handleApiKeyAuth($client, $url, $authCredentials);
                break;
            case 'Bearer':
                $response = $client->withToken($authCredentials['token'])->get($url);
                break;
            case 'Basic_HTTP':
                $response = $client->withBasicAuth($authCredentials['username'], $authCredentials['password'])->get($url);
                break;
            case 'Session_Token':
                $response = $client->withHeaders(['Session-Token' => $authCredentials['session_token']])->get($url);
                break;
            default:
                throw new \Exception('Invalid authentication type');
        }

        return $response;
    }

    private function handleApiKeyAuth($client, $url, $authCredentials)
    {
        $injectInto = $authCredentials['inject_into'];
        $paramName = $authCredentials['parameter_name'];
        $apiKey = $authCredentials['api_key'];

        switch ($injectInto) {
            case 'Query Parameter':
                $url = $this->injectApiKeyIntoUrl($url, $paramName, $apiKey);
                $response = $client->get($url);
                break;
            case 'Header':
                $response = $client->withHeaders([$paramName => $apiKey])->get($url);
                break;
            case 'Body data (urlencoded form)':
                $response = $client->asForm()->post($url, [$paramName => $apiKey]);
                break;
            case 'Body JSON payload':
                $response = $client->asJson()->post($url, [$paramName => $apiKey]);
                break;
            default:
                throw new \Exception('Invalid injection method');
        }

        return $response;
    }

    // inject the api key into the request url
    private function injectApiKeyIntoUrl($url, $paramName, $apiKey)
    {
        $parsedUrl = parse_url($url);
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] . '&' : '';
        $query .= urlencode($paramName) . '=' . urlencode($apiKey);

        return $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $query;
    }

    public function publishConnector(Request $request, $id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $connector->status = 'published';
        $connector->save();

        return response()->json(['message' => 'Connector published successfully', 'data' => $connector]);
    }

    public function createConnector(Request $request)
    {
        $data = $request->all();

        $connector = CustomConnector::where('name', $data['name'])->first();
        if ($connector) {
            // Connector exists, update streams
            $existingStreams = json_decode($connector->streams, true);
            $newStreams = array_map(function ($stream) {
                return [
                    'name' => $stream['name'],
                    'url' => $stream['stream_url'],
                ];
            }, $data['streams']);

            $updatedStreams = array_merge($existingStreams, $newStreams);

            $connector->streams = json_encode($updatedStreams);
            $connector->save();

            return response()->json(['message' => 'Streams added successfully', 'data' => $connector]);
        } else {
            // Connector does not exist, create new connector
            $streams = array_map(function ($stream) {
                return [
                    'name' => $stream['name'],
                    'url' => $stream['stream_url'],
                ];
            }, $data['streams']);

            $connectorData = [
                'name' => $data['name'],
                'base_url' => $data['base_url'],
                'auth_type' => $data['auth_type'],
                'auth_credentials' => $data['auth_credentials'],
                'streams' => json_encode($streams),
                'status' => 'draft',
            ];

            $connector = CustomConnector::create($connectorData);

            return response()->json(['message' => 'Connector created successfully', 'data' => $connector]);
        }
    }


    public function updateConnector(Request $request, $id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $data = $request->all();
        $connector->update([
            'name' => $data['name'],
            'base_url' => $data['base_url'],
            'auth_type' => $data['auth_type'],
            'auth_credentials' => $data['auth_credentials'],
            'streams' => json_encode($data['streams']),
        ]);

        return response()->json(['message' => 'Connector updated successfully', 'data' => $connector]);
    }

    public function deleteConnector($id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $connector->delete();

        return response()->json(['message' => 'Connector deleted successfully']);
    }

    public function listDrafts()
    {
        $drafts = CustomConnector::where('status', 'draft')->get();
        return response()->json(['data' => $drafts]);
    }

    public function listPublished()
    {
        $published = CustomConnector::where('status', 'published')->get();
        return response()->json(['data' => $published]);
    }
}
