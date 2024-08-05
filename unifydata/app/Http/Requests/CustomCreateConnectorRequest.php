<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomCreateConnectorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|unique:CustomConnector,name|string',
            'base_url' => 'required|url',
            'auth_type' => 'required|string',
            'auth_credentials' => 'nullable|array',
            'streams' => 'required|array',
            'streams.*.name' => 'required|string',
            'streams.*.stream_url' => 'required|string',
        ];
    }
}
