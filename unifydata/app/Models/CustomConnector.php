<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomConnector extends Model
{
    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = "custom_connectors";

    protected $fillable = [
        'name',
        'base_url',
        'auth_type',
        'auth_credentials',
        'streams',
        'pagination',
        'incremental_sync',
        'status'
    ];
    protected $casts = [
        'auth_credentials' => 'array',
        'streams' => 'array',
        'pagination' => 'array',
        'incremental_sync'=>'array',
    ];
}
