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
        $streamName = $url['name'];
        $fullUrl = $this->getFullUrl($connector->base_url, $streamUrl);

        $response = $this->makeAuthenticatedRequest($fullUrl, $connector->auth_type, $connector->auth_credentials);

        $responseData = json_decode($response->getBody(), true);


        $responseSchema = $this->getApiSchema($responseData);
        $headers = $response->getHeaders();
        $status = $this->createStatus($streamName);

        if ($response->successful()) {
            return response()->json(['message' => 'Connection successful', 'data' => $responseData, 'schema' => $responseSchema, 'headers' => $headers,'status'=> $status]);
        } else {
            return response()->json(['message' => 'Connection failed', 'status' => $response->status()]);
        }
    }

    public function getApiSchema($responseData)
    {
        // Generate the JSON schema
        $schema =  [
            '$schema' => 'http://json-schema.org/schema#',
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => $this->generateProperties($responseData)
        ];

        // Return the schema as a JSON response
        return response()->json($schema);
    }

    private function generateProperties(array $data)
    {
        $properties = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssoc($value)) {
                    $properties[$key] = [
                        'type' => ['object', 'null'],
                        'properties' => $this->generateProperties($value)
                    ];
                } else {
                    $properties[$key] = [
                        'type' => ['array', 'null'],
                        'items' => [
                            'type' => ['object', 'null'],
                            'properties' => $this->generateProperties($value[0] ?? [])
                        ]
                    ];
                }
            } else {
                $properties[$key] = [
                    'type' => [gettype($value), 'null']
                ];
            }
        }

        return $properties;
    }

    private function isAssoc(array $array)
    {
        if (array() === $array) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function createStatus($streamName)
    {

        return $status =[
            [
                'type' => 'STREAM',
                'stream' => [
                    'stream_descriptor' => [
                        'name' => $streamName
                    ],
                    'stream_state' => [
                        '__ab_no_cursor_state_message' => true
                    ]
                ],
                'sourceStats' => [
                    'recordCount' => 1
                ]
            ]
        ];
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
            case 'OAuth':

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
        $drafts = CustomConnector::select(['name', 'status'])->where('status', 'draft')->get();
        return response()->json(['data' => $drafts]);
    }

    public function listPublished()
    {
        $published = CustomConnector::select(['name', 'status'])->where('status', 'published')->get();
        return response()->json(['data' => $published]);
    }

    public function selectedConnectorDetails($id)
    {
        //  find the connector by its ID
        $connector = CustomConnector::find($id);

        // Check if the connector was found
        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        // Return the connector details as a JSON response
        return response()->json(['data' => $connector]);
    }
    public function deleteStream($connectorId, $streamIndex)
    {
        // Find the connector by ID
        $connector = CustomConnector::find($connectorId);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }
       
        // Decode the streams JSON into an array
        $streams = json_decode($connector->streams, true);

        if (!isset($streams[$streamIndex])) {
            return response()->json(['message' => 'Stream not found at the given index'], 404);
        }

        // Remove the stream at the given index
        unset($streams[$streamIndex]);

        // If no stream left, delete the connector
        if (count($streams) === 0) {
            $connector->delete();
            return response()->json(['message' => 'Connector deleted as it contions no stream now']);
        } 

        // Re-index the array to ensure keys are sequential  
        $streams = array_values($streams);

        // Update the connector with the modified streams
        $connector->streams = json_encode($streams);
        $connector->save();

        return response()->json(['message' => 'Stream deleted successfully', 'data' => $connector]);
    }
}
