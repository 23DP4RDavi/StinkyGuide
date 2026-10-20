<?php

namespace App\Http\Middleware;

use App\Models\TrafficEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordTraffic
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $route = $request->route();

        TrafficEvent::create([
            'user_id' => $request->user()?->id,
            'path' => $route ? $route->uri() : 'unmatched',
            'method' => $request->method(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
