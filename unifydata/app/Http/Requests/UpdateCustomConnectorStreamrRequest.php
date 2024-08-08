<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomConnectorStreamrRequest extends FormRequest
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
            'stream_url' => 'required|string',
            'method' => 'required|string|in:GET,POST',
            'primary_key' => 'nullable|array',

            'pagination' => 'nullable|array',
            'pagination.enabled' => 'nullable|boolean',
            'pagination.strategy' => 'required_if:pagination.enabled,true|in:Offset Increment,Page Increment,Cursor Pagination',
            'pagination.limit' => 'nullable|integer|required_if:pagination.strategy,Offset Increment',

            'pagination.inject_enabled' => 'nullable|boolean|required_if:pagination.enabled,true',
            'pagination.inject_into' => 'nullable|required_if:pagination.inject_enabled,true|in:Query Parameter,Header,Path,Body data (urlencoded form),Body JSON payload',
            'pagination.parameter_name' => 'nullable|required_if:pagination.inject_into,Query Parameter|string',
            'pagination.header_name' => 'nullable|required_if:pagination.inject_into,Header|string',
            'pagination.key_name' => 'nullable|required_if:pagination.inject_into,|in:Body data (urlencoded form),Body JSON payload|string',
           

            'pagination.page_size' => 'nullable|integer|required_if:pagination.strategy,Page Increment',
            'pagination.start_from_page' => 'nullable|integer|required_if:pagination.strategy,Page Increment',

            'pagination.next_page_cursor' => 'nullable|required_if:pagination.strategy,Cursor Pagination|in:Response,Header,Custom',
            'pagination.path' => 'nullable|required_if:pagination.next_page_cursor,Response|string',
            'pagination.path' => 'nullable|required_if:pagination.next_page_cursor,Header|string',

            'pagination.cursor_value' => 'required|required_if:pagination.next_page_cursor,Custom|string',
            'pagination.stop_condition' => 'nullable|required_if:pagination.next_page_cursor,Custom|string',
            'pagination.page_size' => 'nullable|integer|required_if:pagination.strategy,Cursor Pagination',

            'pagination.inject_first_request' => 'nullable|boolean'
        ];
    }
}
