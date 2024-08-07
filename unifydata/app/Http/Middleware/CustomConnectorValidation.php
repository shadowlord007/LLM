<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\CustomTestConnectorRequest;
use App\Http\Requests\CustomCreateConnectorRequest;
use App\Http\Requests\UpdateCustomConnectorRequest;
use App\Http\Requests\UpdateCustomConnectorStreamrRequest;

class CustomConnectorValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $key): Response
    {
        if($key === 'create'){
            app(CustomCreateConnectorRequest::class);
        } else if($key === 'baseUpdate'){
            app(UpdateCustomConnectorRequest::class);
        } else if ($key === 'streamUpdate'){
            app(UpdateCustomConnectorStreamrRequest::class);
        }else if ($key === 'test'){
            app(CustomTestConnectorRequest::class);
        }
        return $next($request);
    }
}
