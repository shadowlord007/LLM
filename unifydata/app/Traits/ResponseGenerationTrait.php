<?php

namespace App\Traits;

trait ResponseGenerationTrait
{
    // Define the base JSON schema with object type and additional properties allowed
    public function generateSchema($responseData)
    {
        $schema =  [
            '$schema' => 'http://json-schema.org/schema#',
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => $this->generateProperties($responseData)
        ];

        // Return the schema as a JSON response
        return response()->json($schema);
    }

// Generate and assign the properties section of the schema
    private function generateProperties(array $data)
    {
        $properties = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssoc($value)) {
                    $properties[$key] = [
                        'type' => ['object', 'null'],
                        'properties' => $this->generateProperties($value) // Recursively generate properties for the object
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

     // Check array is associative or not
    private function isAssoc(array $array)
    {
        if (array() === $array) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }


    //Create state of the response
    private function createState($streamName)
    {

        return $state = [
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
