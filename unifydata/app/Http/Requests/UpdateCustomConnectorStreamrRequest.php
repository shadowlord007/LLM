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

            'name' => 'required|string|max;20|regex:/^[A-Za-z0-9 ]+$/',
            'url' => 'required|string',
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

            'pagination.cursor_value' => 'nullable|required_if:pagination.next_page_cursor,Custom|string',
            'pagination.stop_condition' => 'nullable|required_if:pagination.next_page_cursor,Custom|string',
            'pagination.page_size' => 'nullable|integer|required_if:pagination.strategy,Cursor Pagination',

            'pagination.inject_first_request' => 'nullable|boolean',

            'incremental_sync' => 'nullable|array',
            'incremental_sync.enabled' => 'nullable|boolean',
            'incremental_sync.cursor_field' => 'required_if:incremental_sync.enabled,true|string',

            'incremental_sync.cursor_datetime_formats' => 'required_if:incremental_sync.enabled,true|array',
            'incremental_sync.cursor_datetime_formats.*' => 'string|distinct',

            'incremental_sync.api_time_filtering' => 'required_if:incremental_sync.enabled,true|in:Range,Start,No filter (data feed)',

            'incremental_sync.start_date_time_start_type'=> 'required_if:incremental_sync.api_time_filtering,Range,Start|string|in:User Input,Custom',
            'incremental_sync.start_date_time_start_input'=> 'required_if:incremental_sync.start_date_time_type,User Input|string',
            'incremental_sync.start_date_times_start_value'=> 'required_if:incremental_sync.start_date_time_type,Custom|string',
            'incremental_sync.start_date_times_start_format'=> 'required_if:incremental_sync.start_date_time_type,Custom|string',

            'incremental_sync.start_date_time_end_type'=> 'required_if:incremental_sync.api_time_filtering,Range|string|in:User Input,Custom,Now',
            'incremental_sync.start_date_time_end_input'=> 'required_if:incremental_sync.start_date_time_type,User Input|string',
            'incremental_sync.start_date_times_end_value'=> 'required_if:incremental_sync.start_date_time_type,Custom|string',
            'incremental_sync.start_date_times_end_format'=> 'required_if:incremental_sync.start_date_time_type,Custom|string',


        ];
    }
}
