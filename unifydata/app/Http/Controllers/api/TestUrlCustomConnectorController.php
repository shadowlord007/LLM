<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Traits\ResponseGenerationTrait;
use App\Traits\AuthenticationHandlerTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class TestUrlCustomConnectorController extends Controller
{
    //using traits
    use ResponseGenerationTrait;
    use AuthenticationHandlerTrait;

    //use to test url
    public function testUrl($id, $streamIndex)
    {

        $connector = CustomConnector::find($id);
        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        
        $existingStream = json_decode($connector->streams);
        if($streamIndex >= count($existingStream))
        {
            return response()->json(['message' => 'stream not found'], 404);
        }
        $stream = $existingStream[$streamIndex];

        $streamUrl = $stream->url;
        $streamName = $stream->name;
        $method = $stream->method;
        $primaryKey = $stream->primary_key;
        $fullUrl = $this->getFullUrl($connector->base_url, $streamUrl); //using to make an absoulte path

        try {
            $response = $this->makeAuthenticatedRequest($fullUrl, $connector->auth_type, $connector->auth_credentials, $method); //to check the auth type and use its creds to get response from path.

            $responseData = json_decode($response->getBody(), true);

            if (is_null($responseData)) {
                return response()->json(['message' => 'Invalid JSON response from the API'], 422);
            }

            $responseSchema = $this->generateSchema($responseData); //generate schema of response data
            $headers = $response->getHeaders();
            $state = $this->createState($streamName); //creating state for the generated response
            // Validate primary key
            if (!$this->validatePrimaryKey($responseData, $primaryKey)) {
                return response()->json([ 'data' => $responseData, 'schema' => $responseSchema, 'response' =>[ 'headers'=>$headers,'status'=>'422','body'=>['message' => 'Primary key validation failed','code'=>'422',]], 'state' => $state]);
            }

            if ($response->successful()) {
                return response()->json([ 'data' => $responseData, 'schema' => $responseSchema,  'response' =>[ 'headers'=>$headers,'status'=>'200','body'=>['message' => 'Connection successful','code'=>'200',]], 'status' => $state]);
            } else {
                return response()->json([ 'status' => $response->status(), 'response' =>[ 'headers'=>$headers,'status'=>'404','body'=>['message' => 'Connection failed','code'=>'404',]]]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => "Error" . $e->getMessage()], 500);
        }
    }

    //use to create an absolute path using reletive and base url
    private function getFullUrl($baseUrl, $streamUrl)
    {
        if (filter_var($streamUrl, FILTER_VALIDATE_URL)) {
            return $streamUrl;
        } else {
            return rtrim($baseUrl, '/') . '/' . ltrim($streamUrl, '/');
        }
    }
}
