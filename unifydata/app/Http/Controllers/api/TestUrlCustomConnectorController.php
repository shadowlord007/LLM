<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class TestUrlCustomConnectorController extends Controller
{
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
            $status = $this->createStatus($streamName);
            // Validate primary key
            if (!$this->validatePrimaryKey($responseData, $primaryKey)) {
                return response()->json(['message' => 'Primary key validation failed','data' => $responseData, 'schema' => $responseSchema, 'headers' => $headers,'status'=> $status], 422);
            }
            if ($response->successful()) {
                return response()->json(['message' => 'Connection successful', 'data' => $responseData, 'schema' => $responseSchema, 'headers' => $headers,'status'=> $status]);
            } else {
                return response()->json(['message' => 'Connection failed', 'status' => $response->status()]);
            }
        }catch(\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
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

    private function makeAuthenticatedRequest($url, $authType, $authCredentials, $method)
    {
        $client = Http::withOptions(['base_uri' => $url]);

        switch ($authType) {
            case 'No_Auth':
                $response = ($method === 'POST') ? $client->post($url) : $client->get($url);
                break;
            case 'API_Key':
                $response = $this->handleApiKeyAuth($client, $url, $authCredentials, $method);
                break;
            case 'Bearer':
                $response = $client->withToken($authCredentials['token'])->{$method}($url);
                break;
            case 'Basic_HTTP':
                $response = $client->withBasicAuth($authCredentials['username'], $authCredentials['password'])->{$method}($url);
                break;
            case 'Session_Token':
                $response = $client->withHeaders(['Session-Token' => $authCredentials['session_token']])->{$method}($url);
                break;
            case 'OAuth':

            default:
                return response()->json(['error' => 'Invalid authentication type'], 400);
        }

        return $response;
    }

    private function handleApiKeyAuth($client, $url, $authCredentials, $method)
    {
        $injectInto = $authCredentials['inject_into'];
        $paramName = $authCredentials['parameter_name'];
        $apiKey = $authCredentials['api_key'];

        switch ($injectInto) {
            case 'Query Parameter':
                $url = $this->injectApiKeyIntoUrl($url, $paramName, $apiKey);
                $response = $client->{$method}($url);
                break;
            case 'Header':
                $response = $client->withHeaders([$paramName => $apiKey])->{$method}($url);
                break;
            case 'Body data (urlencoded form)':
                $response = $client->asForm()->{$method}($url, [$paramName => $apiKey]);
                break;
            case 'Body JSON payload':
                $response = $client->asJson()->{$method}($url, [$paramName => $apiKey]);
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

    private function validatePrimaryKey(array $responseData, array $primaryKey)
    {
        if (empty($primaryKey)) {
            return true; // No primary key specified, skip validation
        }

        foreach ($primaryKey as $key) {
            if (!array_key_exists($key, $responseData)) {
                return false; // Primary key field not found in response data
            }
        }

        return true;
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

}
