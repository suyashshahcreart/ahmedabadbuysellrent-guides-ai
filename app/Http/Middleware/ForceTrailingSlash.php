<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceTrailingSlash
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->getPathInfo();
        if ($path !== '/' && ! str_ends_with($path, '/') && ! $request->is('administrator*') && ! $request->is('storage*') && ! preg_match('/\.[a-z0-9]+$/i', $path)) {
            $qs = $request->getQueryString();

            return redirect($path.'/'.($qs ? '?'.$qs : ''), 301);
        }

        return $next($request);
    }
}
