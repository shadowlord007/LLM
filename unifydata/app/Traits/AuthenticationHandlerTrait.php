<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait AuthenticationHandlerTrait
{
    public function makeAuthenticatedRequest($url, $authType, $authCredentials, $method)
    {
        $client = Http::withOptions(['base_uri' => $url]);
        

        switch ($authType) {
            case 'No Auth':
                $response = ($method === 'POST') ? $client->post($url) : $client->get($url);
                break;
            case 'API Key':
                $response = $this->handleApiKeyAuth($client, $url, $authCredentials, $method);
                break;
            case 'Bearer':
                $response = $client->withToken($authCredentials['token'])->{$method}($url);
                break;
            case 'Basic HTTP':
                $response = $client->withBasicAuth($authCredentials['username'], $authCredentials['password'])->{$method}($url);
                break;
            case 'Session Token':
                $response = $client->withHeaders(['Session-Token' => $authCredentials['session_token']])->{$method}($url);
                break;
            case 'OAuth':
                $response = $this->handleOAuth($url, $authCredentials);
                break;
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
                return response()->json(['error' => 'Invalid inject into type'], 400);
        }

        return $response;
    }

    private function handleOAuth($url, $authCredentials)
    {
        // Validate OAuth credentials
        $validatedData  = $authCredentials->validate([
            'grant_type' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'refresh_token' => 'required_if:grant_type,refresh_token|string',
            'token_url' => 'required|url',
        ]);

        // Request for access token
        $tokenResponse = Http::asForm()->post($validatedData['token_url'], [
            'grant_type' => $validatedData['grant_type'],
            'client_id' => $validatedData['client_id'],
            'client_secret' => $validatedData['client_secret'],
            'refresh_token' => $validatedData['grant_type'] === 'refresh_token' ? $validatedData['refresh_token'] : null,
        ]);

        if (!$tokenResponse->successful()) {
            return response()->json(['error' => 'Failed to obtain access token', 'details' => $tokenResponse->body()], $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        if (!isset($tokenData['access_token'])) {
            return response()->json(['error' => 'Access token not found in the response'], 500);
        }


        $accessToken = $tokenData['access_token'];

        // Fetch data from the URL using access token
        $dataResponse = Http::withToken($accessToken)->get($url);

        if ($dataResponse->successful()) {
            return response()->json(['data' => $dataResponse->json()], 200);
        } else {
            return response()->json(['error' => 'Failed to fetch data', 'details' => $dataResponse->body()], $dataResponse->status());
        }
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
}
