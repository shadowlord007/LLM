<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class CustomResponseServeiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Response::macro('success', function($data = null, $message = 'success', $statusCode = 200){
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,

            ], $statusCode);
        });

        Response::macro('error', function($message = 'Error', $statusCode = 500){
            return response()->json([
                'success' => false,
                'error' => $message,
            ], $statusCode);
        });
    }
}
