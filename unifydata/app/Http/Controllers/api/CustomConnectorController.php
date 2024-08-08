<?php

namespace App\Http\Controllers\api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class CustomConnectorController extends Controller
{
    //This function is used for returning all custom connectors.
    public function index()
    {
        $connectors = CustomConnector::select(['name', 'status'])->get();
        return response()->json($connectors);
    }

    //This function is used for publish custom connetors.
    public function publishConnector(Request $request, $id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $connector->status = 'published';
        $connector->save();

        $connector->streams = json_decode($connector->streams, true);//To return response into array form instead of json

        return response()->json(['message' => 'Connector published successfully', $connector]);
    }

    //This function is used for create custom connetors and add streams in it.
    public function createConnector(Request $request)
    {
        $data = $request->all();
        $connector = CustomConnector::where('name', $data['name'])->first();

        if ($connector) {
            return $this->transformStreams($data, $connector);
        } else {
            $connectorData = [
                'name' => $data['name'],
                'base_url' => $data['base_url'],
                'auth_type' => $data['auth_type'],
                'auth_credentials' => $data['auth_credentials'],
                'streams' => json_encode([]),
                'status' => 'draft',
            ];

            $connector = CustomConnector::create($connectorData);
            $this->transformStreams($data, $connector);

            return response()->json(['message' => 'Connector and stream created successfully', $connector]);
        }
    }

    // Mainly use for add streams
    private function transformStreams($data, $connector)
    {

        $existingStreams = json_decode($connector->streams, true);

        $existingStreamsName = array_column($existingStreams, 'name');
        $streams = $data['streams'];
        $streamName = array_column($streams, 'name');
        if (in_array($streamName[0], $existingStreamsName)) {

            return response()->json(['message' => 'Error! Stream Name must be unique.']);
        }

        $newStreams = array_map(function ($stream) {
            return [
                'name' => $stream['name'],
                'url' => $stream['url'],
                'method' => $stream['method'],
                'primary_key' => $stream['primary_key'] ?? [],
            ];
        }, $data['streams']);

        $updatedStreams = array_merge($existingStreams, $newStreams);

        $connector->streams = json_encode($updatedStreams);
        $connector->save();
        $connector->streams = json_decode($connector->streams, true);//To return response into array form instead of json

        return response()->json(['message' => 'Streams added successfully',  $connector]);
    }

    //To update base data of custom connector
    public function updateConnector(Request $request, $id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $data = $request->all();
        $connector->update($data);
        $this->setDraft($connector);


        $connector->streams = json_decode($connector->streams, true);//To return response into array form instead of json
        return response()->json(['message' => 'Connector updated successfully', $connector]);
    }

    //To update streams
    public function updateStreams(Request $request, $id,$index)
    {

        $connector = CustomConnector::find($id);
        $data = $request->all();
        $existingStreams = json_decode($connector->streams, true);

        $existingStreams[$index] = $data;

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $connector->streams = json_encode($existingStreams);
        $this->setDraft($connector);

        $connector->streams = json_decode($connector->streams, true);//To return response into array form instead of json
        return response()->json(['message' => 'Connector updated successfully', $connector]);
    }
    //to set connetor as draft after any update
    private function setDraft($connector)
    {
        $connector->status = "draft";
        $connector->save();
    }
    //To delete connector
    public function deleteConnector($id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $connector->delete();

        return response()->json(['message' => 'Connector deleted successfully']);
    }

    //Return list of drafts connectors
    public function listDrafts()
    {
        $drafts = CustomConnector::select(['name', 'status'])->where('status', 'draft')->get();
        return response()->json($drafts);
    }
    //Return list of publish connectors
    public function listPublished()
    {
        $published = CustomConnector::select(['name', 'status'])->where('status', 'published')->get();
        return response()->json($published);
    }
    //Return details of selected connectors
    public function selectedConnectorDetails($id)
    {
        $connector = CustomConnector::find($id);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }

        $connector->streams = json_decode($connector->streams, true);
        return response()->json($connector);
    }
    //Delete streams from a connectors
    public function deleteStream($connectorId, $streamIndex)
    {
        $connector = CustomConnector::find($connectorId);

        if (!$connector) {
            return response()->json(['message' => 'Connector not found'], 404);
        }
        $streams = json_decode($connector->streams, true);

        if (!isset($streams[$streamIndex])) {
            return response()->json(['message' => 'Stream not found at the given index'], 404);
        }

        unset($streams[$streamIndex]);

        if (count($streams) === 0) {
            $connector->delete();
            return response()->json(['message' => 'Connector deleted as it contions no stream now']);
        }

        $streams = array_values($streams);

        $connector->streams = json_encode($streams);
        $connector->save();

        $connector->streams = json_decode($connector->streams, true);//To return response into array form instead of json
        return response()->json(['message' => 'Stream deleted successfully', $connector]);
    }
}
