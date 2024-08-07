<?php

namespace App\Traits;

trait ResponseGenerationTrait
{
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
