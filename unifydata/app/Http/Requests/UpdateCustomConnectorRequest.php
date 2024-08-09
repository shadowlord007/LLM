<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomConnectorRequest extends FormRequest
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
            'name' => 'required|string',
            'base_url' => 'required|url',
            'auth_type' => 'required|string|in:No Auth,API Key,Bearer,Basic HTTP,OAuth,Session Tokken',
            'auth_credentials' => 'nullable|array',
        ];
    }
}
